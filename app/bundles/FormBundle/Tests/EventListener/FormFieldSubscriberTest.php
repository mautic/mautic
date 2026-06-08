<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\EventListener;

use Mautic\FormBundle\EventListener\FormFieldSubscriber;
use Mautic\FormBundle\FormEvents;
use Mautic\FormBundle\Model\FieldModel;
use PHPUnit\Framework\TestCase;

final class FormFieldSubscriberTest extends TestCase
{
    /**
     * @var FormFieldSubscriber
     */
    private $subscriber;

    public function setUp(): void
    {
        parent::setUp();

        $fieldModel = $this->createMock(FieldModel::class);

        $this->subscriber = new FormFieldSubscriber($fieldModel);
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertEquals(
            [
                FormEvents::FIELD_POST_DELETE => ['onFieldPostDelete', 0],
            ],
            $this->subscriber::getSubscribedEvents()
        );
    }
}
