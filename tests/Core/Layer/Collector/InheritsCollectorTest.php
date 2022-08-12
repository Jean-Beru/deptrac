<?php

declare(strict_types=1);

namespace Tests\Qossmic\Deptrac\Core\Layer\Collector;

use PHPUnit\Framework\TestCase;
use Qossmic\Deptrac\Core\Ast\AstMap\AstMap;
use Qossmic\Deptrac\Core\Ast\AstMap\File\FileReferenceBuilder;
use Qossmic\Deptrac\Core\Layer\Collector\InheritsCollector;

final class InheritsCollectorTest extends TestCase
{
    private InheritsCollector $collector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->collector = new InheritsCollector();
    }

    public function dataProviderSatisfy(): iterable
    {
        yield [['value' => 'App\FizTrait'], true];
        yield [['value' => 'App\Bar'], true];
        yield [['value' => 'App\Baz'], true];
        yield [['value' => 'App\Foo'], true];
        yield [['value' => 'App\None'], false];
        // Legacy attribute:
        yield [['inherits' => 'App\FizTrait'], true];
        yield [['inherits' => 'App\Bar'], true];
        yield [['inherits' => 'App\Baz'], true];
        yield [['inherits' => 'App\Foo'], true];
        yield [['inherits' => 'App\None'], false];
    }

    /**
     * @dataProvider dataProviderSatisfy
     */
    public function testSatisfy(array $configuration, bool $expected): void
    {
        $fooFileReferenceBuilder = FileReferenceBuilder::create('foo.php');
        $fooFileReferenceBuilder
            ->newClassLike('App\Foo', [], false)
            ->implements('App\Bar', 2);
        $fooFileReference = $fooFileReferenceBuilder->build();

        $barFileReferenceBuilder = FileReferenceBuilder::create('bar.php');
        $barFileReferenceBuilder
            ->newClassLike('App\Bar', [], false)
            ->implements('App\Baz', 2);
        $barFileReference = $barFileReferenceBuilder->build();

        $bazFileReferenceBuilder = FileReferenceBuilder::create('baz.php');
        $bazFileReferenceBuilder->newClassLike('App\Baz', [], false);
        $bazFileReference = $bazFileReferenceBuilder->build();

        $fizTraitFileReferenceBuilder = FileReferenceBuilder::create('fiztrait.php');
        $fizTraitFileReferenceBuilder
            ->newClassLike('App\FizTrait', [], false);
        $fizTraitFileReference = $fizTraitFileReferenceBuilder->build();

        $fooBarFileReferenceBuilder = FileReferenceBuilder::create('foobar.php');
        $fooBarFileReferenceBuilder
            ->newClassLike('App\FooBar', [], false)
            ->extends('App\Foo', 2)
            ->trait('App\FizTrait', 4);
        $fooBarFileReference = $fooBarFileReferenceBuilder->build();

        $actual = $this->collector->satisfy(
            $configuration,
            $fooBarFileReference->classLikeReferences[0],
            new AstMap([$fooFileReference, $barFileReference, $bazFileReference, $fooBarFileReference, $fizTraitFileReference]),
        );

        self::assertSame($expected, $actual);
    }
}
