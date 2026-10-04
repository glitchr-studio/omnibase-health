<?php

namespace Base\Health\Compliance;

use Base\Health\Repository\FeeRepository;
use Base\Health\Repository\PractitionerRepository;
use Base\Office\Compliance\ComplianceCheckInterface;
use Base\Office\Compliance\ComplianceResult;

/** Fees are displayed: French law has every practitioner post theirs. */
final class FeesDisplayedCheck implements ComplianceCheckInterface
{
    public function __construct(private readonly PractitionerRepository $practitioners, private readonly FeeRepository $fees)
    {
    }

    public function check(): ComplianceResult
    {
        $practiceWide = \count($this->fees->findBy(['practitioner' => null])) > 0;
        $without = [];
        foreach ($this->practitioners->findAll() as $practitioner) {
            if ($practitioner->getMember()?->isActive() && !$practiceWide && [] === $this->fees->findForPractitioner($practitioner)) {
                $without[] = (string) $practitioner;
            }
        }

        return [] === $without ? ComplianceResult::ok('compliance.fees', 'health') : new ComplianceResult('compliance.fees', ComplianceResult::MISSING, 'compliance.fees_advice', 'health', ['names' => implode(', ', $without)]);
    }
}
