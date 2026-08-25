<?php

namespace Mautic\LeadBundle\Model;

use Mautic\CoreBundle\Model\FormModel;
use Mautic\LeadBundle\Entity\LeadDevice;
use Mautic\LeadBundle\Entity\LeadDeviceRepository;
use Mautic\LeadBundle\Event\LeadDeviceEvent;
use Mautic\LeadBundle\Form\Type\DeviceType;
use Mautic\LeadBundle\LeadEvents;
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
    protected function dispatchEvent($action, &$entity, $isNew = false, ?Event $event = null): ?Event
    {
        if (!$entity instanceof LeadDevice) {
            throw new MethodNotAllowedHttpException(['LeadDevice']);
        }

        switch ($action) {
            case 'pre_save':
                $name = LeadEvents::DEVICE_PRE_SAVE;
                break;
            case 'post_save':
                $name = LeadEvents::DEVICE_POST_SAVE;
                break;
            case 'pre_delete':
                $name = LeadEvents::DEVICE_PRE_DELETE;
                break;
            case 'post_delete':
                $name = LeadEvents::DEVICE_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (!$event instanceof Event) {
                $event = new LeadDeviceEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($event, $name);

            return $event;
        }

        return null;
    }
}
