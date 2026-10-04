<?php

namespace Base\Health\Service;

use App\Entity\User;
use Base\Office\Entity\Share\Document;
use Base\Office\Enum\DocumentKind;
use Base\Office\Share\DocumentVault;
use Symfony\Component\HttpFoundation\File\File;

/**
 * A result, a prescription, a report sent to a patient: kept in the
 * office's encrypted vault, the patient told by an e-mail that says only
 * "a document awaits you" (never by an e-mail that carries it: that would
 * break medical secrecy), and, when asked, a word in the mailbox - itself
 * stored encrypted, its notice without content.
 *
 * The official channel to a patient's Mon espace santé is MSSanté: see
 * Base\Health\Transmission\MssanteTransmitterInterface, reserved.
 */
class ResultSender
{
    /** @param \Base\Mailbox\Service\Mailbox|null $mailbox wired when omnibase/mailbox is installed */
    public function __construct(
        private readonly DocumentVault $vault,
        private readonly ?object $mailbox = null,
    ) {
    }

    /**
     * @throws \Base\Office\Exception\ShareException|\Base\Office\Exception\KeyMissingException
     */
    public function send(File|string $file, User $patient, User $sender, string $title, DocumentKind $kind = DocumentKind::RESULT, ?string $message = null, ?\DateTimeInterface $expiresAt = null): Document
    {
        $document = $this->vault->deposit($file, $patient, $sender, $title, $kind, confidential: true, context: 'health:'.$kind->value, expiresAt: $expiresAt);

        $message = trim((string) $message);
        if ('' !== $message && null !== $this->mailbox) {
            // A word with it, in the mailbox: never the document's title in a subject an e-mail could quote.
            $this->mailbox->composeTo($sender, [$patient], 'Un document vous attend', $message);
        }

        return $document;
    }
}
