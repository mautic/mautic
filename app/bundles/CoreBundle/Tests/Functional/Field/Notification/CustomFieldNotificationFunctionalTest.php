<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Field\Notification;

use Mautic\CoreBundle\Entity\Notification;
use Mautic\CoreBundle\Entity\NotificationRepository;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Field\Notification\CustomFieldNotification;
use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Contracts\Translation\TranslatorInterface;

class CustomFieldNotificationFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;
    private TranslatorInterface $translator;
    private CustomFieldNotification $notifier;
    private LeadField $leadField;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = $this->getContainer()->get('translator');
        $this->notifier   = $this->getContainer()->get('mautic.lead.field.notification.custom_field');
        $this->leadField  = $this->createCustomField();
    }

    public function testNoNotificationWhenLeadFieldCanNotUpdateForInvalidUser(): void
    {
        $this->notifier->customFieldCannotBeUpdated($this->leadField, -1);
        /** @var NotificationRepository $notificationRepo */
        $notificationRepo   = $this->em->getRepository(Notification::class);
        $notifications      = $notificationRepo->getEntities([]);

        $this->assertEquals(0, $notifications->count());
    }

    public function testNotificationForLeadFieldCanNotUpdate(): void
    {
        $this->notifier->customFieldCannotBeUpdated($this->leadField, 1);

        /** @var NotificationRepository $notificationRepo */
        $notificationRepo   = $this->em->getRepository(Notification::class);
        $notifications      = $notificationRepo->getNotifications(1);
        $this->assertEquals(1, count($notifications));

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

        /** @var FieldModel $fieldModel */
        $fieldModel = $this->getContainer()->get('mautic.lead.model.field');
        $fieldModel->saveEntity($field);
        $fieldModel->getRepository()->detachEntity($field);

        return $field;
    }
}
