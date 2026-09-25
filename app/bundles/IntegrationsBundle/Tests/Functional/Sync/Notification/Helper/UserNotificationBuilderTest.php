<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Functional\Sync\Notification\Helper;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\IntegrationsBundle\Sync\Notification\Helper\UserNotificationBuilder;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\UserBundle\Entity\User;

final class UserNotificationBuilderTest extends MauticMysqlTestCase
{
    private const ADMIN_USER = 'admin';

    private const SALES_USER = 'sales';

    private UserNotificationBuilder $notificationBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notificationBuilder = self::getContainer()->get(UserNotificationBuilder::class);
    }

    public function testGetUserIdsWithNonExistentObject(): void
    {
        $user    = $this->getUser(self::ADMIN_USER);
        $this->assertInstanceOf(User::class, $user);
        $userIds = $this->notificationBuilder->getUserIds('lead', 253);

        $this->assertSame([$user->getId()], $userIds);
    }

    public function testGetUserIdsWithExistentObject(): void
    {
        $user = $this->getUser(self::SALES_USER);
        $this->assertInstanceOf(User::class, $user);

        $lead = new Lead();
        $lead->setOwner($user);
        $this->em->persist($lead);
        $this->em->flush();

        $userIds = $this->notificationBuilder->getUserIds('lead', $lead->getId());

        $this->assertSame([$user->getId()], $userIds);
    }

    public function testBuildLink(): void
    {
        $link = $this->notificationBuilder->buildLink('lead', 253, 'Some text');

        $this->assertSame('<a href="/s/contacts/view/253">Some text</a>', $link);
    }

    public function testFormatHeader(): void
    {
        $header = $this->notificationBuilder->formatHeader('Integration name', 'Lead');

        $this->assertSame('Issue encountered while syncing with the Integration name Lead object', $header);
    }

    public function testFormatMessage(): void
    {
        $header = $this->notificationBuilder->formatMessage('Some message', 'Some link');

        $this->assertSame('Some link failed to sync with message, &quot;Some message&quot;', $header);
    }
}
