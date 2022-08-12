<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Core\Ast\AstMap\ClassLike;

use Qossmic\Deptrac\Core\Ast\AstMap\AstInherit;
use Qossmic\Deptrac\Core\Ast\AstMap\FileOccurrence;
use Qossmic\Deptrac\Core\Ast\AstMap\ReferenceBuilder;

final class ClassLikeReferenceBuilder extends ReferenceBuilder
{
    /** @var AstInherit[] */
    private array $inherits = [];

    /**
     * @param string[] $tokenTemplates
     */
    protected function __construct(
        array $tokenTemplates,
        string $filepath,
        private readonly ClassLikeToken $classLikeToken,
        private readonly ClassLikeType $classLikeType,
        private readonly bool $isInternal
    ) {
        parent::__construct($tokenTemplates, $filepath);
    }

    /**
     * @param string[] $classTemplates
     */
    public static function createClassLike(string $filepath, string $classLikeName, array $classTemplates, bool $isInternal): self
    {
        return new self($classTemplates, $filepath, ClassLikeToken::fromFQCN($classLikeName), ClassLikeType::classLike(), $isInternal);
    }

    /**
     * @param string[] $classTemplates
     */
    public static function createClass(string $filepath, string $classLikeName, array $classTemplates, bool $isInternal): self
    {
        return new self($classTemplates, $filepath, ClassLikeToken::fromFQCN($classLikeName), ClassLikeType::class(), $isInternal);
    }

    /**
     * @param string[] $classTemplates
     */
    public static function createTrait(string $filepath, string $classLikeName, array $classTemplates, bool $isInternal): self
    {
        return new self($classTemplates, $filepath, ClassLikeToken::fromFQCN($classLikeName), ClassLikeType::trait(), $isInternal);
    }

    /**
     * @param string[] $classTemplates
     */
    public static function createInterface(string $filepath, string $classLikeName, array $classTemplates, bool $isInternal): self
    {
        return new self($classTemplates, $filepath, ClassLikeToken::fromFQCN($classLikeName), ClassLikeType::interface(), $isInternal);
    }

    /** @internal */
    public function build(): ClassLikeReference
    {
        return new ClassLikeReference(
            $this->classLikeToken,
            $this->classLikeType,
            $this->inherits,
            $this->dependencies,
            $this->isInternal
        );
    }

    public function extends(string $classLikeName, int $occursAtLine): self
    {
        $this->inherits[] = AstInherit::newExtends(
            ClassLikeToken::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine)
        );

        return $this;
    }

    public function implements(string $classLikeName, int $occursAtLine): self
    {
        $this->inherits[] = AstInherit::newImplements(
            ClassLikeToken::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine)
        );

        return $this;
    }

    public function trait(string $classLikeName, int $occursAtLine): self
    {
        $this->inherits[] = AstInherit::newTraitUse(
            ClassLikeToken::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine)
        );

        return $this;
    }
}
