<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Field\Notification;

use Mautic\CoreBundle\Entity\Notification;
use Mautic\CoreBundle\Entity\NotificationRepository;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Field\Notification\CustomFieldNotification;
use Mautic\LeadBundle\Model\FieldModel;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Group('database')]
final class CustomFieldNotificationFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    private TranslatorInterface $translator;

    private CustomFieldNotification $notifier;

    private LeadField $leadField;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = $this->getContainer()->get(TranslatorInterface::class);
        $this->notifier   = $this->getContainer()->get(CustomFieldNotification::class);
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

        /** @var FieldModel $fieldModel */
        $fieldModel = $this->getContainer()->get(FieldModel::class);
        $fieldModel->saveEntity($field);
        $fieldModel->getRepository()->detachEntity($field);

        return $field;
    }
}
