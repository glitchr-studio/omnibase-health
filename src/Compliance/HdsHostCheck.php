<?php

namespace Base\Health\Compliance;

use Base\Office\Compliance\ComplianceCheckInterface;
use Base\Office\Compliance\ComplianceResult;
use Base\Service\SettingBagInterface;

/** Health data are hosted by a certified host (HDS), and the legal notice says which. */
final class HdsHostCheck implements ComplianceCheckInterface
{
    public function __construct(private readonly SettingBagInterface $settings)
    {
    }

    public function check(): ComplianceResult
    {
        try {
            $hds = trim((string) $this->settings->getScalar('health.legal.hds'));
        } catch (\Throwable) {
            $hds = '';
        }

        return '' !== $hds ? ComplianceResult::ok('compliance.hds', 'health') : new ComplianceResult('compliance.hds', ComplianceResult::MISSING, 'compliance.hds_advice', 'health');
    }
}
