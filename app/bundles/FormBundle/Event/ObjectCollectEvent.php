<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Event;

use Mautic\FormBundle\Collection\ObjectCollection;
use Mautic\FormBundle\Crate\ObjectCrate;
use Symfony\Component\EventDispatcher\Event;

final class ObjectCollectEvent extends Event
{
    /**
     * @var ObjectCollection
     */
    private $objects;

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
