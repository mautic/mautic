<?php

namespace Mautic\CoreBundle\Test;

use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use InvalidArgumentException;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Mautic\CoreBundle\Test\Session\FixedMockFileSessionStorage;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

abstract class AbstractMauticTestCase extends WebTestCase
{
    protected EntityManager $em;
    protected Connection $connection;
    protected KernelBrowser $client;
    protected array $clientOptions = [];
    protected array $clientServer  = [
        'PHP_AUTH_USER' => 'admin',
        'PHP_AUTH_PW'   => 'mautic',
    ];

    protected array $configParams = [
        'api_enabled'                       => true,
        'api_enable_basic_auth'             => true,
        'create_custom_field_in_background' => false,
        'mailer_from_name'                  => 'Mautic',
    ];

    /**
     * Flag to turn off the mockServices() method.
     */
    protected bool $useMockServices = true;

    protected AbstractDatabaseTool $databaseTool;

    protected function setUp(): void
    {
        $this->setUpSymfony($this->configParams);
        $this->databaseTool = $this->getContainer()->get(DatabaseToolCollection::class)->get();
    }

    protected function setUpSymfony(array $defaultConfigOptions = []): void
    {
        putenv('MAUTIC_CONFIG_PARAMETERS='.json_encode($defaultConfigOptions));

        $this->client = static::createClient($this->clientOptions, $this->clientServer);
        $this->client->disableReboot();
        $this->client->followRedirects(true);

        $this->em         = self::$container->get('doctrine')->getManager();
        $this->connection = $this->em->getConnection();

        /** @var RouterInterface $router */
        $router = self::$container->get('router');
        $scheme = $router->getContext()->getScheme();
        $secure = 0 === strcasecmp($scheme, 'https');

        $this->client->setServerParameter('HTTPS', $secure);

        if ($this->useMockServices) {
            $this->mockServices();
        }
    }

    /**
     * Overrides \Liip\TestFixturesBundle\Test\FixturesTrait::getContainer() method to prevent from having multiple instances of container.
     */
    protected function getContainer(): ContainerInterface
    {
        return self::$container;
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

    private function mockServices(): void
    {
        self::$container->set('session', new Session(new FixedMockFileSessionStorage()));
    }

    protected function applyMigrations(): void
    {
        $input  = new ArgvInput(['console', 'doctrine:migrations:version', '--add', '--all', '--no-interaction']);
        $output = new BufferedOutput();

        $application = new Application(self::$container->get('kernel'));
        $application->setAutoExit(false);
        $application->run($input, $output);
    }

    protected function installDatabaseFixtures(array $classNames = []): void
    {
        $this->loadFixtures($classNames);
    }

    /**
     * Use when POSTing directly to forms.
     *
     * @param string $intention
     *
     * @return string
     */
    protected function getCsrfToken($intention)
    {
        return $this->client->getContainer()->get('security.csrf.token_manager')->refreshToken($intention)->getValue();
    }

    /**
     * @return string[]
     */
    protected function createAjaxHeaders(): array
    {
        return [
            'HTTP_Content-Type'     => 'application/x-www-form-urlencoded; charset=UTF-8',
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
            'HTTP_X-CSRF-Token'     => $this->getCsrfToken('mautic_ajax_post'),
        ];
    }

    /**
     * @return string Command's output
     *
     * @throws \Exception
     *
     * @deprecated use testSymfonyCommand() instead
     */
    protected function runCommand(string $name, array $params = [], Command $command = null, int $expectedStatusCode = 0): string
    {
        $params      = array_merge(['command' => $name], $params);
        $kernel      = self::$container->get('kernel');
        $application = new Application($kernel);
        $application->setAutoExit(false);
        $application->setCatchExceptions(false);

        if ($command) {
            // Register the command
            $application->add($command);
        }

        $input      = new ArrayInput($params);
        $output     = new BufferedOutput();
        $statusCode = $application->run($input, $output);
        $result     = $output->fetch();

        Assert::assertSame($expectedStatusCode, $statusCode, $result);

        return $result;
    }

    protected function loginUser(string $username): void
    {
        $user = $this->em->getRepository(User::class)
            ->findOneBy(['username' => $username]);

        if (!$user) {
            throw new InvalidArgumentException(sprintf('User with username "%s" not found.', $username));
        }

        $firewall = 'mautic';
        $session  = self::$container->get('session');
        $token    = new UsernamePasswordToken($user, null, $firewall, $user->getRoles());
        $session->set('_security_'.$firewall, serialize($token));
        $session->save();
        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }

    /**
     * @param array<mixed,mixed> $params
     */
    protected function testSymfonyCommand(string $name, array $params = [], Command $command = null): CommandTester
    {
        $kernel      = self::$container->get('kernel');
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
