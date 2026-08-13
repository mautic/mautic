<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Field\Notification;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\Notification;
use Mautic\CoreBundle\Entity\NotificationRepository;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Field\Notification\CustomFieldNotification;
use Mautic\LeadBundle\Model\FieldModel;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Group('non-parallel')]
final class CustomFieldNotificationFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    private TranslatorInterface $translator;

    private CustomFieldNotification $notifier;

    private LeadField $leadField;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = $this->getService(TranslatorInterface::class);
        $this->notifier   = $this->getService(CustomFieldNotification::class);
        $this->leadField  = $this->createCustomField();
    }

    public function testNoNotificationWhenLeadFieldCanNotUpdateForInvalidUser(): void
    {
        $this->notifier->customFieldCannotBeUpdated($this->leadField, -1);
        $notificationRepo   = $this->getService(NotificationRepository::class);
        /** @var Paginator<Notification> $notifications */
        $notifications      = $notificationRepo->getEntities([]);

        $this->assertCount(0, $notifications);
    }

    public function testNotificationForLeadFieldCanNotUpdate(): void
    {
        $this->notifier->customFieldCannotBeUpdated($this->leadField, 1);

        $notificationRepo   = $this->getService(NotificationRepository::class);
        $notifications      = $notificationRepo->getNotifications(1);
        $this->assertCount(1, $notifications);

        $notification = array_shift($notifications);
        $this->assertEquals($notification['header'], $this->translator->trans('mautic.lead.field.notification.cannot_be_updated_header'));
        $this->assertEquals($notification['message'], $this->translator->trans('mautic.lead.field.notification.cannot_be_updated_message', ['%label%' => $this->leadField->getLabel()]));
    }

    private function createCustomField(): LeadField
    {
        $field = new LeadField();
        $field->setType('text');
        $field->setObject('lead');
        $field->setGroup('core');
        $field->setLabel('Test field');
        $field->setAlias('custom_field_test');
        $field->setCharLengthLimit(64);

        $fieldModel = $this->getService(FieldModel::class);
        $fieldModel->saveEntity($field);
        $fieldModel->getRepository()->detachEntity($field);

        return $field;
    }
}
