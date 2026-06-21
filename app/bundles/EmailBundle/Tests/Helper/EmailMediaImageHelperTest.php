<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\EmailBundle\Helper\EmailMediaImageHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class EmailMediaImageHelperTest extends TestCase
{
    private Filesystem $filesystem;

    private string $mediaDir;

    private string $imageDir;

    private string $filesDir;

    private EmailMediaImageHelper $helper;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->mediaDir   = sys_get_temp_dir().'/email_media_helper_test/media';
        $this->imageDir   = $this->mediaDir.'/images';
        $this->filesDir   = $this->mediaDir.'/files';
        $this->filesystem->mkdir([$this->imageDir.'/sub', $this->filesDir]);

        $pathsHelper = $this->createMock(PathsHelper::class);
        $pathsHelper->method('getImagePath')->willReturn($this->imageDir);
        $pathsHelper->method('getMediaPath')->willReturn($this->mediaDir);

        $this->helper = new EmailMediaImageHelper($pathsHelper, $this->coreParametersHelper());
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove(\dirname($this->mediaDir));
    }

    public function testExtractLocalImageFilesResolvesExistingReferences(): void
    {
        file_put_contents($this->imageDir.'/banner.png', 'png');
        file_put_contents($this->imageDir.'/sub/inline.jpg', 'jpg');

        $content = '<img src="https://example.ddev.site/media/images/banner.png">'
            .'background:url(https://example.ddev.site/media/images/sub/inline.jpg)';

        self::assertEqualsCanonicalizing(
            [$this->imageDir.'/banner.png', $this->imageDir.'/sub/inline.jpg'],
            $this->helper->extractLocalImageFiles($content),
        );
    }

    public function testExtractLocalImageFilesIgnoresMissingForeignAndTraversal(): void
    {
        file_put_contents($this->mediaDir.'/secret.png', 'secret');

        $content = '<img src="https://example.ddev.site/media/images/missing.png">'
            .'<img src="https://cdn.example.com/assets/remote.png">'
            .'<img src="/media/images/../secret.png">';

        self::assertSame([], $this->helper->extractLocalImageFiles($content));
    }

    public function testRestoreInHtmlRelocatesFileAndRewritesUrl(): void
    {
        // Simulates the import plumbing having restored the packed image into the deny-all media files dir.
        file_put_contents($this->filesDir.'/banner.png', 'png-bytes');

        $html = '<img src="https://origin.example.com/media/images/promo/banner.png">';

        $result = $this->helper->restoreInHtml($html);

        self::assertSame('<img src="/media/images/banner.png">', $result);
        self::assertFileExists($this->imageDir.'/banner.png');
        self::assertSame('png-bytes', file_get_contents($this->imageDir.'/banner.png'));
    }

    public function testRestoreInContentRewritesNestedReferences(): void
    {
        file_put_contents($this->filesDir.'/hero.png', 'hero-bytes');

        $content = [
            'slots' => [
                'hero' => '<img src="https://origin.example.com/media/images/hero.png">',
            ],
            'meta' => ['title' => 'No image here'],
        ];

        $result = $this->helper->restoreInContent($content);

        self::assertSame('<img src="/media/images/hero.png">', $result['slots']['hero']);
        self::assertSame('No image here', $result['meta']['title']);
        self::assertFileExists($this->imageDir.'/hero.png');
    }

    private function coreParametersHelper(): CoreParametersHelper&MockObject
    {
        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $coreParametersHelper->method('get')->willReturnCallback(
            fn (string $key, mixed $default = null) => 'image_path' === $key ? 'media/images' : $default,
        );

        return $coreParametersHelper;
    }
}
