<?php

namespace App\Command\Attachment;

use App\Repository\AttachmentRepository;
use App\Service\Attachment\AttachmentManager;
use App\Service\Flusher;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

#[AsCommand(
    name: 'app:attachment:purge-dangling',
    description: 'Delete attachments with no ownership link older than a given interval',
)]
#[AsCronTask('0 3 * * *', arguments: 'P1D')]
final class PurgeDanglingAttachmentsCommand extends Command
{
    public function __construct(
        private readonly AttachmentRepository $attachmentRepository,
        private readonly AttachmentManager $attachmentManager,
        private readonly Flusher $flusher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'olderThan',
            InputArgument::REQUIRED,
            'Minimum age of a dangling attachment before deletion (DateInterval format, e.g. PT1H, P1D)',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $interval = new \DateInterval($input->getArgument('olderThan'));
        } catch (\Exception $e) {
            $io->error(sprintf('Invalid DateInterval: %s', $e->getMessage()));

            return Command::FAILURE;
        }

        $cutoff = new \DateTimeImmutable()->sub($interval);
        $attachments = $this->attachmentRepository->findDangling($cutoff);

        if (!$attachments) {
            $io->info('No dangling attachments found.');

            return Command::SUCCESS;
        }

        foreach ($attachments as $attachment) {
            $this->attachmentManager->delete($attachment);
        }

        $this->flusher->flush();

        $io->success(sprintf('Removed %d dangling attachment(s).', count($attachments)));

        return Command::SUCCESS;
    }
}
