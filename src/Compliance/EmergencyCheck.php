<?php

namespace Base\Health\Compliance;

use Base\Health\Repository\PracticeRepository;
use Base\Office\Compliance\ComplianceCheckInterface;
use Base\Office\Compliance\ComplianceResult;

/** The emergencies page says what to do out of hours. */
final class EmergencyCheck implements ComplianceCheckInterface
{
    public function __construct(private readonly PracticeRepository $practices)
    {
    }

    public function check(): ComplianceResult
    {
        $practice = $this->practices->findMain();

        return null !== $practice && null !== $practice->getOutOfHours() ? ComplianceResult::ok('compliance.emergency', 'health') : new ComplianceResult('compliance.emergency', ComplianceResult::WARNING, 'compliance.emergency_advice', 'health');
    }
}
