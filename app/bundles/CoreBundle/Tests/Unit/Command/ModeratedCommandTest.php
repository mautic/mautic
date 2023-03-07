<?php

namespace Mautic\CoreBundle\Tests\Unit\Command;

use Mautic\CoreBundle\Command\ModeratedCommand;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Tests\Unit\Command\src\FakeModeratedCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Lock\Exception\LockAcquiringException;

class ModeratedCommandTest extends TestCase
{
    private CoreParametersHelper|MockObject $coreParametersHelper;

    /**
     * @var MockObject|InputInterface
     */
    private $input;

    /**
     * @var MockObject|PathsHelper
     */
    private $pathsHelper;

    /**
     * @var NullOutput
     */
    private $output;

    /**
     * @var FakeModeratedCommand
     */
    private $fakeModeratedCommand;

    protected function setUp(): void
    {
        $this->input                = $this->createMock(InputInterface::class);
        $this->pathsHelper          = $this->createMock(PathsHelper::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->output               = new NullOutput();
        $this->fakeModeratedCommand = new FakeModeratedCommand($this->pathsHelper, $this->coreParametersHelper);
    }

    public function testUnableToWriteLockFileThrowsAnException(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->pathsHelper->expects($this->once())
            ->method('getSystemPath')
            ->with('cache')
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

        $this->fakeModeratedCommand->run($this->input, $this->output);
    }

    public function testLockByPassDoesNotAttemptToCreateALock(): void
    {
        $this->pathsHelper->expects($this->never())
            ->method('getSystemPath');

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

        $this->fakeModeratedCommand->run($this->input, $this->output);
    }

    public function testDeprecatedForceOptionIsRecognized(): void
    {
        $this->pathsHelper->expects($this->never())
            ->method('getSystemPath');

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

        $this->fakeModeratedCommand->run($this->input, $this->output);
    }

    public function testPidLock(): void
    {
        if (!$this->fakeModeratedCommand->isPidSupported()) {
            $this->markTestSkipped('getmypid and/or posix_getpgid are not available');
        }

        $cacheDir = __DIR__.'/resource/cache/tmp';

        $this->pathsHelper->expects($this->once())
            ->method('getSystemPath')
            ->with('cache')
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

        $this->fakeModeratedCommand->run($this->input, $this->output);

        // Assert that the file lock was created
        $runDir   = $cacheDir.'/../run';
        $this->assertFileExists($runDir);

        $finder = new Finder();
        $finder->in($runDir)
            ->name('sf*')
            ->files();

        $this->assertEquals(1, $finder->count());

        // Complete the command
        $this->fakeModeratedCommand->forceCompleteRun();

        // Clean up the files
        $finder = new Finder();
        $finder->in($runDir)
            ->name('sf*')
            ->files();

        $this->assertEquals(0, $finder->count());

        // Cleanup
        rmdir($runDir);
    }

    public function testFileLock(): void
    {
        $cacheDir = __DIR__.'/resource/cache/tmp';

        $this->pathsHelper->expects($this->once())
            ->method('getSystemPath')
            ->with('cache')
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

        $this->fakeModeratedCommand->run($this->input, $this->output);

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
        $this->fakeModeratedCommand->forceCompleteRun();

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

    public function testRedisLock(): void
    {
        $this->coreParametersHelper->expects($this->once())
            ->method('get')
            ->willReturn(['dsn' => 'redis://localhost']);

        $this->input->method('getOption')
            ->willReturnCallback(
                function (string $name) {
                    switch ($name) {
                        case 'lock_mode':
                            return ModeratedCommand::MODE_REDIS;
                        case 'bypass-locking':
                            return false;
                        default:
                            return null;
                    }
                }
            );

        $this->expectException(LockAcquiringException::class);

        $this->fakeModeratedCommand->run($this->input, $this->output);
    }

    private function getFirstFile(Finder $finder): SplFileInfo
    {
        $iterator = $finder->getIterator();
        $iterator->rewind();

        return $iterator->current();
    }
}
