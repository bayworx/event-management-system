<?php

namespace App\Command;

use App\Entity\Administrator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Create a new administrator user',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'Admin email address')
            ->addOption('name', null, InputOption::VALUE_OPTIONAL, 'Admin full name')
            ->addOption('password', null, InputOption::VALUE_OPTIONAL, 'Admin password')
            ->addOption('super-admin', null, InputOption::VALUE_NONE, 'Make this user a super administrator')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email');
        if (!$email) {
            $helper = $this->getHelper('question');
            $question = new Question('Enter admin email address: ');
            $question->setValidator(function ($value) {
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    throw new \RuntimeException('Please enter a valid email address');
                }
                return $value;
            });
            $email = $helper->ask($input, $output, $question);
        }

        // Check if admin already exists
        $existingAdmin = $this->entityManager->getRepository(Administrator::class)
            ->findOneBy(['email' => $email]);

        if ($existingAdmin) {
            $io->error('Administrator with this email already exists!');
            return Command::FAILURE;
        }

        $name = $input->getOption('name');
        if (!$name) {
            $helper = $this->getHelper('question');
            $question = new Question('Enter admin full name: ');
            $name = $helper->ask($input, $output, $question);
        }

        $password = $input->getOption('password');
        if (!$password) {
            $helper = $this->getHelper('question');
            $question = new Question('Enter admin password: ');
            $question->setHidden(true);
            $question->setHiddenFallback(false);
            $question->setValidator(function ($value) {
                if (strlen($value) < 8) {
                    throw new \RuntimeException('Password must be at least 8 characters long');
                }
                return $value;
            });
            $password = $helper->ask($input, $output, $question);
        }

        $isSuperAdmin = $input->getOption('super-admin');

        // Create admin user
        $admin = new Administrator();
        $admin->setEmail($email);
        $admin->setName($name);
        $admin->setIsSuperAdmin($isSuperAdmin);

        $hashedPassword = $this->passwordHasher->hashPassword($admin, $password);
        $admin->setPassword($hashedPassword);

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $io->success(sprintf('Administrator "%s" created successfully!', $email));
        
        if ($isSuperAdmin) {
            $io->note('This user has super administrator privileges.');
        }

        return Command::SUCCESS;
    }
}