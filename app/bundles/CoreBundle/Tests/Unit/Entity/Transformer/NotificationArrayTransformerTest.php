<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Entity\Transformer;

use Mautic\CoreBundle\Entity\Notification;
use Mautic\CoreBundle\Entity\Transformer\NotificationArrayTransformer;
use PHPUnit\Framework\TestCase;

final class NotificationArrayTransformerTest extends TestCase
{
    /**
     * @var NotificationArrayTransformer<Notification>
     */
    private NotificationArrayTransformer $notificationArrayTransformer;
    private Notification$notification;

    protected function setUp(): void
    {
        $this->notificationArrayTransformer = new NotificationArrayTransformer();
        $this->notification                 = new Notification();
    }

    public function testThatTransformWorks(): void
    {
        $notificationProperties = $this->notificationArrayTransformer->transform($this->notification);
        $this->assertNotEmpty($notificationProperties);
    }

    public function testThatReverseTransformWorks(): void
    {
        $notification = $this->notificationArrayTransformer->reverseTransform([]);
        $this->assertInstanceOf(Notification::class, $notification);
    }
}
