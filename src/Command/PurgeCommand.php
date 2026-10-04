<?php

namespace Base\Health\Command;

use Base\Health\Repository\HomeCareRequestRepository;
use Base\Office\Repository\Booking\AppointmentRepository;
use Base\Office\Repository\ContactRequestRepository;
use Base\Office\Repository\Share\AccessLogRepository;
use Base\Office\Repository\Share\DocumentRepository;
use Base\Office\Share\DocumentVault;
use Base\Office\Visio\Rooms;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * What has outlived its retention goes (health.retention, in months; the
 * contact requests after office.contact.retention_months): past
 * appointments, decided home care requests, the access log, documents
 * expired or withdrawn - their files destroyed -, the video rooms'
 * handshakes. From the cron container, each night. --dry-run counts only.
 */
#[AsCommand(name: 'health:purge', description: 'Remove what has outlived its retention: appointments, home care requests, access logs, expired documents')]
class PurgeCommand extends Command
{
    public function __construct(
        private readonly AppointmentRepository $appointments,
        private readonly HomeCareRequestRepository $homeCare,
        private readonly AccessLogRepository $logs,
        private readonly DocumentRepository $documents,
        private readonly ContactRequestRepository $contacts,
        private readonly DocumentVault $vault,
        private readonly Rooms $rooms,
        #[Autowire('%health.retention%')] private readonly array $retention,
        #[Autowire('%office.contact.retention_months%')] private readonly int $contactMonths = 12,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Say what would go, remove nothing');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $before = static fn (int $months) => new \DateTimeImmutable(sprintf('-%d months', $months));
        $gone = $this->documents->findGoneBefore($before((int) $this->retention['documents_after_expiry']));

        if ($input->getOption('dry-run')) {
            $output->writeln(sprintf('%d document(s) expired or withdrawn would be destroyed; appointments ended before %s, home care requests decided before %s, access logs before %s and contact requests before %s would be removed.', \count($gone), $before((int) $this->retention['appointments'])->format('Y-m-d'), $before((int) $this->retention['home_care'])->format('Y-m-d'), $before((int) $this->retention['access_logs'])->format('Y-m-d'), $before($this->contactMonths)->format('Y-m-d')));

            return Command::SUCCESS;
        }

        foreach ($gone as $document) {
            $this->vault->destroy($document);
        }
        $output->writeln(sprintf(
            '%d document(s) destroyed, %d appointment(s), %d home care request(s), %d access log line(s), %d contact request(s) removed, %d signal(s) purged.',
            \count($gone),
            $this->appointments->purgeEndedBefore($before((int) $this->retention['appointments'])),
            $this->homeCare->purgeDecidedBefore($before((int) $this->retention['home_care'])),
            $this->logs->purgeBefore($before((int) $this->retention['access_logs'])),
            $this->contacts->purgeBefore($before($this->contactMonths)),
            $this->rooms->purge(),
        ));

        return Command::SUCCESS;
    }
}
