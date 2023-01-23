<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\FormBundle\Collection\ObjectCollection;
use Mautic\FormBundle\Crate\ObjectCrate;
use Symfony\Contracts\EventDispatcher\Event;

final class ObjectCollectEvent extends CommonEvent
{
    private ObjectCollection $objects;

    public function __construct()
    {
        $this->objects = new ObjectCollection();
    }

    public function appendObject(ObjectCrate $object): void
    {
        $this->objects->append($object);
    }

    public function getObjects(): ObjectCollection
    {
        return $this->objects;
    }
}
