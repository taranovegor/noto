<?php

namespace App\Command\Stash;

use App\Repository\StashRepository;
use App\Service\Flusher;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

#[AsCommand(
    name: 'app:stash:prune',
    description: 'Remove expired, unpinned stashes past a given grace interval',
)]
#[AsCronTask('0 * * * *', arguments: 'P1D')]
final class PruneStashCommand extends Command
{
    public function __construct(
        private readonly StashRepository $stashRepository,
        private readonly Flusher $flusher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'grace',
            InputArgument::REQUIRED,
            'Grace interval after expiration before deletion (DateInterval format, e.g. P1D, PT6H)',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $grace = new \DateInterval($input->getArgument('grace'));
        } catch (\Exception $e) {
            $io->error(sprintf('Invalid DateInterval: %s', $e->getMessage()));

            return Command::FAILURE;
        }

        $cutoff = new \DateTimeImmutable()->sub($grace);

        $expired = $this->stashRepository->findExpired($cutoff);

        if (!$expired) {
            $io->info('No expired stashes to remove.');

            return Command::SUCCESS;
        }

        foreach ($expired as $stash) {
            $this->stashRepository->remove($stash);
        }

        $this->flusher->flush();

        $io->success(sprintf('Removed %d expired stash(es).', count($expired)));

        return Command::SUCCESS;
    }
}
