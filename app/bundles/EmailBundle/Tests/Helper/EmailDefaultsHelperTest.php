<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Helper\EmailDefaultsHelper;
use Mautic\PageBundle\Entity\Page;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class EmailDefaultsHelperTest extends TestCase
{
    private MockObject&CoreParametersHelper $coreParametersHelper;

    private MockObject&EntityManagerInterface $entityManager;

    private EmailDefaultsHelper $helper;

    protected function setUp(): void
    {
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->entityManager        = $this->createMock(EntityManagerInterface::class);
        $this->helper               = new EmailDefaultsHelper(
            $this->coreParametersHelper,
            $this->entityManager,
        );
    }

    public function testAppliesPreferenceCenterAndUtmTagDefaults(): void
    {
        $page = new Page();
        $page->setTitle('Default PC');

        $this->coreParametersHelper->expects($this->exactly(5))->method('get')->willReturnMap([
            ['email_default_preference_center_id', null, 42],
            ['email_default_utm_source', null, 'config-source'],
            ['email_default_utm_medium', null, 'config-medium'],
            ['email_default_utm_campaign', null, 'config-campaign'],
            ['email_default_utm_content', null, 'config-content'],
        ]);

        $this->entityManager->method('find')
            ->with(Page::class, 42)
            ->willReturn($page);

        $email = new Email();
        $this->helper->applyDefaults($email);

        $this->assertSame($page, $email->getPreferenceCenter());
        $this->assertSame([
            'utmSource'   => 'config-source',
            'utmMedium'   => 'config-medium',
            'utmCampaign' => 'config-campaign',
            'utmContent'  => 'config-content',
        ], $email->getUtmTags());
    }

    public function testDoesNotOverwriteExistingPreferenceCenter(): void
    {
        $existingPage = new Page();
        $existingPage->setTitle('Existing PC');

        $email = new Email();
        $email->setPreferenceCenter($existingPage);

        $this->coreParametersHelper->expects($this->exactly(4))->method('get')->willReturnMap([
            ['email_default_preference_center_id', null, 99],
            ['email_default_utm_source', null, 'config-source'],
            ['email_default_utm_medium', null, null],
            ['email_default_utm_campaign', null, null],
            // ['email_default_utm_content', null, null],
        ]);

        // Verify helper skips loading when preference center already set
        $this->entityManager->expects($this->never())->method('find');

        $this->helper->applyDefaults($email);

        $this->assertSame($existingPage, $email->getPreferenceCenter());
    }

    public function testDoesNotOverwriteExistingUtmTags(): void
    {
        $existingUtmTags = [
            'utmSource'   => 'existing-source',
            'utmMedium'   => 'existing-medium',
            'utmCampaign' => 'existing-campaign',
            'utmContent'  => 'existing-content',
        ];

        $email = new Email();
        $email->setUtmTags($existingUtmTags);

        $this->coreParametersHelper->expects($this->exactly(1))->method('get')->willReturnMap([
            ['email_default_preference_center_id', null, null],
        ]);

        $this->helper->applyDefaults($email);

        $this->assertSame($existingUtmTags, $email->getUtmTags());
    }

    public function testAppliesDefaultsWhenUtmTagsContainOnlyNullValues(): void
    {
        // Form submission with clearMissing=true sets all fields to null; verify we treat this as "empty"
        $email = new Email();
        $email->setUtmTags([
            'utmSource'   => null,
            'utmMedium'   => null,
            'utmCampaign' => null,
            'utmContent'  => null,
        ]);

        $this->coreParametersHelper->expects($this->exactly(5))->method('get')->willReturnMap([
            ['email_default_preference_center_id', null, null],
            ['email_default_utm_source', null, 'config-source'],
            ['email_default_utm_medium', null, 'config-medium'],
            ['email_default_utm_campaign', null, null],
            ['email_default_utm_content', null, null],
        ]);

        $this->helper->applyDefaults($email);

        $this->assertSame([
            'utmSource' => 'config-source',
            'utmMedium' => 'config-medium',
        ], $email->getUtmTags());
    }

    public function testFiltersOutNullAndEmptyUtmValues(): void
    {
        $this->coreParametersHelper->expects($this->exactly(5))->method('get')->willReturnMap([
            ['email_default_preference_center_id', null, null],
            ['email_default_utm_source', null, 'only-source'],
            ['email_default_utm_medium', null, null],
            ['email_default_utm_campaign', null, ''],
            ['email_default_utm_content', null, null],
        ]);

        $email = new Email();
        $this->helper->applyDefaults($email);

        $this->assertSame(['utmSource' => 'only-source'], $email->getUtmTags());
    }

    public function testLeavesFieldsUnchangedWhenConfigIsEmpty(): void
    {
        $this->coreParametersHelper->method('get')->willReturn(null);
        $this->entityManager->expects($this->never())->method('find');

        $email = new Email();
        $this->helper->applyDefaults($email);

        $this->assertNull($email->getPreferenceCenter());
        $this->assertEmpty($email->getUtmTags());
    }

    public function testHandlesInvalidPreferenceCenterIdGracefully(): void
    {
        $this->coreParametersHelper->expects($this->exactly(5))->method('get')->willReturnMap([
            ['email_default_preference_center_id', null, 999],
            ['email_default_utm_source', null, null],
            ['email_default_utm_medium', null, null],
            ['email_default_utm_campaign', null, null],
            ['email_default_utm_content', null, null],
        ]);

        $this->entityManager->method('find')
            ->with(Page::class, 999)
            ->willReturn(null);

        $email = new Email();
        $this->helper->applyDefaults($email);

        $this->assertNull($email->getPreferenceCenter());
    }

    public function testPreservesPreExistingChanges(): void
    {
        $email = new Email();
        $email->setName('Test Email');
        $changesBefore = $email->getChanges();
        // Verify the email has tracked changes before applying defaults
        $this->assertNotEmpty($changesBefore);

        $this->coreParametersHelper->expects($this->exactly(5))->method('get')->willReturnMap([
            ['email_default_preference_center_id', null, null],
            ['email_default_utm_source', null, 'src'],
            ['email_default_utm_medium', null, null],
            ['email_default_utm_campaign', null, null],
            ['email_default_utm_content', null, null],
        ]);

        $this->helper->applyDefaults($email);

        $this->assertSame($changesBefore, $email->getChanges());
        $this->assertSame(['utmSource' => 'src'], $email->getUtmTags());
    }
}
