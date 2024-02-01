<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Functional\Sync\Notification\Helper;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\IntegrationsBundle\Sync\Notification\Helper\UserNotificationBuilder;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Assert;

class UserNotificationBuilderTest extends MauticMysqlTestCase
{
    /**
     * @var UserNotificationBuilder
     */
    private $notificationBuilder;

    public function setUp(): void
    {
        parent::setUp();

        $this->notificationBuilder = static::getContainer()->get('mautic.integrations.sync.notification.user_notification_builder');
    }

    public function testGetUserIdsWithNonExistentObject(): void
    {
        $userIds = $this->notificationBuilder->getUserIds('lead', 253);

        Assert::assertSame([1], $userIds);
    }

    public function testGetUserIdsWithExistentObject(): void
    {
        $user = $this->em->find(User::class, 2);
        $lead = new Lead();
        $lead->setOwner($user);
        $this->em->persist($lead);
        $this->em->flush();

        $userIds = $this->notificationBuilder->getUserIds('lead', (int) $lead->getId());

        Assert::assertSame([$user->getId()], $userIds);
    }

    public function testBuildLink(): void
    {
        $link = $this->notificationBuilder->buildLink('lead', 253, 'Some text');

        Assert::assertSame('<a href="/s/contacts/view/253">Some text</a>', $link);
    }

    public function testFormatHeader(): void
    {
        $header = $this->notificationBuilder->formatHeader('Integration name', 'Lead');

        Assert::assertSame('Issue encountered while syncing with the Integration name Lead object', $header);
    }

    public function testFormatMessage(): void
    {
        $header = $this->notificationBuilder->formatMessage('Some message', 'Some link');

        Assert::assertSame('Some link failed to sync with message, &quot;Some message&quot;', $header);
    }
}
