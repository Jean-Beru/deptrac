<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Supportive\OutputFormatter;

use DOMAttr;
use DOMDocument;
use DOMElement;
use Exception;
use Qossmic\Deptrac\Contract\OutputFormatter\OutputFormatterInput;
use Qossmic\Deptrac\Contract\OutputFormatter\OutputFormatterInterface;
use Qossmic\Deptrac\Contract\OutputFormatter\OutputInterface;
use Qossmic\Deptrac\Contract\Result\CoveredRuleInterface;
use Qossmic\Deptrac\Contract\Result\LegacyResult;
use Qossmic\Deptrac\Contract\Result\RuleInterface;
use Qossmic\Deptrac\Contract\Result\SkippedViolation;
use Qossmic\Deptrac\Contract\Result\Uncovered;
use Qossmic\Deptrac\Contract\Result\Violation;

/**
 * @internal
 */
final class JUnitOutputFormatter implements OutputFormatterInterface
{
    private const DEFAULT_PATH = './junit-report.xml';

    public static function getName(): string
    {
        return 'junit';
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function finish(
        LegacyResult $result,
        OutputInterface $output,
        OutputFormatterInput $outputFormatterInput
    ): void {
        $xml = $this->createXml($result);

        $dumpXmlPath = $outputFormatterInput->getOutputPath() ?? self::DEFAULT_PATH;
        file_put_contents($dumpXmlPath, $xml);
        $output->writeLineFormatted('<info>JUnit Report dumped to '.realpath($dumpXmlPath).'</info>');
    }

    /**
     * @throws Exception
     */
    private function createXml(LegacyResult $result): string
    {
        if (!class_exists(DOMDocument::class)) {
            throw new Exception('Unable to create xml file (php-xml needs to be installed)');
        }

        $xmlDoc = new DOMDocument('1.0', 'UTF-8');
        $xmlDoc->formatOutput = true;

        $this->addTestSuites($result, $xmlDoc);

        return (string) $xmlDoc->saveXML();
    }

    private function addTestSuites(LegacyResult $result, DOMDocument $xmlDoc): void
    {
        $testSuites = $xmlDoc->createElement('testsuites');

        $xmlDoc->appendChild($testSuites);

        if ($result->hasErrors()) {
            $testSuite = $xmlDoc->createElement('testsuite');
            $testSuite->appendChild(new DOMAttr('id', '0'));
            $testSuite->appendChild(new DOMAttr('package', ''));
            $testSuite->appendChild(new DOMAttr('name', 'Unmatched skipped violations'));
            $testSuite->appendChild(new DOMAttr('hostname', 'localhost'));
            $testSuite->appendChild(new DOMAttr('tests', '0'));
            $testSuite->appendChild(new DOMAttr('failures', '0'));
            $testSuite->appendChild(new DOMAttr('skipped', '0'));
            $testSuite->appendChild(new DOMAttr('errors', (string) count($result->errors())));
            $testSuite->appendChild(new DOMAttr('time', '0'));
            foreach ($result->errors() as $message) {
                $error = $xmlDoc->createElement('error');
                $error->appendChild(new DOMAttr('message', $message->toString()));
                $error->appendChild(new DOMAttr('type', 'WARNING'));
                $testSuite->appendChild($error);
            }

            $testSuites->appendChild($testSuite);
        }

        $this->addTestSuite($result, $xmlDoc, $testSuites);
    }

    private function addTestSuite(LegacyResult $result, DOMDocument $xmlDoc, DOMElement $testSuites): void
    {
        /** @var array<string, array<RuleInterface>> $layers */
        $layers = [];
        foreach ($result->rules() as $rule) {
            if ($rule instanceof CoveredRuleInterface) {
                $layers[$rule->getDependerLayer()][] = $rule;
            } elseif ($rule instanceof Uncovered) {
                $layers[$rule->getLayer()][] = $rule;
            }
        }

        $layerIndex = 0;
        foreach ($layers as $layer => $rules) {
            $violationsByLayer = array_filter($rules, static fn (RuleInterface $rule) => $rule instanceof Violation);

            $skippedViolationsByLayer = array_filter($rules, static fn (RuleInterface $rule) => $rule instanceof SkippedViolation);

            $rulesByClassName = [];
            foreach ($rules as $rule) {
                $rulesByClassName[$rule->getDependency()->getDepender()->toString()][] = $rule;
            }

            $testSuite = $xmlDoc->createElement('testsuite');
            $testSuite->appendChild(new DOMAttr('id', (string) ++$layerIndex));
            $testSuite->appendChild(new DOMAttr('package', ''));
            $testSuite->appendChild(new DOMAttr('name', $layer));
            $testSuite->appendChild(new DOMAttr('hostname', 'localhost'));
            $testSuite->appendChild(new DOMAttr('tests', (string) count($rulesByClassName)));
            $testSuite->appendChild(new DOMAttr('failures', (string) count($violationsByLayer)));
            $testSuite->appendChild(new DOMAttr('skipped', (string) count($skippedViolationsByLayer)));
            $testSuite->appendChild(new DOMAttr('errors', '0'));
            $testSuite->appendChild(new DOMAttr('time', '0'));

            $testSuites->appendChild($testSuite);

            $this->addTestCase($layer, $rulesByClassName, $xmlDoc, $testSuite);
        }
    }

    /**
     * @param array<string, RuleInterface[]> $rulesByClassName
     */
    private function addTestCase(string $layer, array $rulesByClassName, DOMDocument $xmlDoc, DOMElement $testSuite): void
    {
        foreach ($rulesByClassName as $className => $rules) {
            $testCase = $xmlDoc->createElement('testcase');
            $testCase->appendChild(new DOMAttr('name', $layer.' - '.$className));
            $testCase->appendChild(new DOMAttr('classname', $className));
            $testCase->appendChild(new DOMAttr('time', '0'));

            foreach ($rules as $rule) {
                if ($rule instanceof SkippedViolation) {
                    $this->addSkipped($xmlDoc, $testCase);
                } elseif ($rule instanceof Violation) {
                    $this->addFailure($rule, $xmlDoc, $testCase);
                } elseif ($rule instanceof Uncovered) {
                    $this->addWarning($rule, $xmlDoc, $testCase);
                }
            }

            $testSuite->appendChild($testCase);
        }
    }

    private function addFailure(Violation $violation, DOMDocument $xmlDoc, DOMElement $testCase): void
    {
        $dependency = $violation->getDependency();

        $message = sprintf(
            '%s:%d must not depend on %s (%s on %s)',
            $dependency->getDepender()->toString(),
            $dependency->getFileOccurrence()->getLine(),
            $dependency->getDependent()->toString(),
            $violation->getDependerLayer(),
            $violation->getDependentLayer()
        );

        $error = $xmlDoc->createElement('failure');
        $error->appendChild(new DOMAttr('message', $message));
        $error->appendChild(new DOMAttr('type', 'WARNING'));

        $testCase->appendChild($error);
    }

    private function addSkipped(DOMDocument $xmlDoc, DOMElement $testCase): void
    {
        $skipped = $xmlDoc->createElement('skipped');
        $testCase->appendChild($skipped);
    }

    private function addWarning(Uncovered $rule, DOMDocument $xmlDoc, DOMElement $testCase): void
    {
        $dependency = $rule->getDependency();

        $message = sprintf(
            '%s:%d has uncovered dependency on %s (%s)',
            $dependency->getDepender()->toString(),
            $dependency->getFileOccurrence()->getLine(),
            $dependency->getDependent()->toString(),
            $rule->getLayer()
        );

        $error = $xmlDoc->createElement('warning');
        $error->appendChild(new DOMAttr('message', $message));
        $error->appendChild(new DOMAttr('type', 'WARNING'));

        $testCase->appendChild($error);
    }
}
