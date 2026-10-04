<?php

namespace Base\Health\Compliance;

use Base\Office\Compliance\ComplianceCheckInterface;
use Base\Office\Compliance\ComplianceResult;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/** Messages are stored encrypted, and there is a key to do it with. */
final class MailboxKeyCheck implements ComplianceCheckInterface
{
    public function __construct(#[Autowire(service: 'health.mailbox_cipher')] private readonly ?object $cipher = null)
    {
    }

    public function check(): ComplianceResult
    {
        if (null === $this->cipher || !method_exists($this->cipher, 'isEnabled')) {
            return ComplianceResult::ok('compliance.mailbox_key', 'health');
        }
        if (!$this->cipher->isEnabled()) {
            return new ComplianceResult('compliance.mailbox_key', ComplianceResult::MISSING, 'compliance.mailbox_encrypt_advice', 'health');
        }

        return $this->cipher->isReady() ? ComplianceResult::ok('compliance.mailbox_key', 'health') : new ComplianceResult('compliance.mailbox_key', ComplianceResult::MISSING, 'compliance.mailbox_key_advice', 'health');
    }
}
