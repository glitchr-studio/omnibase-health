<?php

namespace Base\Health\Transmission;

use Base\Office\Entity\Share\Document;

/**
 * Reserved, not implemented: sending a document to a patient's Mon espace
 * santé, or to another professional, through MSSanté - the official secure
 * messaging of health. It takes an MSSanté operator and a professional's
 * CPS card, which a practice's site does not have: a practice that gets
 * them writes this interface's implementation; ResultSender would then
 * offer it beside the vault.
 */
interface MssanteTransmitterInterface
{
    /** Whether this address can be written to (a patient's "<INS>@patient.mssante.fr", a professional's box). */
    public function supports(string $address): bool;

    /**
     * @param string $from the sender's own MSSanté address
     *
     * @return string the operator's message id
     */
    public function transmit(Document $document, string $from, string $to, string $subject, string $body): string;
}
