<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

/*
 * Everything in src/ is a plain autowired service. The back office is loaded
 * when omnibase/admin is installed; the mailbox hooks when omnibase/mailbox is.
 */
return function (ContainerConfigurator $configurator) {
    $src = dirname(__DIR__).'/src';

    $services = $configurator->services();
    $services->defaults()
        ->autowire(true)
        ->autoconfigure(true)
        ->public(false);

    $services->load('Base\\Health\\', $src.'/')
        ->exclude([
            $src.'/DependencyInjection/',
            $src.'/Entity/',
            $src.'/Enum/',
            $src.'/Exception/',
            $src.'/Model/',
            $src.'/Form/Model/',
            $src.'/Controller/Admin/',
            $src.'/Admin/',
            $src.'/Mailbox/',
            $src.'/Transmission/',
            $src.'/HealthBundle.php',
        ]);

    $services->load('Base\\Health\\Controller\\Client\\', $src.'/Controller/Client/')
        ->tag('controller.service_arguments');

    if (class_exists('Base\\Admin\\Controller\\AbstractCrudController')) {
        $services->load('Base\\Health\\Controller\\Admin\\', $src.'/Controller/Admin/')
            ->tag('controller.service_arguments');
        $services->load('Base\\Health\\Admin\\', $src.'/Admin/');
    }

    if (interface_exists('Base\\Mailbox\\Recipient\\RecipientProviderInterface')) {
        $services->load('Base\\Health\\Mailbox\\', $src.'/Mailbox/');
    }
};
