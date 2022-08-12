<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Core\Ast\AstMap;

use Qossmic\Deptrac\Core\Ast\AstMap\ClassLike\ClassLikeToken;
use Qossmic\Deptrac\Core\Ast\AstMap\FunctionLike\FunctionLikeToken;
use Qossmic\Deptrac\Core\Ast\AstMap\Variable\SuperGlobalToken;

abstract class ReferenceBuilder
{
    /** @var DependencyToken[] */
    protected array $dependencies = [];

    /**
     * @param string[] $tokenTemplates
     */
    protected function __construct(protected array $tokenTemplates, protected string $filepath)
    {
    }

    /**
     * @return string[]
     */
    final public function getTokenTemplates(): array
    {
        return $this->tokenTemplates;
    }

    /**
     * Unqualified function and constant names inside a namespace cannot be
     * statically resolved. Inside a namespace Foo, a call to strlen() may
     * either refer to the namespaced \Foo\strlen(), or the global \strlen().
     * Because PHP-Parser does not have the necessary context to decide this,
     * such names are left unresolved.
     */
    public function unresolvedFunctionCall(string $functionName, int $occursAtLine): self
    {
        $this->dependencies[] = DependencyToken::fromType(
            FunctionLikeToken::fromFQCN($functionName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine),
            DependencyToken::UNRESOLVED_FUNCTION_CALL
        );

        return $this;
    }

    public function variable(string $classLikeName, int $occursAtLine): self
    {
        $this->dependencies[] = DependencyToken::fromType(
            ClassLikeToken::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine),
            DependencyToken::VARIABLE
        );

        return $this;
    }

    public function superglobal(string $superglobalName, int $occursAtLine): void
    {
        $this->dependencies[] = DependencyToken::fromType(
            new SuperGlobalToken($superglobalName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine),
            DependencyToken::SUPERGLOBAL_VARIABLE
        );
    }

    public function returnType(string $classLikeName, int $occursAtLine): self
    {
        $this->dependencies[] = DependencyToken::fromType(
            ClassLikeToken::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine),
            DependencyToken::RETURN_TYPE
        );

        return $this;
    }

    public function throwStatement(string $classLikeName, int $occursAtLine): self
    {
        $this->dependencies[] = DependencyToken::fromType(
            ClassLikeToken::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine),
            DependencyToken::THROW
        );

        return $this;
    }

    public function anonymousClassExtends(string $classLikeName, int $occursAtLine): void
    {
        $this->dependencies[] = DependencyToken::fromType(
            ClassLikeToken::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine),
            DependencyToken::ANONYMOUS_CLASS_EXTENDS
        );
    }

    public function anonymousClassTrait(string $classLikeName, int $occursAtLine): void
    {
        $this->dependencies[] = DependencyToken::fromType(
            ClassLikeToken::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine),
            DependencyToken::ANONYMOUS_CLASS_TRAIT
        );
    }

    public function constFetch(string $classLikeName, int $occursAtLine): void
    {
        $this->dependencies[] = DependencyToken::fromType(
            ClassLikeToken::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine),
            DependencyToken::CONST
        );
    }

    public function anonymousClassImplements(string $classLikeName, int $occursAtLine): void
    {
        $this->dependencies[] = DependencyToken::fromType(
            ClassLikeToken::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine),
            DependencyToken::ANONYMOUS_CLASS_IMPLEMENTS
        );
    }

    public function parameter(string $classLikeName, int $occursAtLine): self
    {
        $this->dependencies[] = DependencyToken::fromType(
            ClassLikeToken::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine),
            DependencyToken::PARAMETER
        );

        return $this;
    }

    public function attribute(string $classLikeName, int $occursAtLine): self
    {
        $this->dependencies[] = DependencyToken::fromType(
            ClassLikeToken::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine),
            DependencyToken::ATTRIBUTE
        );

        return $this;
    }

    public function instanceof(string $classLikeName, int $occursAtLine): self
    {
        $this->dependencies[] = DependencyToken::fromType(
            ClassLikeToken::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine),
            DependencyToken::INSTANCEOF
        );

        return $this;
    }

    public function newStatement(string $classLikeName, int $occursAtLine): self
    {
        $this->dependencies[] = DependencyToken::fromType(
            ClassLikeToken::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine),
            DependencyToken::NEW
        );

        return $this;
    }

    public function staticProperty(string $classLikeName, int $occursAtLine): self
    {
        $this->dependencies[] = DependencyToken::fromType(
            ClassLikeToken::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine),
            DependencyToken::STATIC_PROPERTY
        );

        return $this;
    }

    public function staticMethod(string $classLikeName, int $occursAtLine): self
    {
        $this->dependencies[] = DependencyToken::fromType(
            ClassLikeToken::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine),
            DependencyToken::STATIC_METHOD
        );

        return $this;
    }

    public function catchStmt(string $classLikeName, int $occursAtLine): self
    {
        $this->dependencies[] = DependencyToken::fromType(
            ClassLikeToken::fromFQCN($classLikeName),
            FileOccurrence::fromFilepath($this->filepath, $occursAtLine),
            DependencyToken::CATCH
        );

        return $this;
    }

    public function addTokenTemplate(string $tokenTemplate): void
    {
        $this->tokenTemplates[] = $tokenTemplate;
    }

    public function removeTokenTemplate(string $tokenTemplate): void
    {
        $key = array_search($tokenTemplate, $this->tokenTemplates, true);
        if (false === $key) {
            return;
        }
        unset($this->tokenTemplates[$key]);
    }
}
