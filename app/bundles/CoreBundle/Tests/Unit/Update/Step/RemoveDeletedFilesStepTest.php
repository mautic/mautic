<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://www.mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Unit\Update\Step;

use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Update\Step\RemoveDeletedFilesStep;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class RemoveDeletedFilesStepTest extends AbstractStepTest
{
    /**
     * @var MockObject|TranslatorInterface
     */
    private $translator;

    /**
     * @var MockObject|LoggerInterface
     */
    private $logger;

    /**
     * @var MockObject|PathsHelper
     */
    private $pathsHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator  = $this->createMock(TranslatorInterface::class);
        $this->logger      = $this->createMock(LoggerInterface::class);
        $this->pathsHelper = $this->createMock(PathsHelper::class);
    }

    public function testNothingDoneIfDeletedFileListDoesNotExist()
    {
        $this->pathsHelper->method('getRootPath')
            ->willReturn(__DIR__);

        $step = $this->getStep();

        $step->execute($this->progressBar, $this->input, $this->output);
        $this->assertEquals(0, $this->progressBar->getProgress());
    }

    public function testFileIsDeleted()
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

        $this->assertFileNotExists($resourcePath.'/delete_me.txt');
        $this->assertFileNotExists($resourcePath.'/deleted_files.txt');
    }

    public function testNonExistentFileIsIgnored()
    {
        $resourcePath = __DIR__.'/resources';
        file_put_contents($resourcePath.'/deleted_files.txt', '["delete_me.txt"]');

        $this->assertFileNotExists($resourcePath.'/delete_me.txt');

        $this->pathsHelper->method('getRootPath')
            ->willReturn($resourcePath);

        $step = $this->getStep();

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturn('');

        $step->execute($this->progressBar, $this->input, $this->output);
        $this->logger->expects($this->never())
            ->method('error');

        $this->assertFileNotExists($resourcePath.'/deleted_files.txt');
    }

    private function getStep(): RemoveDeletedFilesStep
    {
        return new RemoveDeletedFilesStep($this->translator, $this->pathsHelper, $this->logger);
    }
}
