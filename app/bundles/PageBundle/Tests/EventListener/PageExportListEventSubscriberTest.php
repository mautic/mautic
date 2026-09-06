<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\EventListener;

use Mautic\AssetBundle\Event\AssetExportListEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\EmailBundle\Helper\EmailMediaImageHelper;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\EventListener\PageExportListEventSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class PageExportListEventSubscriberTest extends TestCase
{
    private Filesystem $filesystem;

    private string $imageDir;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        // realpath: macOS's temp dir is a symlink (/var -> /private/var) and the helper
        // resolves collected paths, so the expectation must use the resolved prefix too.
        $this->imageDir = realpath(sys_get_temp_dir()).'/page_export_images_test/media/images';
        $this->filesystem->mkdir($this->imageDir.'/sub');
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove(\dirname($this->imageDir, 2));
    }

    public function testCollectsBuilderImagesReferencedInPageContent(): void
    {
        file_put_contents($this->imageDir.'/hero.png', 'png-bytes');
        file_put_contents($this->imageDir.'/sub/inline.jpg', 'jpg-bytes');

        $page = [
            'custom_html' => '<img src="https://example.ddev.site/media/images/hero.png">',
            'content'     => ['slots' => ['main' => 'https://example.ddev.site/media/images/sub/inline.jpg']],
        ];

        $event = $this->dispatchForPage($page);

        $this->assertEqualsCanonicalizing([$this->imageDir.'/hero.png', $this->imageDir.'/sub/inline.jpg'], $event->getList());
    }

    public function testIgnoresMissingFilesAndForeignUrls(): void
    {
        $page = [
            'custom_html' => '<img src="https://example.ddev.site/media/images/does-not-exist.png">'
                .'<img src="https://cdn.example.com/assets/remote.png">',
        ];

        $event = $this->dispatchForPage($page);

        $this->assertSame([], $event->getList());
    }

    /**
     * @param array<string, mixed> $page
     */
    private function dispatchForPage(array $page): AssetExportListEvent
    {
        $pathsHelper = $this->createMock(PathsHelper::class);
        $pathsHelper->method('getImagePath')->willReturn($this->imageDir);

        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $coreParametersHelper->method('get')->willReturnCallback(
            fn (string $key, mixed $default = null): mixed => 'image_path' === $key ? 'media/images' : $default,
        );

        $subscriber = new PageExportListEventSubscriber(new EmailMediaImageHelper($pathsHelper, $coreParametersHelper));

        // The event's declared section type is loose; real export data (matched here) holds a list of
        // entities per name, exactly as EntityExportEvent::getEntities() produces at runtime.
        // @phpstan-ignore argument.type
        $event = new AssetExportListEvent([[Page::ENTITY_NAME => [$page]]]);
        $subscriber->onExportList($event);

        return $event;
    }
}
