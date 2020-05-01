<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://www.mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Unit\Command;

use Mautic\CoreBundle\Tests\Unit\Command\src\FakeCommand;
use Mautic\CoreBundle\Tests\Unit\Command\src\FakeCompleteCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;

class ModeratedCommandTest extends TestCase
{
    /**
     * @var MockObject|ContainerInterface
     */
    private $container;

    /**
     * @var MockObject|InputInterface
     */
    private $input;

    /**
     * @var NullOutput
     */
    private $output;

    protected function setUp()
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->input     = $this->createMock(InputInterface::class);
        $this->output    = new NullOutput();
    }

    public function testUnableToWriteLockFileThrowsAnException()
    {
        $command   = new FakeCommand();
        $command->setContainer($this->container);

        $this->expectException(\RuntimeException::class);

        $this->container->expects($this->once())
            ->method('getParameter')
            ->with('kernel.cache_dir')
            ->willReturn('/does/not/exist');

        $this->input->method('getOption')
            ->willReturnCallback(
                function (string $name) {
                    switch ($name) {
                        case 'lock_mode':
                            return 'file_lock';
                        default:
                            return null;
                    }
                }
            );

        $command->run($this->input, $this->output);
    }

    public function testLockFileCreated()
    {
        $command   = new FakeCommand();
        $command->setContainer($this->container);

        $cacheDir = __DIR__.'/resource/cache/tmp';
        $this->container->expects($this->once())
            ->method('getParameter')
            ->with('kernel.cache_dir')
            ->willReturn($cacheDir);

        $this->input->method('getOption')
            ->willReturnCallback(
                function (string $name) {
                    switch ($name) {
                        case 'lock_mode':
                            return 'file_lock';
                        case 'force':
                            return false;
                        default:
                            return null;
                    }
                }
            );

        $returnCode = $command->run($this->input, $this->output);

        // Assert that 1 is returned because the checkRunStatus status gave the go to process
        $this->assertSame(1, $returnCode);

        // Assert that the file lock was created
        $runDir = $cacheDir.'/../run';
        $this->assertFileExists($runDir);

        $finder = new Finder();
        $finder->in($runDir)
            ->name('sf*')
            ->files();

        $this->assertEquals(1, $finder->count());

        // Cleanup
        foreach ($finder as $file) {
            unlink($file->getPathname());
        }

        rmdir($runDir);
    }

    public function testLockFileIsCleanedUp()
    {
        $command   = new FakeCompleteCommand();
        $command->setContainer($this->container);

        $cacheDir = __DIR__.'/resource/cache/tmp';
        $this->container->expects($this->once())
            ->method('getParameter')
            ->with('kernel.cache_dir')
            ->willReturn($cacheDir);

        $this->input->method('getOption')
            ->willReturnCallback(
                function (string $name) {
                    switch ($name) {
                        case 'lock_mode':
                            return 'file_lock';
                        case 'force':
                            return false;
                        default:
                            return null;
                    }
                }
            );

        $returnCode = $command->run($this->input, $this->output);

        // Assert that 1 is returned because the checkRunStatus status gave the go to process
        $this->assertSame(1, $returnCode);

        // Assert that the file lock was removed after the command completed execution
        $runDir = $cacheDir.'/../run';
        $this->assertFileExists($runDir);

        $finder = new Finder();
        $finder->in($runDir)
            ->name('sf*')
            ->files();

        $this->assertEquals(0, $finder->count());

        // Cleanup
        rmdir($runDir);
    }

    public function testLockByPassDoesNotAttemptToCreateALock()
    {
        $command   = new FakeCommand();
        $command->setContainer($this->container);

        $this->container->expects($this->never())
            ->method('getParameter');

        $this->input->method('getOption')
            ->willReturnCallback(
                function (string $name) {
                    switch ($name) {
                        case 'lock_mode':
                            return 'file_lock';
                        case 'bypass-locking':
                            return true;
                        default:
                            return null;
                    }
                }
            );

        $resultCode = $command->run($this->input, $this->output);

        // Assert that 1 is returned because the checkRunStatus status gave the go to process
        $this->assertSame(1, $resultCode);
    }
}
