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
use Symfony\Component\Lock\LockInterface;

class ModeratedCommandTest extends TestCase
{
    private string $lockFilePath;
    private CoreParametersHelper|MockObject $coreParametersHelper;

    /**
     * @var MockObject|InputInterface
     */
    private MockObject $input;

    /**
     * @var MockObject|PathsHelper
     */
    private MockObject $pathsHelper;

    private NullOutput $output;

    private FakeModeratedCommand $fakeModeratedCommand;

    protected function setUp(): void
    {
        $this->lockFilePath         = sys_get_temp_dir().'/test_lock_file.lock';
        $this->input                = $this->createMock(InputInterface::class);
        $this->pathsHelper          = $this->createMock(PathsHelper::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->output               = new NullOutput();

        $this->fakeModeratedCommand = new FakeModeratedCommand(
            $this->pathsHelper,
            $this->coreParametersHelper
        );

        $this->fakeModeratedCommand->setLockFile($this->lockFilePath);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->lockFilePath)) {
            unlink($this->lockFilePath);
        }
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
                fn (string $name) => match ($name) {
                    'lock_mode' => 'file_lock',
                    default     => null,
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
                fn (string $name) => match ($name) {
                    'lock_mode'      => ModeratedCommand::MODE_FLOCK,
                    'bypass-locking' => true,
                    default          => null,
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
                fn (string $name) => match ($name) {
                    'lock_mode'      => ModeratedCommand::MODE_FLOCK,
                    'bypass-locking' => false,
                    'force'          => true,
                    default          => null,
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
                fn (string $name) => match ($name) {
                    'lock_mode'      => ModeratedCommand::MODE_PID,
                    'bypass-locking' => false,
                    default          => null,
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
                fn (string $name) => match ($name) {
                    'lock_mode'      => ModeratedCommand::MODE_FLOCK,
                    'bypass-locking' => false,
                    default          => null,
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
            ->willReturn(['dsn' => '']);

        $this->input->method('getOption')
            ->willReturnCallback(
                fn (string $name) => match ($name) {
                    'lock_mode'      => ModeratedCommand::MODE_REDIS,
                    'bypass-locking' => false,
                    default          => null,
                }
            );

        $this->expectException(\InvalidArgumentException::class);

        $this->fakeModeratedCommand->run($this->input, $this->output);
    }

    private function getFirstFile(Finder $finder): SplFileInfo
    {
        $iterator = $finder->getIterator();
        $iterator->rewind();

        return $iterator->current();
    }

    public function testCompleteRunRemovesLockFileWhenItExists(): void
    {
        // Create a dummy lock file
        file_put_contents($this->lockFilePath, 'test_lock');
        $this->assertFileExists($this->lockFilePath);

        // Mock the lock object to ensure release is called if it exists
        $lock = $this->createMock(LockInterface::class);
        $lock->expects($this->once())
            ->method('release');

        $this->fakeModeratedCommand->setLock($lock);

        // Call the completeRun method
        $this->fakeModeratedCommand->forceCompleteRun();

        // Assert that the lock file is removed
        $this->assertFileDoesNotExist($this->lockFilePath);
    }

    public function testCompleteRunDoesNothingWhenLockFileDoesNotExist(): void
    {
        $this->assertFileDoesNotExist($this->lockFilePath);

        // Mock the lock object to ensure release is called if it exists
        $lock = $this->createMock(LockInterface::class);
        $lock->expects($this->once())
            ->method('release');

        $this->fakeModeratedCommand->setLock($lock);

        // Call the completeRun method
        $this->fakeModeratedCommand->forceCompleteRun();

        // Assert that no error is thrown and file still does not exist
        $this->assertFileDoesNotExist($this->lockFilePath);
    }

    public function testCompleteRunHandlesNullLockObject(): void
    {
        // Ensure lock object is null
        $this->fakeModeratedCommand->setLock(null);

        // Create a dummy lock file
        file_put_contents($this->lockFilePath, 'test_lock');
        $this->assertFileExists($this->lockFilePath);

        // Call the completeRun method
        $this->fakeModeratedCommand->forceCompleteRun();

        // Assert that the lock file is removed
        $this->assertFileDoesNotExist($this->lockFilePath);
    }
}
