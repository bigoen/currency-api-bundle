<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    // Defaults
    $services
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->private();

    // Global
    $services
        ->load('Bigoen\\CurrencyApiBundle\\', '../src/')
        ->exclude('../src/{DependencyInjection,Entity}');
};
