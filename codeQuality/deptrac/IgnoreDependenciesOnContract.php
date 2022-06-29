<?php

declare(strict_types=1);

use Qossmic\Deptrac\Contract\Analyser\ProcessEvent;

class IgnoreDependenciesOnContract
{
    public function __invoke(ProcessEvent $event): void
    {
        if (array_key_exists('Contract', $event->getDependentLayers())) {
            $event->stopPropagation();
        }
    }
}
