<?php

namespace App\Command\Extraction;

use App\Enum\Extraction\FragmentStatus;
use App\Repository\ExtractionRepository;
use App\Service\Extraction\ExtractionManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

#[AsCommand(
    name: 'app:extraction:check-stale',
    description: 'Mark processing extractions as failed if they have been stuck longer than a given interval',
)]
#[AsCronTask('*/5 * * * *', arguments: 'PT10M')]
final class CheckStaleExtractionsCommand extends Command
{
    public function __construct(
        private readonly ExtractionRepository $repository,
        private readonly ExtractionManager $extractionManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'staleTimeout',
            InputArgument::REQUIRED,
            'Maximum allowed time in processing state (DateInterval format, e.g. PT10M, PT1H)',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $interval = new \DateInterval($input->getArgument('staleTimeout'));
        } catch (\Exception $e) {
            $io->error(sprintf('Invalid DateInterval: %s', $e->getMessage()));

            return Command::FAILURE;
        }

        $since = new \DateTimeImmutable()->sub($interval);
        $stale = $this->repository->findStaleProcessing($since);

        if (!$stale) {
            $io->info('No stale extractions found.');

            return Command::SUCCESS;
        }

        $marked = 0;

        foreach ($stale as $extraction) {
            $pending = 0;
            $failed = 0;
            $done = 0;

            foreach ($extraction->getFragments() as $fragment) {
                match ($fragment->status) {
                    FragmentStatus::Pending => $pending++,
                    FragmentStatus::Failed => $failed++,
                    FragmentStatus::Done => $done++,
                };
            }

            $errorMessage = sprintf(
                'Extraction timed out after %s: %d done, %d failed, %d pending.',
                $this->formatInterval($interval),
                $done,
                $failed,
                $pending,
            );

            $this->extractionManager->markFailed($extraction, $errorMessage);

            $io->writeln(sprintf(
                '  <fg=red>FAILED</> <comment>%s</comment> — %d done, %d failed, %d pending',
                $extraction->id,
                $done,
                $failed,
                $pending,
            ));

            ++$marked;
        }

        $io->success(sprintf('Marked %d stale extraction(s) as failed.', $marked));

        return Command::SUCCESS;
    }

    private function formatInterval(\DateInterval $interval): string
    {
        if ($interval->h > 0) {
            return sprintf('%dh %dm', $interval->h, $interval->i);
        }

        return sprintf('%dm', $interval->i);
    }
}
