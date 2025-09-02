<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\ApiPlatform\EventListener;

use ApiPlatform\Symfony\EventListener\EventPriorities;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class MauticWriteSubscriber implements EventSubscriberInterface
{
    public function __construct(private UserHelper $userHelper)
    {
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['addData', EventPriorities::PRE_WRITE],
        ];
    }

    public function addData(ViewEvent $event): void
    {
        $entity = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();

        if (!$entity instanceof FormEntity
            || (
                Request::METHOD_POST !== $method
                && Request::METHOD_PATCH !== $method
                && Request::METHOD_PUT !== $method
            )
        ) {
            return;
        }

        $user = $this->userHelper->getUser();
        $now  = new DateTimeHelper();

        if ($entity->isNew()) {
            $entity->setDateAdded($now->getUtcDateTime());
            if ($user) {
                $entity->setCreatedBy($user);
                $entity->setCreatedByUser($user->getName());
            }
        }

        $entity->setDateModified($now->getUtcDateTime());
        if ($user) {
            $entity->setModifiedBy($user);
            $entity->setModifiedByUser($user->getName());
        }
    }
}
