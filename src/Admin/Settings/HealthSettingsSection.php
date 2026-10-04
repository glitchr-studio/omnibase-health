<?php

namespace Base\Health\Admin\Settings;

use Base\Admin\Settings\SettingsSectionInterface;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Contracts\Translation\TranslatorInterface;

/** The settings' health line: which certified host (HDS) keeps the data - the legal notice prints it, the compliance widget checks it. */
#[AsTaggedItem(priority: 40)]
final class HealthSettingsSection implements SettingsSectionInterface
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function getPage(): string
    {
        return self::SETTINGS;
    }

    public function getFields(): array
    {
        return [
            'health.legal.hds' => ['required' => false, 'label' => $this->translator->trans('settings.hds', [], 'health')],
        ];
    }
}
