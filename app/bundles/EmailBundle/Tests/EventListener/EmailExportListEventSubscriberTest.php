<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\EventListener;

use Mautic\AssetBundle\Event\AssetExportListEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\EventListener\EmailExportListEventSubscriber;
use Mautic\EmailBundle\Helper\EmailMediaImageHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class EmailExportListEventSubscriberTest extends TestCase
{
    private Filesystem $filesystem;

    private string $imageDir;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->imageDir   = sys_get_temp_dir().'/email_export_images_test/media/images';
        $this->filesystem->mkdir($this->imageDir.'/sub');
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove(\dirname($this->imageDir, 2));
    }

    public function testCollectsBuilderImagesReferencedInEmailContent(): void
    {
        file_put_contents($this->imageDir.'/banner.png', 'png-bytes');
        file_put_contents($this->imageDir.'/sub/inline.jpg', 'jpg-bytes');

        $email = [
            'custom_html' => '<img src="https://example.ddev.site/media/images/banner.png">',
            'content'     => ['slots' => ['hero' => 'https://example.ddev.site/media/images/sub/inline.jpg']],
        ];

        $event = $this->dispatchForEmail($email);

        $this->assertEqualsCanonicalizing([$this->imageDir.'/banner.png', $this->imageDir.'/sub/inline.jpg'], $event->getList());
    }

    public function testIgnoresMissingFilesAndForeignUrls(): void
    {
        $email = [
            'custom_html' => '<img src="https://example.ddev.site/media/images/does-not-exist.png">'
                .'<img src="https://cdn.example.com/assets/remote.png">',
        ];

        $event = $this->dispatchForEmail($email);

        $this->assertSame([], $event->getList());
    }

    public function testGuardsAgainstPathTraversal(): void
    {
        file_put_contents(\dirname($this->imageDir).'/secret.png', 'secret');

        $email = [
            'custom_html' => '<img src="/media/images/../secret.png">',
        ];

        $event = $this->dispatchForEmail($email);

        $this->assertSame([], $event->getList());
    }

    /**
     * @param array<string, mixed> $email
     */
    private function dispatchForEmail(array $email): AssetExportListEvent
    {
        $pathsHelper = $this->createMock(PathsHelper::class);
        $pathsHelper->method('getImagePath')->willReturn($this->imageDir);

        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $coreParametersHelper->method('get')->willReturnCallback(
            fn (string $key, mixed $default = null): mixed => 'image_path' === $key ? 'media/images' : $default,
        );

        $subscriber = new EmailExportListEventSubscriber(new EmailMediaImageHelper($pathsHelper, $coreParametersHelper));

        // The event's declared section type is loose; real export data (matched here) holds a list of
        // entities per name, exactly as EntityExportEvent::getEntities() produces at runtime.
        // @phpstan-ignore argument.type
        $event = new AssetExportListEvent([[Email::ENTITY_NAME => [$email]]]);
        $subscriber->onExportList($event);

        return $event;
    }
}
