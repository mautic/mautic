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

use Mautic\CoreBundle\Exception\UpdateFailedException;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\UpdateHelper;
use Mautic\CoreBundle\Update\Step\InstallNewFilesStep;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Translation\TranslatorInterface;

class InstallNewFilesStepTest extends AbstractStepTest
{
    /**
     * @var MockObject|TranslatorInterface
     */
    private $translator;

    /**
     * @var MockObject|UpdateHelper
     */
    private $updateHelper;

    /**
     * @var MockObject|PathsHelper
     */
    private $pathsHelper;

    /**
     * @var InstallNewFilesStep
     */
    private $step;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator   = $this->createMock(TranslatorInterface::class);
        $this->updateHelper = $this->createMock(UpdateHelper::class);
        $this->pathsHelper  = $this->createMock(PathsHelper::class);

        $this->step = new InstallNewFilesStep($this->translator, $this->updateHelper, $this->pathsHelper);
    }

    public function testUpdatePackageUnzipped()
    {
        $resourcePath = __DIR__.'/resources';

        // Copy the zip so that the original isn't deleted
        copy($resourcePath.'/update.zip', $resourcePath.'/update-test.zip');

        $this->updateHelper->expects($this->once())
            ->method('fetchData')
            ->willReturn(
                [
                    'package' => $resourcePath.'/update-test.zip',
                ]
            );

        $this->pathsHelper->expects($this->once())
            ->method('getCachePath')
            ->willReturn($resourcePath);

        $this->pathsHelper->expects($this->once())
            ->method('getRootPath')
            ->willReturn($resourcePath);

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturn('');

        $this->step->execute($this->progressBar, $this->input, $this->output);

        $this->assertFileExists($resourcePath.'/update');
        $this->assertFileNotExists($resourcePath.'/update-test.zip');

        // Cleanup
        $filesystem = new Filesystem();
        $filesystem->remove($resourcePath.'/update');
    }

    public function testCustomUpdatePackageUnzipped()
    {
        $resourcePath = __DIR__.'/resources';

        // Copy the zip so that the original isn't deleted
        copy($resourcePath.'/update.zip', $resourcePath.'/update-test.zip');

        $this->updateHelper->expects($this->never())
            ->method('fetchData');

        $this->pathsHelper->expects($this->never())
            ->method('getCachePath');

        $this->pathsHelper->expects($this->once())
            ->method('getRootPath')
            ->willReturn($resourcePath);

        $this->input->expects($this->once())
            ->method('getOption')
            ->with('update-package')
            ->willReturn($resourcePath.'/update-test.zip');

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturn('');

        $this->step->execute($this->progressBar, $this->input, $this->output);

        $this->assertFileExists($resourcePath.'/update');
        $this->assertFileNotExists($resourcePath.'/update-test.zip');

        // Cleanup
        $filesystem = new Filesystem();
        $filesystem->remove($resourcePath.'/update');
    }

    public function testUpdateFailedExceptionThrownIfCustomPackageDoesNotExist()
    {
        $this->expectException(UpdateFailedException::class);

        $resourcePath = __DIR__.'/resources';

        $this->updateHelper->expects($this->never())
            ->method('fetchData');

        $this->input->expects($this->once())
            ->method('getOption')
            ->with('update-package')
            ->willReturn($resourcePath.'/update-test.zip');

        $this->step->execute($this->progressBar, $this->input, $this->output);
    }

    public function testUpdateFailedExceptionThrownIfUnzippingFails()
    {
        $this->expectException(UpdateFailedException::class);

        $resourcePath = __DIR__.'/resources';

        $this->updateHelper->expects($this->once())
            ->method('fetchData')
            ->willReturn(
                [
                    'package' => $resourcePath.'/update-test.zip',
                ]
            );

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturn('');

        $this->step->execute($this->progressBar, $this->input, $this->output);
    }
}
