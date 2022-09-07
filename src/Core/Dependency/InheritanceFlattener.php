<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Core\Dependency;

use Qossmic\Deptrac\Core\Ast\AstMap\AstMap;

class InheritanceFlattener
{
    public function flattenDependencies(AstMap $astMap, DependencyList $dependencyList): void
    {
        foreach ($astMap->getClassLikeReferences() as $classReference) {
            $classLikeName = $classReference->getToken();
            foreach ($astMap->getClassInherits($classLikeName) as $inherit) {
                foreach ($dependencyList->getDependenciesByClass($inherit->classLikeName) as $dep) {
                    $dependencyList->addInheritDependency(
                        new InheritDependency(
                            $classLikeName, $dep->getDependent(), $dep, $inherit
                        )
                    );
                }
            }
        }
    }
}
