<?php

declare(strict_types=1);

use Qossmic\Deptrac\Contract\Analyser\ProcessEvent;

class IgnoreDependenciesOnShouldNotHappenException
{
    public function __invoke(ProcessEvent $event): void
    {
        if("Qossmic\Deptrac\Supportive\ShouldNotHappenException" === $event->getDependentReference()->getToken()->toString()) {
            $event->stopPropagation();
        }
    }
}
