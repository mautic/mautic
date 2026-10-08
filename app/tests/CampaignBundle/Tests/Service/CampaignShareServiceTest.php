<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Service;

use Mautic\CampaignBundle\Service\CampaignShareService;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\ExportHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class CampaignShareServiceTest extends TestCase
{
    private CampaignShareService $service;

    protected function setUp(): void
    {
        $this->service = new CampaignShareService(
            $this->createStub(ExportHelper::class),
            $this->createStub(CoreParametersHelper::class),
            $this->createStub(UrlGeneratorInterface::class),
            new Filesystem(),
        );
    }

    /**
     * Whatever lands in this metadata is written into the package's composer.json and read back
     * by the marketplace, so markup must not make it out of here.
     */
    public function testShortMetadataFieldsAreStrippedOfMarkup(): void
    {
        $metadata = $this->service->buildShareMetadata([
            'title'             => 'Welcome flow<img src onerror=alert(1)>',
            'vendorName'        => '<b>acme</b>',
            'headline'          => 'Onboarding<script>alert(2)</script>',
            'keywords'          => 'email, <i>drip</i>',
            'version'           => '2.0.1<img src onerror=alert(3)>" onerror=alert(4)>',
            'worksWithVersions' => ['7.0<img src onerror=alert(5)>', '<b></b>'],
            'languages'         => ['en<img src onerror=alert(6)>'],
            'galleryImage1'     => 'banner.png',
            'galleryAlt1'       => '" onclick=alert(7) x-data="',
        ]);

        $this->assertSame('Welcome flow', $metadata['title']);
        $this->assertSame('acme', $metadata['vendorName']);
        $this->assertSame('email, drip', $metadata['keywords']);
        $this->assertSame('en', $metadata['languages'][0]);

        // strip_tags keeps what was between the tags. That text is inert — what matters is that
        // nothing can be parsed as markup again.
        $this->assertSame('Onboardingalert(2)', $metadata['headline']);
        $this->assertSame('2.0.1" onerror=alert(4)', $metadata['version']);

        foreach (['title', 'vendorName', 'headline', 'keywords', 'version'] as $field) {
            $this->assertStringNotContainsString('<', (string) $metadata[$field]);
            $this->assertStringNotContainsString('>', (string) $metadata[$field]);
        }

        // Quotes survive, and should: "John's screenshot" is ordinary alt text. Escaping them is
        // the renderer's job, not this method's.
        $this->assertSame('" onclick=alert(7) x-data="', $metadata['gallery'][0]['alt']);
    }

    /**
     * List entries that were nothing but markup would otherwise survive as empty strings.
     */
    public function testEmptiedListEntriesAreDropped(): void
    {
        $metadata = $this->service->buildShareMetadata([
            'title'             => 'Flow',
            'version'           => '1.0.0',
            'worksWithVersions' => ['7.0', '<b></b>', '6.0'],
        ]);

        $this->assertSame(['7.0', '6.0'], $metadata['worksWithVersions']);
    }

    /**
     * Description is markdown and is rendered with HTML input escaped, so stripping tags here
     * would only corrupt legitimate content.
     */
    public function testDescriptionIsLeftAsWritten(): void
    {
        $description = "A campaign for onboarding.\n\nWorks when list size < 500.";

        $metadata = $this->service->buildShareMetadata([
            'title'       => 'Flow',
            'version'     => '1.0.0',
            'description' => $description,
        ]);

        $this->assertSame($description, $metadata['description']);
    }
}
