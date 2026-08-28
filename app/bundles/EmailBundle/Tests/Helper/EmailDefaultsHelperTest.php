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

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
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
        $this->coreParametersHelper->expects($this->exactly(4))->method('get')->willReturnMap([
            ['email_default_utm_source', null, 'config-source'],
            ['email_default_utm_medium', null, 'config-medium'],
            ['email_default_utm_campaign', null, 'config-campaign'],
            ['email_default_utm_content', null, 'config-content'],
        ]);

        $this->entityManager->expects($this->never())->method('find');

        $email = new Email();
        $this->helper->applyDefaults($email);

        $this->assertNotInstanceOf(Page::class, $email->getPreferenceCenter());
        $this->assertSame([
            'utmSource'   => 'config-source',
            'utmMedium'   => 'config-medium',
            'utmCampaign' => 'config-campaign',
            'utmContent'  => 'config-content',
        ], $email->getUtmTags());
    }

    public function testResolvePreferenceCenterReturnsExplicitPreferenceCenter(): void
    {
        $existingPage = $this->createPreferenceCenterPageMock(true);

        $email = new Email();
        $email->setPreferenceCenter($existingPage);

        $this->entityManager->expects($this->never())->method('find');
        $this->coreParametersHelper->expects($this->never())->method('get');

        $this->assertSame($existingPage, $this->helper->resolvePreferenceCenter($email));
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

        $this->coreParametersHelper->expects($this->never())->method('get');

        $this->helper->applyDefaults($email);

        $this->assertSame($existingUtmTags, $email->getUtmTags());
    }

    public function testAppliesDefaultsWhenUtmTagsContainOnlyNullValues(): void
    {
        $email = new Email();
        $email->setUtmTags([
            'utmSource'   => null,
            'utmMedium'   => null,
            'utmCampaign' => null,
            'utmContent'  => null,
        ]);

        $this->coreParametersHelper->expects($this->exactly(4))->method('get')->willReturnMap([
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
        $this->coreParametersHelper->expects($this->exactly(4))->method('get')->willReturnMap([
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
        $this->coreParametersHelper->expects($this->exactly(4))->method('get')->willReturn(null);
        $this->entityManager->expects($this->never())->method('find');

        $email = new Email();
        $this->helper->applyDefaults($email);

        $this->assertNotInstanceOf(Page::class, $email->getPreferenceCenter());
        $this->assertEmpty($email->getUtmTags());
    }

    public function testResolvePreferenceCenterReturnsConfiguredDefaultForEmailsWithoutExplicitPreferenceCenter(): void
    {
        $page = $this->createPreferenceCenterPageMock(true);

        $this->coreParametersHelper->expects($this->once())->method('get')->with('email_default_preference_center_id')->willReturn(42);

        $this->entityManager->expects($this->once())->method('find')
            ->with(Page::class, 42)
            ->willReturn($page);

        $email = new Email();

        $this->assertSame($page, $this->helper->resolvePreferenceCenter($email));
        $this->assertNotInstanceOf(Page::class, $email->getPreferenceCenter());
    }

    public function testResolvePreferenceCenterReturnsNullForInvalidDefault(): void
    {
        $this->coreParametersHelper->expects($this->once())->method('get')->with('email_default_preference_center_id')->willReturn(999);

        $this->entityManager->expects($this->once())->method('find')
            ->with(Page::class, 999)
            ->willReturn(null);

        $email = new Email();

        $this->assertNotInstanceOf(Page::class, $this->helper->resolvePreferenceCenter($email));
    }

    public function testResolvePreferenceCenterReturnsNullForUnpublishedDefault(): void
    {
        $page = $this->createPreferenceCenterPageMock(false);

        $this->coreParametersHelper->expects($this->once())->method('get')->with('email_default_preference_center_id')->willReturn(42);

        $this->entityManager->expects($this->once())->method('find')
            ->with(Page::class, 42)
            ->willReturn($page);

        $email = new Email();

        $this->assertNotInstanceOf(Page::class, $this->helper->resolvePreferenceCenter($email));
    }

    public function testResolvePreferenceCenterReturnsNullForPublishedNonPreferenceCenterDefault(): void
    {
        $page = $this->createPreferenceCenterPageMock(true, false);

        $this->coreParametersHelper->expects($this->once())->method('get')->with('email_default_preference_center_id')->willReturn(42);

        $this->entityManager->expects($this->once())->method('find')
            ->with(Page::class, 42)
            ->willReturn($page);

        $email = new Email();

        $this->assertNotInstanceOf(Page::class, $this->helper->resolvePreferenceCenter($email));
    }

    public function testPreservesPreExistingChanges(): void
    {
        $email = new Email();
        $email->setName('Test Email');
        $changesBefore = $email->getChanges();
        $this->assertNotEmpty($changesBefore);

        $this->coreParametersHelper->expects($this->exactly(4))->method('get')->willReturnMap([
            ['email_default_utm_source', null, 'src'],
            ['email_default_utm_medium', null, null],
            ['email_default_utm_campaign', null, null],
            ['email_default_utm_content', null, null],
        ]);

        $this->helper->applyDefaults($email);

        $this->assertSame($changesBefore, $email->getChanges());
        $this->assertSame(['utmSource' => 'src'], $email->getUtmTags());
    }

    private function createPreferenceCenterPageMock(bool $published, bool $isPreferenceCenter = true): Page&MockObject
    {
        $page = $this->createMock(Page::class);
        $page->method('getIsPreferenceCenter')->willReturn($isPreferenceCenter);
        $page->method('isPublished')->willReturn($published);

        return $page;
    }
}
