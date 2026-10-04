<?php

namespace Base\Health\Admin\Settings;

use Base\Admin\Settings\SettingsSectionInterface;
use Base\Health\Service\PractitionerRegistry;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The API keys page's health line: the Annuaire Santé's key (the ANS's
 * Gravitee portal), read at each "Lire l'Annuaire Santé". FranceConnect's,
 * Pro Santé Connect's and TURN's are not typed here: the first two are read
 * when the container is built, the third is shared with the coturn server -
 * they live in the environment and the secrets vault.
 */
#[AsTaggedItem(priority: 40)]
final class HealthKeySection implements SettingsSectionInterface
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function getPage(): string
    {
        return self::API_KEYS;
    }

    public function getFields(): array
    {
        return [
            PractitionerRegistry::KEY_SETTING => ['required' => false, 'label' => $this->translator->trans('settings.annuaire_sante_key', [], 'health')],
        ];
    }
}
