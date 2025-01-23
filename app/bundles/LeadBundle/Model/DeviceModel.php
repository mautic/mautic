<?php

namespace Mautic\LeadBundle\Model;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\LeadDevice;
use Mautic\LeadBundle\Entity\LeadDeviceRepository;
use Mautic\LeadBundle\Event\LeadDeviceEvent;
use Mautic\LeadBundle\Form\Type\DeviceType;
use Mautic\LeadBundle\LeadEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @extends FormModel<LeadDevice>
 */
class DeviceModel extends FormModel
{
    public function __construct(
        private LeadDeviceRepository $leadDeviceRepository,
        EntityManager $em,
        CorePermissions $security,
        EventDispatcherInterface $dispatcher,
        UrlGeneratorInterface $router,
        Translator $translator,
        UserHelper $userHelper,
        LoggerInterface $mauticLogger,
        CoreParametersHelper $coreParametersHelper,
    ) {
        parent::__construct($em, $security, $dispatcher, $router, $translator, $userHelper, $mauticLogger, $coreParametersHelper);
    }

    /**
     * @return LeadDeviceRepository
     */
    public function getRepository()
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
    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): \Symfony\Component\Form\FormInterface
    {
        if (!$entity instanceof LeadDevice) {
            throw new MethodNotAllowedHttpException(['LeadDevice']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create(DeviceType::class, $entity, $options);
    }

    /**
     * @throws MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null): ?Event
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
            if (empty($event)) {
                $event = new LeadDeviceEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($event, $name);

            return $event;
        } else {
            return null;
        }
    }
}
