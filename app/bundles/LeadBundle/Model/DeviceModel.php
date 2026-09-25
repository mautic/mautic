<?php

namespace Mautic\LeadBundle\Model;

use Mautic\CoreBundle\Model\FormModel;
use Mautic\LeadBundle\Entity\LeadDevice;
use Mautic\LeadBundle\Entity\LeadDeviceRepository;
use Mautic\LeadBundle\Event\DevicePostDeleteEvent;
use Mautic\LeadBundle\Event\DevicePostSaveEvent;
use Mautic\LeadBundle\Event\DevicePreDeleteEvent;
use Mautic\LeadBundle\Event\DevicePreSaveEvent;
use Mautic\LeadBundle\Form\Type\DeviceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @extends FormModel<LeadDevice>
 */
final class DeviceModel extends FormModel
{
    public static function getName(): string
    {
        return 'lead.device';
    }

    private LeadDeviceRepository $leadDeviceRepository;

    #[Required]
    public function autowireDeviceModel(
        LeadDeviceRepository $leadDeviceRepository,
    ): void {
        $this->leadDeviceRepository = $leadDeviceRepository;
    }

    public function getRepository(): LeadDeviceRepository
    {
        return $this->leadDeviceRepository;
    }

    public function getPermissionBase(): string
    {
        return 'lead:leads';
    }

    /**
     * Get a specific entity or generate a new one if id is empty.
     */
    public function getEntity($id = null): ?LeadDevice
    {
        if (null === $id) {
            return new LeadDevice();
        }

        return parent::getEntity($id);
    }

    /**
     * @param array $options
     */
    public function createForm($entity, $action = null, $options = []): FormInterface
    {
        if (!$entity instanceof LeadDevice) {
            throw new MethodNotAllowedHttpException(['LeadDevice']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $this->formFactory->create(DeviceType::class, $entity, $options);
    }

    /**
     * @throws MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, bool $isNew = false, ?Event $event = null): ?Event
    {
        if (!$entity instanceof LeadDevice) {
            throw new MethodNotAllowedHttpException(['LeadDevice']);
        }

        $event = match ($action) {
            'pre_save'    => new DevicePreSaveEvent($entity, $isNew),
            'post_save'   => new DevicePostSaveEvent($entity, $isNew),
            'pre_delete'  => new DevicePreDeleteEvent($entity, $isNew),
            'post_delete' => new DevicePostDeleteEvent($entity, $isNew),
            default       => null,
        };

        if (null === $event || !$this->dispatcher->hasListeners($event::class)) {
            return null;
        }

        $this->dispatcher->dispatch($event);

        return $event;
    }
}
