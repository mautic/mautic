<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\InstallBundle\Tests\Install;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Configurator\Configurator;
use Mautic\CoreBundle\Configurator\Step\StepInterface;
use Mautic\CoreBundle\Helper\CacheHelper;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\InstallBundle\Configurator\Step\CheckStep;
use Mautic\InstallBundle\Configurator\Step\DoctrineStep;
use Mautic\InstallBundle\Configurator\Step\EmailStep;
use Mautic\InstallBundle\Helper\SchemaHelper;
use Mautic\InstallBundle\Install\InstallService;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class InstallServiceTest extends \PHPUnit\Framework\TestCase
{
    private $configurator;

    private $cacheHelper;
    private $pathsHelper;
    private $entityManager;
    private $translator;
    private $kernel;
    private $validator;
    private $encoder;

    private $installer;

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
        $this->encoder              = $this->createMock(UserPasswordEncoder::class);

        $this->installer = new InstallService(
            $this->configurator,
            $this->cacheHelper,
            $this->pathsHelper,
            $this->entityManager,
            $this->translator,
            $this->kernel,
            $this->validator,
            $this->encoder
        );
    }

    public function testCheckIfInstalledWhenNoLocalConfig(): void
    {
        $this->pathsHelper->expects($this->once())
            ->method('getSystemPath')
            ->with('local_config', false)
            ->willReturn(
                null
            );

        $this->assertFalse($this->installer->checkIfInstalled());
    }

    public function testGetStepWhenNoLocalConfig(): void
    {
        $this->pathsHelper->expects($this->once())
            ->method('getSystemPath')
            ->with('local_config', false)
            ->willReturn(
                null
            );

        $this->configurator->expects($this->once())
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
            ->with('local_config', false)
            ->willReturn(
                null
            );

        $this->configurator->expects($this->once())
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

        $messages = true;

        $step->expects($this->once())
            ->method('update')
            ->with($step)
            ->willReturn($params);

        $this->configurator->expects($this->once())
            ->method('write');

        $this->configurator->expects($this->once())
            ->method('mergeParameters')
            ->with($params)
            ->willReturn($messages);

        $this->assertEquals($messages, $this->installer->saveConfiguration($params, $step, $clearCache));
    }

    public function testSaveConfigurationWhenCacheClear(): void
    {
        $params     = [];
        $step       = $this->createMock(StepInterface::class);
        $clearCache = true;

        $messages = true;

        $step->expects($this->once())
            ->method('update')
            ->with($step)
            ->willReturn($params);

        $this->configurator->expects($this->once())
            ->method('mergeParameters')
            ->with($params)
            ->willReturn($messages);

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
            'driver' => 'mysql',
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
            'driver' => 'mysql',
            'host'   => 'localhost',
            'port'   => '3306',
            'name'   => 'mautic',
            'user'   => 'mautic',
        ];

        $this->assertEquals(true, $this->installer->validateDatabaseParams($dbParams));
    }
}
