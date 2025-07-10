<?php

declare(strict_types=1);

namespace Mautic\InstallBundle\Tests\Install;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Mautic\CoreBundle\Configurator\Configurator;
use Mautic\CoreBundle\Configurator\Step\StepInterface;
use Mautic\CoreBundle\Doctrine\Loader\FixturesLoaderInterface;
use Mautic\CoreBundle\Helper\CacheHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\InstallBundle\Install\InstallService;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class InstallServiceTest extends \PHPUnit\Framework\TestCase
{
    private MockObject $configurator;

    private MockObject $cacheHelper;

    private MockObject $pathsHelper;

    /**
     * @var EntityManager&MockObject
     */
    private MockObject $entityManager;

    private MockObject $translator;

    private MockObject $kernel;

    private MockObject $validator;

    private UserPasswordHasher $hasher;

    /**
     * @var MockObject&FixturesLoaderInterface
     */
    private MockObject $fixtureLoader;

    private InstallService $installer;

    public function setUp(): void
    {
        parent::setUp();

        $this->configurator         = $this->createMock(Configurator::class);
        $this->cacheHelper          = $this->createMock(CacheHelper::class);
        $this->pathsHelper          = $this->createMock(PathsHelper::class);
        $this->entityManager        = $this->createMock(EntityManager::class);
        $this->translator           = $this->createMock(TranslatorInterface::class);
        $this->kernel               = $this->createMock(KernelInterface::class);
        $this->validator            = $this->createMock(ValidatorInterface::class);
        $this->hasher               = $this->createMock(UserPasswordHasher::class);
        $this->fixtureLoader        = $this->createMock(FixturesLoaderInterface::class);

        $this->installer = new InstallService(
            $this->configurator,
            $this->cacheHelper,
            $this->pathsHelper,
            $this->entityManager,
            $this->translator,
            $this->kernel,
            $this->validator,
            $this->hasher,
            $this->fixtureLoader
        );
    }

    public function testCheckIfInstalledWhenNoLocalConfig(): void
    {
        $this->pathsHelper->expects($this->once())
            ->method('getSystemPath')
            ->with('root', false)
            ->willReturn(
                __DIR__.'/../../../../../',
            );

        $this->assertFalse($this->installer->checkIfInstalled());
    }

    public function testGetStepWhenNoLocalConfig(): void
    {
        $this->pathsHelper->expects($this->once())
            ->method('getSystemPath')
            ->with('root', false)
            ->willReturn(
                __DIR__.'/../../../../../',
            );

        $this->configurator->expects($this->exactly(2))
            ->method('getParameters')
            ->willReturn(
                []
            );

        $index = 0;
        $step  = $this->createMock(StepInterface::class);

        $this->configurator->expects($this->once())
            ->method('getStep')
            ->with($index)
            ->willReturn([$step]);

        $this->assertEquals($step, $this->installer->getStep($index));
    }

    public function testGetStepWhenDbDriverSet(): void
    {
        $this->pathsHelper->expects($this->once())
            ->method('getSystemPath')
            ->with('root', false)
            ->willReturn(
                __DIR__.'/../../../../../',
            );

        $this->configurator->expects($this->exactly(2))
            ->method('getParameters')
            ->willReturn(
                ['db_driver' => 'test']
            );

        $index = 0;
        $step  = $this->createMock(StepInterface::class);

        $this->configurator->expects($this->once())
            ->method('getStep')
            ->with($index)
            ->willReturn([$step]);

        $this->assertEquals($step, $this->installer->getStep($index));
    }

    public function testCheckRequirements(): void
    {
        $step     = $this->createMock(StepInterface::class);
        $messages = ['dummy' => 'test'];

        $step->expects($this->once())
            ->method('checkRequirements')
            ->willReturn($messages);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('test', [], null, null)
            ->willReturn('test');

        $this->assertEquals($messages, $this->installer->checkRequirements($step));
    }

    public function testCheckOptionalSettings(): void
    {
        $step     = $this->createMock(StepInterface::class);
        $messages = ['dummy' => 'test'];

        $step->expects($this->once())
            ->method('checkOptionalSettings')
            ->willReturn($messages);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('test', [], null, null)
            ->willReturn('test');

        $this->assertEquals($messages, $this->installer->checkOptionalSettings($step));
    }

    public function testSaveConfigurationWhenNoCacheClear(): void
    {
        $params     = [];
        $step       = $this->createMock(StepInterface::class);
        $clearCache = false;

        $messages = [];

        $step->expects($this->once())
            ->method('update')
            ->with($step)
            ->willReturn($params);

        $this->configurator->expects($this->once())
            ->method('write');

        $this->configurator->expects($this->once())
            ->method('mergeParameters');

        $this->assertEquals($messages, $this->installer->saveConfiguration($params, $step, $clearCache));
    }

    public function testSaveConfigurationWhenCacheClear(): void
    {
        $params     = [];
        $step       = $this->createMock(StepInterface::class);
        $clearCache = true;

        $messages = [];

        $step->expects($this->once())
            ->method('update')
            ->with($step)
            ->willReturn($params);

        $this->configurator->expects($this->once())
            ->method('mergeParameters');

        $this->configurator->expects($this->once())
            ->method('write');

        $this->cacheHelper->expects($this->once())
            ->method('refreshConfig');

        $this->assertEquals($messages, $this->installer->saveConfiguration($params, $step, $clearCache));
    }

    public function testValidateDatabaseParamsWhenNoRequired(): void
    {
        $dbParams = [];
        $messages = [
            'driver' => null,
            'host'   => null,
            'port'   => null,
            'name'   => null,
            'user'   => null,
        ];

        $this->assertEquals($messages, $this->installer->validateDatabaseParams($dbParams));
    }

    public function testValidateDatabaseParamsWhenPortNotValid(): void
    {
        $dbParams = [
            'driver' => 'pdo_mysql',
            'host'   => 'localhost',
            'port'   => '-1',
            'name'   => 'mautic',
            'user'   => 'mautic',
        ];
        $messages = [
            'port' => null,
        ];

        $this->assertEquals($messages, $this->installer->validateDatabaseParams($dbParams));
    }

    public function testValidateDatabaseParamsWhenAllValid(): void
    {
        $dbParams = [
            'driver' => 'pdo_mysql',
            'host'   => 'localhost',
            'port'   => '3306',
            'name'   => 'mautic',
            'user'   => 'mautic',
        ];

        $this->assertEquals([], $this->installer->validateDatabaseParams($dbParams));
    }

    public function testValidateDatabaseParamsWhenDriverNotValid(): void
    {
        $dbParams = [
            'driver' => 'pdo_sqlite',
            'host'   => 'localhost',
            'port'   => '3306',
            'name'   => 'mautic',
            'user'   => 'mautic',
        ];
        $messages = [
            'driver' => null,
        ];

        $this->assertEquals($messages, $this->installer->validateDatabaseParams($dbParams));
    }

    /**
     * When an exception is raised while creating a database, there must be an array returned.
     */
    public function testCreateDatabaseStepWithErrors(): void
    {
        $dbParams = [
            'driver'       => 'pdo_mysql',
            'host'         => 'localhost',
            'port'         => '3306',
            'name'         => 'mautic',
            'user'         => 'mautic',
            'table_prefix' => 'mautic_',
        ];

        $step = $this->createMock(StepInterface::class);
        $this->assertEquals(['error' => null], $this->installer->createDatabaseStep($step, $dbParams));
    }

    /**
     * When an exception is raised while creating the schema, there must be an array returned.
     */
    public function testCreateSchemaStepWithErrors(): void
    {
        $dbParams = [
            'driver'       => 'pdo_mysql',
            'host'         => 'localhost',
            'port'         => '3306',
            'name'         => 'mautic',
            'user'         => 'mautic',
            'table_prefix' => 'mautic_',
        ];

        $this->assertEquals(['error' => null], $this->installer->createSchemaStep($dbParams));
    }

    public function testCreateAdminUserStepWhenPasswordIsMissing(): void
    {
        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->once())
            ->method('find')
            ->willReturn(0);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($mockRepo);

        $data = [
            'firstname' => 'Demo',
            'lastname'  => 'User',
            'username'  => 'admin',
            'email'     => 'demo@demo.com',
        ];

        $this->assertEquals(['password' => null], $this->installer->createAdminUserStep($data));
    }

    public function testCreateAdminUserStepWhenPasswordIsNotLongEnough(): void
    {
        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->once())
            ->method('find')
            ->willReturn(new User());

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($mockRepo);

        $data = [
            'firstname' => 'Demo',
            'lastname'  => 'User',
            'username'  => 'admin',
            'password'  => '1',
            'email'     => 'demo@demo.com',
        ];

        $mockValidation = $this->createMock(ConstraintViolationInterface::class);
        $mockValidation->expects($this->once())
            ->method('getMessage')
            ->willReturn('password');

        $this->validator->method('validate')
            ->withConsecutive([$data['email']], [$data['password']])
            ->willReturnOnConsecutiveCalls(
                new ConstraintViolationList([]),
                new ConstraintViolationList([$mockValidation])
            );

        $this->assertEquals([0 => 'password'], $this->installer->createAdminUserStep($data));
    }
}
