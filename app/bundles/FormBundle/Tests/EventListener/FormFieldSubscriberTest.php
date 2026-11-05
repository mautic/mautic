<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\EventListener;

use Mautic\CoreBundle\Test\ReflectionHelper;
use Mautic\FormBundle\EventListener\FormFieldSubscriber;
use Mautic\FormBundle\FormEvents;
use PHPUnit\Framework\TestCase;

final class FormFieldSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $expected = [
            [FormEvents::FIELD_POST_DELETE, 'onFieldPostDelete', 0],
        ];
        sort($expected);

        $this->assertSame($expected, ReflectionHelper::getAttributeListeners(FormFieldSubscriber::class));
    }
}
