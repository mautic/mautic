<?php

namespace Mautic\CoreBundle\Tests\Unit\Update\Step;

use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Update\Step\RemoveDeletedFilesStep;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RemoveDeletedFilesStepTest extends AbstractStepTest
{
    /**
     * @var MockObject|TranslatorInterface
     */
    private MockObject $translator;

    /**
     * @var MockObject|LoggerInterface
     */
    private MockObject $logger;

    /**
     * @var MockObject|PathsHelper
     */
    private MockObject $pathsHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator  = $this->createMock(TranslatorInterface::class);
        $this->logger      = $this->createMock(LoggerInterface::class);
        $this->pathsHelper = $this->createMock(PathsHelper::class);
    }

    public function testNothingDoneIfDeletedFileListDoesNotExist(): void
    {
        $this->pathsHelper->method('getRootPath')
            ->willReturn(__DIR__);

        $step = $this->getStep();

        $step->execute($this->progressBar, $this->input, $this->output);
        $this->assertEquals(0, $this->progressBar->getProgress());
    }

    public function testFileIsDeleted(): void
    {
        $resourcePath = __DIR__.'/resources';

        file_put_contents($resourcePath.'/deleted_files.txt', '["delete_me.txt"]');
        file_put_contents($resourcePath.'/delete_me.txt', '');

        $this->assertFileExists($resourcePath.'/delete_me.txt');

        $this->pathsHelper->method('getRootPath')
            ->willReturn($resourcePath);

        $step = $this->getStep();

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturn('');

        $step->execute($this->progressBar, $this->input, $this->output);

        $this->assertFileDoesNotExist($resourcePath.'/delete_me.txt');
        $this->assertFileDoesNotExist($resourcePath.'/deleted_files.txt');
    }

    public function testNonExistentFileIsIgnored(): void
    {
        $resourcePath = __DIR__.'/resources';
        file_put_contents($resourcePath.'/deleted_files.txt', '["delete_me.txt"]');

        $this->assertFileDoesNotExist($resourcePath.'/delete_me.txt');

        $this->pathsHelper->method('getRootPath')
            ->willReturn($resourcePath);

        $step = $this->getStep();

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturn('');

        $step->execute($this->progressBar, $this->input, $this->output);
        $this->logger->expects($this->never())
            ->method('error');

        $this->assertFileDoesNotExist($resourcePath.'/deleted_files.txt');
    }

    private function getStep(): RemoveDeletedFilesStep
    {
        return new RemoveDeletedFilesStep($this->translator, $this->pathsHelper, $this->logger);
    }
}
