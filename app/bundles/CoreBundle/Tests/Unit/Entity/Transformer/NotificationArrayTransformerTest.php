<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
