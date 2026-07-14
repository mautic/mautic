<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\EventListener;

use Mautic\FormBundle\EventListener\FormFieldSubscriber;
use Mautic\FormBundle\FormEvents;
use Mautic\FormBundle\Model\FieldModel;
use PHPUnit\Framework\TestCase;

final class FormFieldSubscriberTest extends TestCase
{
    private FormFieldSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subscriber = new FormFieldSubscriber($this->createStub(FieldModel::class));
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertSame(
            [
                FormEvents::FIELD_POST_DELETE => ['onFieldPostDelete', 0],
            ],
            $this->subscriber::getSubscribedEvents()
        );
    }
}
