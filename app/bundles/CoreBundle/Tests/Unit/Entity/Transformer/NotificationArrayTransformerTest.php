<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Entity\Transformer;

use Mautic\CoreBundle\Entity\Notification;
use Mautic\CoreBundle\Entity\Transformer\NotificationArrayTransformer;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class NotificationArrayTransformerTest extends TestCase
{
    /**
     * @var NotificationArrayTransformer
     */
    private $notificationArrayTransformer;

    /**
     * @var Notification
     */
    private $notification;

    protected function setUp(): void
    {
        $this->notificationArrayTransformer = new NotificationArrayTransformer();
        $this->notification                 = new Notification();
    }

    public function testThatTransformWorks(): void
    {
        $notificationProperties = $this->notificationArrayTransformer->transform($this->notification);
        Assert::assertIsArray($notificationProperties);
        Assert::assertNotEmpty($notificationProperties);
    }

    public function testThatReverseTransformWorks(): void
    {
        $notification = $this->notificationArrayTransformer->reverseTransform([]);
        Assert::assertInstanceOf(Notification::class, $notification);
    }
}
