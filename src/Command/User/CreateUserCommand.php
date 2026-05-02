<?php

namespace App\Command\User;

use App\Exception\EntityNotFoundException;
use App\Service\User\UserManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user:create',
    description: 'Create a new user with email and password',
)]
final class CreateUserCommand extends Command
{
    public function __construct(
        private readonly UserManager $userManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'User email address');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');

        try {
            $this->userManager->getByEmail($email);
            $io->error(sprintf('User with email "%s" already exists', $email));

            return Command::FAILURE;
        } catch (EntityNotFoundException) {
            // User does not exist, proceed with creation
        }

        $password = $io->askHidden('Enter password');

        if (!$password) {
            $io->error('Password cannot be empty');

            return Command::FAILURE;
        }

        $confirmedPassword = $io->askHidden('Confirm password');

        if ($password !== $confirmedPassword) {
            $io->error('Passwords do not match');

            return Command::FAILURE;
        }

        try {
            $user = $this->userManager->create($email, $password);
            $io->success(sprintf('User "%s" created successfully', $user->getUserIdentifier()));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(sprintf('Failed to create user: %s', $e->getMessage()));

            return Command::FAILURE;
        }
    }
}
