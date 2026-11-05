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

        $this->assertEqualsCanonicalizing([$this->imageDir.'/banner.png', $this->imageDir.'/sub/inline.jpg'], $this->helper->extractLocalImageFiles($content));
    }

    public function testExtractLocalImageFilesIgnoresMissingForeignAndTraversal(): void
    {
        file_put_contents($this->mediaDir.'/secret.png', 'secret');

        $content = '<img src="https://example.ddev.site/media/images/missing.png">'
            .'<img src="https://cdn.example.com/assets/remote.png">'
            .'<img src="/media/images/../secret.png">';

        $this->assertSame([], $this->helper->extractLocalImageFiles($content));
    }

    public function testRestoreInHtmlRelocatesFileAndRewritesUrl(): void
    {
        // Simulates the import plumbing having restored the packed image into the deny-all media files dir.
        file_put_contents($this->filesDir.'/banner.png', 'png-bytes');

        $html = '<img src="https://origin.example.com/media/images/promo/banner.png">';

        $result = $this->helper->restoreInHtml($html);

        $this->assertSame('<img src="/media/images/banner.png">', $result);
        $this->assertFileExists($this->imageDir.'/banner.png');
        $this->assertSame('png-bytes', file_get_contents($this->imageDir.'/banner.png'));
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

        $this->assertSame('<img src="/media/images/hero.png">', $result['slots']['hero']);
        $this->assertSame('No image here', $result['meta']['title']);
        $this->assertFileExists($this->imageDir.'/hero.png');
    }

    public function testRestoredSvgLosesItsScripts(): void
    {
        file_put_contents($this->filesDir.'/logo.svg', <<<'SVG'
            <svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)">
                <script>alert(2)</script>
                <a href="javascript:alert(3)"><rect width="10" height="10" fill="#000"/></a>
            </svg>
            SVG);

        $this->helper->restoreInHtml('<img src="https://origin.example.com/media/images/logo.svg">');

        $restored = (string) file_get_contents($this->imageDir.'/logo.svg');

        $this->assertStringNotContainsString('<script', $restored);
        $this->assertStringNotContainsString('onload', $restored);
        $this->assertStringNotContainsString('javascript:', $restored);
        // The drawing itself must survive — this runs on every imported SVG, not just hostile ones.
        $this->assertStringContainsString('<rect', $restored);
    }

    public function testRestoredSvgThatIsNotXmlIsDiscarded(): void
    {
        file_put_contents($this->filesDir.'/broken.svg', '<html><script>alert(1)</script>');

        $this->helper->restoreInHtml('<img src="https://origin.example.com/media/images/broken.svg">');

        $this->assertFileDoesNotExist($this->imageDir.'/broken.svg');
    }

    public function testRestoredRasterImageIsUntouched(): void
    {
        file_put_contents($this->filesDir.'/photo.png', 'raw-png-bytes');

        $this->helper->restoreInHtml('<img src="https://origin.example.com/media/images/photo.png">');

        $this->assertSame('raw-png-bytes', file_get_contents($this->imageDir.'/photo.png'));
    }

    private function coreParametersHelper(): CoreParametersHelper&MockObject
    {
        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $coreParametersHelper->method('get')->willReturnCallback(
            fn (string $key, mixed $default = null): mixed => 'image_path' === $key ? 'media/images' : $default,
        );

        return $coreParametersHelper;
    }
}
