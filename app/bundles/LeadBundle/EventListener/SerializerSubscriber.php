<?php

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use Mautic\LeadBundle\Entity\LeadField;
use Symfony\Component\HttpFoundation\RequestStack;

class SerializerSubscriber implements EventSubscriberInterface
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(
        RequestStack $requestStack
    ) {
        $this->requestStack = $requestStack;
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents()
    {
        return [
            [
                'event'  => Events::POST_SERIALIZE,
                'method' => 'changeEmptyArraysToObject',
            ],
        ];
    }

    public function changeEmptyArraysToObject(ObjectEvent $event)
    {
        $request  = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return;
        }

        $object = $event->getObject();

        if (!$object instanceof LeadField) {
            return;
        }

        if (empty($object->getProperties())) {
            // fixing array/object discrepancy for empty properties
            $event->getContext()->getVisitor()->setData('properties', new \ArrayObject());
        }
    }
}
