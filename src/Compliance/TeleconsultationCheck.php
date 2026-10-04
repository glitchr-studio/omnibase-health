<?php

namespace Base\Health\Compliance;

use Base\Office\Compliance\ComplianceCheckInterface;
use Base\Office\Compliance\ComplianceResult;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/** The built-in video is not listed by the ANS: no reimbursable teleconsultation through it. */
final class TeleconsultationCheck implements ComplianceCheckInterface
{
    public function __construct(#[Autowire('%health.teleconsultation_referenced%')] private readonly bool $referenced = false, #[Autowire('%office.visio.enabled%')] private readonly bool $visio = true)
    {
    }

    public function check(): ComplianceResult
    {
        return $this->referenced || !$this->visio ? ComplianceResult::ok('compliance.teleconsultation', 'health') : new ComplianceResult('compliance.teleconsultation', ComplianceResult::WARNING, 'compliance.teleconsultation_advice', 'health');
    }
}
