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

use Mautic\FormBundle\Collection\FieldCollection;
use Mautic\FormBundle\Crate\FieldCrate;
use Symfony\Component\EventDispatcher\Event;

final class FieldCollectEvent extends Event
{
    /**
     * @var string
     */
    private $object;

    /**
     * @var FieldCollection
     */
    private $fields;

    public function __construct(string $object)
    {
        $this->object = $object;
        $this->fields = new FieldCollection();
    }

    public function getObject(): string
    {
        return $this->object;
    }

    public function appendField(FieldCrate $field): void
    {
        $this->fields->append($field);
    }

    public function getFields(): FieldCollection
    {
        return $this->fields;
    }
}
