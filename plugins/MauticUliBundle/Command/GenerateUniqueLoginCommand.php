<?php

namespace MauticPlugin\MauticUliBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\UserBundle\Entity\UserRepository;
use MauticPlugin\MauticUliBundle\Entity\UniqueLogin;
use MauticPlugin\MauticUliBundle\Entity\UniqueLoginRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

#[AsCommand(
    name: 'mautic:uli',
    description: 'Generate a unique login link for a user'
)]
class GenerateUniqueLoginCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $logger,
        private string $siteUrl = ''
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('mautic:uli')
            ->setDescription('Generate a unique login link for a user')
            ->addArgument(
                'user_id',
                InputArgument::REQUIRED,
                'The numeric user ID to generate a login link for'
            )
            ->setHelp('This command generates a one-time login link for a specified user ID that expires in 24 hours.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $userId = (int) $input->getArgument('user_id');

        if ($userId <= 0) {
            $output->writeln('<error>User ID must be a positive integer.</error>');
            return Command::FAILURE;
        }

        // Check if user exists
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(\Mautic\UserBundle\Entity\User::class);
        $user = $userRepository->find($userId);

        if (!$user) {
            $output->writeln("<error>User with ID {$userId} not found.</error>");
            return Command::FAILURE;
        }


        try {
            // Generate secure hash
            $hash = bin2hex(random_bytes(32));

            // Create ULI entry
            $uniqueLogin = new UniqueLogin();
            $uniqueLogin->setHash($hash)
                ->setUserId($userId)
                ->setTtl((new \DateTime())->add(new \DateInterval('P1D'))) // 24 hours from now
                ->setDateCreated(new \DateTime());

            $this->entityManager->persist($uniqueLogin);
            $this->entityManager->flush();

            // Generate URL
            $this->urlGenerator->getContext()->setScheme('https'); // Ensure HTTPS
            $url = $this->urlGenerator->generate('mautic_uli_login', ['hash' => $hash], UrlGeneratorInterface::ABSOLUTE_URL);

            $output->writeln('<info>Unique login link generated successfully!</info>');
            $output->writeln('<info>User:</info> ' . $user->getName() . ' (' . $user->getUsername() . ')');
            $output->writeln('<info>Expires:</info> ' . $uniqueLogin->getTtl()->format('Y-m-d H:i:s T'));
            $output->writeln('<info>URL:</info> ' . $url);

            // Log the generation
            $this->logger->info('ULI generated', [
                'user_id' => $userId,
                'username' => $user->getUsername(),
                'hash' => $hash,
                'ttl' => $uniqueLogin->getTtl()->format('Y-m-d H:i:s T')
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $output->writeln('<error>Failed to generate unique login link: ' . $e->getMessage() . '</error>');
            $this->logger->error('ULI generation failed', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return Command::FAILURE;
        }
    }
}