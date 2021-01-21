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

use Mautic\CoreBundle\Command\ModeratedCommand;
use Mautic\CoreBundle\Tests\Unit\Command\src\FakeModeratedCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

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

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->input     = $this->createMock(InputInterface::class);
        $this->output    = new NullOutput();
    }

    public function testUnableToWriteLockFileThrowsAnException()
    {
        $command   = new FakeModeratedCommand();
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

    public function testLockByPassDoesNotAttemptToCreateALock()
    {
        $command   = new FakeModeratedCommand();
        $command->setContainer($this->container);

        $this->container->expects($this->never())
            ->method('getParameter');

        $this->input->method('getOption')
            ->willReturnCallback(
                function (string $name) {
                    switch ($name) {
                        case 'lock_mode':
                            return ModeratedCommand::MODE_FLOCK;
                        case 'bypass-locking':
                            return true;
                        default:
                            return null;
                    }
                }
            );

        $command->run($this->input, $this->output);
    }

    public function testDeprecatedForceOptionIsRecognized()
    {
        $command   = new FakeModeratedCommand();
        $command->setContainer($this->container);

        $this->container->expects($this->never())
            ->method('getParameter');

        $this->input->method('getOption')
            ->willReturnCallback(
                function (string $name) {
                    switch ($name) {
                        case 'lock_mode':
                            return ModeratedCommand::MODE_FLOCK;
                        case 'bypass-locking':
                            return false;
                        case 'force':
                            return true;
                        default:
                            return null;
                    }
                }
            );

        $command->run($this->input, $this->output);
    }

    public function testPidLock(): void
    {
        $command = new FakeModeratedCommand();
        if (!$command->isPidSupported()) {
            $this->markTestSkipped('getmypid and/or posix_getpgid are not available');
        }

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
                            return ModeratedCommand::MODE_PID;
                        case 'bypass-locking':
                            return false;
                        default:
                            return null;
                    }
                }
            );

        $command->setContainer($this->container);
        $command->run($this->input, $this->output);

        // Assert that the file lock was created
        $runDir   = $cacheDir.'/../run';
        $this->assertFileExists($runDir);

        $finder = new Finder();
        $finder->in($runDir)
            ->name('sf*')
            ->files();

        $this->assertEquals(1, $finder->count());

        // Complete the command
        $command->forceCompleteRun();

        // Clean up the files
        $finder = new Finder();
        $finder->in($runDir)
            ->name('sf*')
            ->files();

        $this->assertEquals(0, $finder->count());

        // Cleanup
        rmdir($runDir);
    }

    public function testFileLock()
    {
        $command = new FakeModeratedCommand();
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
                            return ModeratedCommand::MODE_FLOCK;
                        case 'bypass-locking':
                            return false;
                        default:
                            return null;
                    }
                }
            );

        $command->run($this->input, $this->output);

        $runDir = $cacheDir.'/../run';
        $this->assertFileExists($runDir);

        $finder = new Finder();
        $finder->in($runDir)
            ->name('sf*')
            ->files();

        $this->assertEquals(1, $finder->count());

        // Check the file is locked
        $file        = $this->getFirstFile($finder);
        $fileHandler = fopen($file->getPathname(), 'r');
        if (flock($fileHandler, LOCK_EX | LOCK_NB)) {
            $this->fail('file is not locked');
        }
        fclose($fileHandler);

        // Finish the command
        $command->forceCompleteRun();

        // Check the file is unlocked
        $fileHandler = fopen($file->getPathname(), 'r');
        if (!flock($fileHandler, LOCK_EX | LOCK_NB)) {
            $this->fail('file is still locked');
        }
        flock($fileHandler, LOCK_UN | LOCK_NB);
        fclose($fileHandler);

        // Cleanup
        unlink($file->getPathname());
        rmdir($runDir);
    }

    private function getFirstFile(Finder $finder): SplFileInfo
    {
        $iterator = $finder->getIterator();
        $iterator->rewind();

        return $iterator->current();
    }
}
