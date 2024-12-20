<?php

namespace Mautic\CoreBundle\Test;

use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Mautic\EmailBundle\Mailer\Message\MauticMessage;
use Mautic\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mime\RawMessage;
use Symfony\Component\Routing\Router;

abstract class AbstractMauticTestCase extends WebTestCase
{
    protected EntityManager $em;

    protected Connection $connection;

    protected KernelBrowser $client;

    protected Router $router;

    protected array $clientOptions = [];

    // Credentials for API authentication
    protected array $clientServer  = [
        'PHP_AUTH_USER' => 'admin',
        'PHP_AUTH_PW'   => 'Maut1cR0cks!',
    ];

    protected array $configParams = [
        'api_enabled'                       => true,
        'api_enable_basic_auth'             => true,
        'create_custom_field_in_background' => false,
        'site_url'                          => 'https://localhost',
        'mailer_dsn'                        => 'null://null',
        'messenger_dsn_email'               => 'in-memory://default',
        'messenger_dsn_hit'                 => 'sync://',
        'messenger_dsn_failed'              => 'in-memory://default',
    ];

    protected bool $authenticateApi = false;

    protected AbstractDatabaseTool $databaseTool;

    /**
     * Overloading the method from MailerAssertionsTrait to get better typehint.
     *
     * @return MauticMessage[]
     */
    public static function getMailerMessages(string $transport = null): array
    {
        $messages = parent::getMailerMessages($transport);

        return array_map(function (RawMessage $message): MauticMessage {
            \assert($message instanceof MauticMessage);

            return $message;
        }, $messages);
    }

    /**
     * Overloading the method from MailerAssertionsTrait to get better typehint.
     */
    public static function getMailerMessage(int $index = 0, string $transport = null): ?MauticMessage
    {
        return self::getMailerMessages($transport)[$index] ?? null;
    }

    /**
     * @return MauticMessage[]
     */
    public static function getMailerMessagesByToAddress(string $toAddress, string $transport = null): array
    {
        return array_values(array_filter(self::getMailerMessages($transport), fn (MauticMessage $mauticMessage) => $mauticMessage->getTo()[0]->getAddress() === $toAddress));
    }

    protected function setUp(): void
    {
        $this->setUpSymfony($this->configParams);
        $this->databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
    }

    protected function setUpSymfony(array $defaultConfigOptions = []): void
    {
        putenv('MAUTIC_CONFIG_PARAMETERS='.json_encode($defaultConfigOptions));
        EnvLoader::load();

        self::ensureKernelShutdown();
        $this->client = static::createClient($this->clientOptions, $this->authenticateApi ? $this->clientServer : []);
        $this->client->disableReboot();
        $this->client->followRedirects(true);

        $this->em = static::getContainer()->get('doctrine')->getManager();
        \assert($this->em instanceof EntityManagerInterface);
        $this->connection = $this->em->getConnection();
        $this->router     = static::getContainer()->get('router');
        $scheme           = $this->router->getContext()->getScheme();
        $secure           = 0 === strcasecmp($scheme, 'https');

        $this->client->setServerParameter('HTTPS', (string) $secure);
    }

    public function loginUser(User $user): void
    {
        $this->client->loginUser($user, 'mautic');
    }

    protected function logoutUser(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/logout');
    }

    /**
     * Make `$append = true` default so we can avoid unnecessary purges.
     */
    protected function loadFixtures(array $classNames = [], bool $append = true): ?AbstractExecutor
    {
        return $this->databaseTool->loadFixtures($classNames, $append);
    }

    /**
     * Make `$append = true` default so we can avoid unnecessary purges.
     */
    protected function loadFixtureFiles(array $paths = [], bool $append = true): array
    {
        return $this->databaseTool->loadAliceFixture($paths, $append);
    }

    protected function applyMigrations(): void
    {
        $input  = new ArgvInput(['console', 'doctrine:migrations:version', '--add', '--all', '--no-interaction']);
        $output = new BufferedOutput();

        $application = new Application(static::getContainer()->get('kernel'));
        $application->setAutoExit(false);
        $application->run($input, $output);
    }

    protected function installDatabaseFixtures(array $classNames = []): void
    {
        $this->loadFixtures($classNames);
    }

    public function setCsrfHeader(string $intention = 'mautic_ajax_post'): void
    {
        $this->client->setServerParameter('HTTP_X-CSRF-Token', $this->getCsrfToken($intention));
    }

    /**
     * Use when POSTing directly to forms.
     */
    protected function getCsrfToken(string $intention): string
    {
        return $this->client->getContainer()->get('security.csrf.token_manager')->refreshToken($intention)->getValue();
    }

    /**
     * @param array<mixed,mixed> $params
     */
    protected function testSymfonyCommand(string $name, array $params = [], Command $command = null): CommandTester
    {
        $kernel      = static::getContainer()->get('kernel');
        $application = new Application($kernel);

        if ($command) {
            // Register the command
            $application->add($command);
        }

        $command       = $application->find($name);
        $commandTester = new CommandTester($command);
        $commandTester->execute($params);

        return $commandTester;
    }
}
