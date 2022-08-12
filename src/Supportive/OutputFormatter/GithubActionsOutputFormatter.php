<?php

namespace Qossmic\Deptrac\Supportive\OutputFormatter;

use Qossmic\Deptrac\Contract\OutputFormatter\OutputFormatterInput;
use Qossmic\Deptrac\Contract\OutputFormatter\OutputFormatterInterface;
use Qossmic\Deptrac\Contract\OutputFormatter\OutputInterface;
use Qossmic\Deptrac\Contract\Result\LegacyResult;
use Qossmic\Deptrac\Contract\Result\RuleInterface;
use Qossmic\Deptrac\Contract\Result\SkippedViolation;
use Qossmic\Deptrac\Contract\Result\Violation;
use Qossmic\Deptrac\Core\Dependency\InheritDependency;

final class GithubActionsOutputFormatter implements OutputFormatterInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getName(): string
    {
        return 'github-actions';
    }

    /**
     * {@inheritdoc}
     */
    public function finish(LegacyResult $result, OutputInterface $output, OutputFormatterInput $outputFormatterInput): void
    {
        foreach ($result->rules() as $rule) {
            if (!$rule instanceof Violation && !$rule instanceof SkippedViolation) {
                continue;
            }
            if ($rule instanceof SkippedViolation && !$outputFormatterInput->getReportSkipped()) {
                continue;
            }

            $dependency = $rule->getDependency();

            $message = sprintf(
                '%s%s must not depend on %s (%s on %s)',
                $rule instanceof SkippedViolation ? '[SKIPPED] ' : '',
                $dependency->getDepender()->toString(),
                $dependency->getDependent()->toString(),
                $rule->getDependerLayer(),
                $rule->getDependentLayer()
            );

            if ($dependency instanceof InheritDependency) {
                $message .= '%0A'.$this->inheritPathMessage($dependency);
            }

            $output->writeLineFormatted(sprintf(
                '::%s file=%s,line=%s::%s',
                $this->determineLogLevel($rule),
                $dependency->getFileOccurrence()->getFilepath(),
                $dependency->getFileOccurrence()->getLine(),
                $message
            ));
        }

        if ($outputFormatterInput->getReportUncovered() && $result->hasUncovered()) {
            $this->printUncovered($result, $output, $outputFormatterInput->getFailOnUncovered());
        }

        if ($result->hasErrors()) {
            $this->printErrors($result, $output);
        }

        if ($result->hasWarnings()) {
            $this->printWarnings($result, $output);
        }
    }

    private function determineLogLevel(RuleInterface $rule): string
    {
        return match ($rule::class) {
            Violation::class => 'error',
            SkippedViolation::class => 'warning',
            default => 'debug',
        };
    }

    private function printUncovered(LegacyResult $result, OutputInterface $output, bool $reportAsError): void
    {
        foreach ($result->uncovered() as $u) {
            $dependency = $u->getDependency();
            $output->writeLineFormatted(
                sprintf(
                    '::%s file=%s,line=%s::%s has uncovered dependency on %s (%s)',
                    $reportAsError ? 'error' : 'warning',
                    $dependency->getFileOccurrence()->getFilepath(),
                    $dependency->getFileOccurrence()->getLine(),
                    $dependency->getDepender()->toString(),
                    $dependency->getDependent()->toString(),
                    $u->getLayer()
                )
            );
        }
    }

    private function inheritPathMessage(InheritDependency $dependency): string
    {
        $buffer = [];
        $astInherit = $dependency->getInheritPath();
        foreach ($astInherit->getPath() as $p) {
            array_unshift($buffer, sprintf('%s::%d', $p->getClassLikeName()->toString(), $p->getFileOccurrence()->getLine()));
        }

        $buffer[] = sprintf('%s::%d', $astInherit->getClassLikeName()->toString(), $astInherit->getFileOccurrence()->getLine());
        $buffer[] = sprintf(
            '%s::%d',
            $dependency->getOriginalDependency()->getDependent()->toString(),
            $dependency->getOriginalDependency()->getFileOccurrence()->getLine()
        );

        return implode(' ->%0A', $buffer);
    }

    private function printErrors(LegacyResult $result, OutputInterface $output): void
    {
        foreach ($result->errors() as $error) {
            $output->writeLineFormatted('::error ::'.$error->toString());
        }
    }

    private function printWarnings(LegacyResult $result, OutputInterface $output): void
    {
        foreach ($result->warnings() as $error) {
            $output->writeLineFormatted('::warning ::'.$error->toString());
        }
    }
}
