<?php

namespace Base\Health\DependencyInjection;

use Base\Bundle\AbstractBaseExtension;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class HealthExtension extends AbstractBaseExtension implements PrependExtensionInterface
{
    public function getConfiguration(array $config, ContainerBuilder $container): HealthConfiguration
    {
        return new HealthConfiguration();
    }

    /**
     * The office's public pages in the health regime's words: the team as
     * practitioners (profession, sector, fees, next slots), the contact form's
     * warning (no medical data: the patient space is for that).
     */
    public function prepend(ContainerBuilder $container): void
    {
        if ($container->hasExtension('office')) {
            $container->prependExtensionConfig('office', [
                'templates' => [
                    'team' => '@Health/client/team.html.twig',
                    'member' => '@Health/client/practitioner.html.twig',
                    'space_nav' => '@Health/client/space/_nav.html.twig',
                ],
                'contact' => ['notice' => '@health.contact.notice'],
                'booking' => ['space_route' => 'health_space'],
            ]);
        }
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new HealthConfiguration();
        $config = (new Processor())->processConfiguration($configuration, $configs);
        $this->setConfiguration($container, $config, $configuration->getTreeBuilder()->buildTree()->getName());

        $loader = new PhpFileLoader($container, new FileLocator(\dirname(__DIR__, 2).'/config'));
        $loader->load('services.php');

        // The mailbox's cipher, for the compliance check: a stand-in when omnibase/mailbox is not installed.
        if (class_exists(\Base\Mailbox\Crypto\MessageCipher::class)) {
            $container->setAlias('health.mailbox_cipher', \Base\Mailbox\Crypto\MessageCipher::class);
            $container->setAlias('health.mailbox', \Base\Mailbox\Service\Mailbox::class);
            $container->setAlias('health.mailbox_desks', \Base\Mailbox\Desk\Desks::class);
        } else {
            foreach (['health.mailbox_cipher', 'health.mailbox', 'health.mailbox_desks'] as $id) {
                $container->register($id, \stdClass::class);
            }
        }

        // A word with a result goes through omnibase/mailbox, when it is installed.
        if (class_exists(\Base\Mailbox\Service\Mailbox::class)) {
            $container->getDefinition(\Base\Health\Service\ResultSender::class)
                ->setArgument('$mailbox', new \Symfony\Component\DependencyInjection\Reference(\Base\Mailbox\Service\Mailbox::class, \Symfony\Component\DependencyInjection\ContainerInterface::NULL_ON_INVALID_REFERENCE));
        }
    }
}
