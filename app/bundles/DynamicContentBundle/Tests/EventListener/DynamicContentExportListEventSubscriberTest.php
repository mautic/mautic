<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Tests\EventListener;

use Mautic\AssetBundle\Event\AssetExportListEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\DynamicContentBundle\EventListener\DynamicContentExportListEventSubscriber;
use Mautic\EmailBundle\Helper\EmailMediaImageHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class DynamicContentExportListEventSubscriberTest extends TestCase
{
    private Filesystem $filesystem;

    private string $imageDir;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        // realpath: macOS's temp dir is a symlink (/var -> /private/var) and the helper
        // resolves collected paths, so the expectation must use the resolved prefix too.
        $this->imageDir = realpath(sys_get_temp_dir()).'/dwc_export_images_test/media/images';
        $this->filesystem->mkdir($this->imageDir);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove(\dirname($this->imageDir, 2));
    }

    public function testCollectsBuilderImagesReferencedInHtmlContent(): void
    {
        file_put_contents($this->imageDir.'/promo.png', 'png-bytes');

        $event = $this->dispatchForContent(
            ['content' => '<img src="https://example.ddev.site/media/images/promo.png">'],
        );

        $this->assertSame([$this->imageDir.'/promo.png'], $event->getList());
    }

    public function testCollectsImagesFromStructuredBuilderContent(): void
    {
        file_put_contents($this->imageDir.'/slot.png', 'png-bytes');

        $event = $this->dispatchForContent(
            ['content' => ['slots' => ['main' => 'https://example.ddev.site/media/images/slot.png']]],
        );

        $this->assertSame([$this->imageDir.'/slot.png'], $event->getList());
    }

    public function testIgnoresMissingFilesAndForeignUrls(): void
    {
        $event = $this->dispatchForContent([
            'content' => '<img src="https://example.ddev.site/media/images/does-not-exist.png">'
                .'<img src="https://cdn.example.com/assets/remote.png">',
        ]);

        $this->assertSame([], $event->getList());
    }

    /**
     * @param array<string, mixed> $dynamicContent
     */
    private function dispatchForContent(array $dynamicContent): AssetExportListEvent
    {
        $pathsHelper = $this->createMock(PathsHelper::class);
        $pathsHelper->method('getImagePath')->willReturn($this->imageDir);

        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $coreParametersHelper->method('get')->willReturnCallback(
            fn (string $key, mixed $default = null): mixed => 'image_path' === $key ? 'media/images' : $default,
        );

        $subscriber = new DynamicContentExportListEventSubscriber(new EmailMediaImageHelper($pathsHelper, $coreParametersHelper));

        // The event's declared section type is loose; real export data (matched here) holds a list of
        // entities per name, exactly as EntityExportEvent::getEntities() produces at runtime.
        // @phpstan-ignore argument.type
        $event = new AssetExportListEvent([[DynamicContent::ENTITY_NAME => [$dynamicContent]]]);
        $subscriber->onExportList($event);

        return $event;
    }
}
