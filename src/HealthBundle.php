<?php

namespace Base\Health;

use Base\Bundle\AbstractBaseBundle;
use Base\Traits\SingletonTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * The health regime, on omnibase/office: a practice of care (a
 * multi-professional health centre, a nursing practice, a physiotherapist,
 * a midwife), its practitioners read from the RPPS, patients and their
 * dependants, home care requests, fees, results sent through the office's
 * encrypted vault - and medical secrecy in who may read what: the patient,
 * the practitioner who wrote it, the care team unless the patient objected;
 * the secretariat files and sends, it does not read. No e-mail ever holds
 * health data, not even a document's title or an appointment's reason.
 */
class HealthBundle extends AbstractBaseBundle
{
    use SingletonTrait;

    public function __construct()
    {
        parent::__construct();
    }

    /** Modern layout: the class lives in src/, the bundle root is the package root. */
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $this->setMapping($this->getPath().'/src/Entity', 'Base\Health\Entity', 'App\Entity\Health');
        $this->setMapping($this->getPath().'/src/Repository', 'Base\Health\Repository', 'App\Repository\Health');
    }
}
