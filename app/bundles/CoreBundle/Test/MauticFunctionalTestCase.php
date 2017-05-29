<?php

namespace Mautic\CoreBundle\Test;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Mautic\CoreBundle\Helper\CookieHelper;
use Mautic\CoreBundle\Templating\Helper\TranslatorHelper;
use Mautic\InstallBundle\Helper\SchemaHelper;
use MauticPlugin\MauticCrmBundle\Tests\DoctrineExtensions\TablePrefix;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Templating\DelegatingEngine;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Response;

abstract class MauticFunctionalTestCase extends WebTestCase
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Client
     */
    protected $client;

    public function setUp()
    {
        \Mautic\CoreBundle\ErrorHandler\ErrorHandler::register('prod');

        $this->client = static::createClient();
        $this->client->disableReboot();
        $this->container = $this->client->getContainer();
        $this->em        = $this->container->get('doctrine')->getManager();

        $cookieHelper = $this->getMockBuilder(CookieHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['setCookie', 'setCharset'])
            ->getMock();

        $cookieHelper->expects($this->any())
            ->method('setCookie');

        $translatorHelper = $this->getMockBuilder(TranslatorHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['setCharset', 'trans', 'getJsLang'])
            ->getMock();

        $translatorHelper->expects($this->any())
            ->method('setCharset');

        $translatorHelper->expects($this->any())
            ->method('trans');

        $translatorHelper->expects($this->any())
            ->method('getJsLang');

        $templateHelper = $this->getMockBuilder(DelegatingEngine::class)
            ->disableOriginalConstructor()
            ->setMethods(['renderResponse'])
            ->getMock();

        $templateHelper->expects($this->any())
            ->method('renderResponse')
            ->willReturnCallback(function () {
                return new Response();
            });

        $this->container->set('mautic.helper.cookie', $cookieHelper);
        $this->container->set('templating.helper.translator', $translatorHelper);
        //uncomment to develop tests faster
//        $this->container->set('templating', $templateHelper);

        if (file_exists($this->getOriginalDatabasePath())) {
            $this->createDatabaseFromFile();
        } else {
            $this->createDatabase();
            $this->applyMigrations();
            $this->installDatabaseFixtures();
            $this->backupOrginalDatabase();
        }
    }

    protected function tearDown()
    {
        $this->em->close();

        parent::tearDown();
    }

    private function createDatabase()
    {
        // fix problem with prefixes in sqlite
        $tablePrefix = new TablePrefix('prefix_');
        $this->em->getEventManager()->addEventListener(Events::loadClassMetadata, $tablePrefix);

        $dbParams = array_merge($this->container->get('doctrine')->getConnection()->getParams(), [
            'table_prefix'  => null,
            'backup_tables' => 0,
        ]);

        // create schema
        $schemaHelper = new SchemaHelper($dbParams);
        $schemaHelper->setEntityManager($this->em);

        $schemaHelper->createDatabase();
        $schemaHelper->installSchema();

        $this->em->getConnection()->close();
    }

    private function applyMigrations()
    {
        $input  = new ArgvInput(['console', 'doctrine:migrations:version', '--add', '--all', '--no-interaction']);
        $output = new BufferedOutput();

        $application = new Application($this->container->get('kernel'));
        $application->setAutoExit(false);
        $application->run($input, $output);
    }

    private function installDatabaseFixtures()
    {
        $paths  = [dirname(__DIR__).'/../InstallBundle/InstallFixtures/ORM'];
        $loader = new ContainerAwareLoader($this->container);

        foreach ($paths as $path) {
            if (is_dir($path)) {
                $loader->loadFromDirectory($path);
            }
        }

        $fixtures = $loader->getFixtures();

        if (!$fixtures) {
            throw new \InvalidArgumentException(
                sprintf('Could not find any fixtures to load in: %s', "\n\n- ".implode("\n- ", $paths))
            );
        }

        $purger = new ORMPurger($this->em);
        $purger->setPurgeMode(ORMPurger::PURGE_MODE_DELETE);
        $executor = new ORMExecutor($this->em, $purger);
        $executor->execute($fixtures, true);
    }

    private function createDatabaseFromFile()
    {
        copy($this->getOriginalDatabasePath(), $this->getDatabasePath());
    }

    private function backupOrginalDatabase()
    {
        copy($this->getDatabasePath(), $this->getOriginalDatabasePath());
    }

    private function getOriginalDatabasePath()
    {
        return $this->getDatabasePath().'.original';
    }

    private function getDatabasePath()
    {
        return $this->container->get('doctrine')->getConnection()->getParams()['path'];
    }

    /**
     * {@inheritdoc}
     */
    protected static function getKernelClass()
    {
        if (isset($_SERVER['KERNEL_DIR'])) {
            $dir = $_SERVER['KERNEL_DIR'];

            if (!is_dir($dir)) {
                $phpUnitDir = static::getPhpUnitXmlDir();
                if (is_dir("$phpUnitDir/$dir")) {
                    $dir = "$phpUnitDir/$dir";
                }
            }
        } else {
            $dir = static::getPhpUnitXmlDir();
        }

        $finder = new Finder();
        $finder->name('*TestKernel.php')->depth(0)->in($dir);
        $results = iterator_to_array($finder);
        if (!count($results)) {
            throw new \RuntimeException('Either set KERNEL_DIR in your phpunit.xml according to https://symfony.com/doc/current/book/testing.html#your-first-functional-test or override the WebTestCase::createKernel() method.');
        }

        $file  = current($results);
        $class = $file->getBasename('.php');

        require_once $file;

        return $class;
    }
}
