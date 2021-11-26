<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Event;

use Mautic\FormBundle\Entity\Field;
use Symfony\Component\EventDispatcher\Event;

final class FormFieldEvent extends Event
{
    /**
     * @var Field
     */
    private $entity;

    /**
     * @var bool
     */
    private $isNew;

    public function __construct(Field $field, bool $isNew = false)
    {
        $this->entity = $field;
        $this->isNew  = $isNew;
    }

    public function getField(): Field
    {
        return $this->entity;
    }

    public function isNew(): bool
    {
        return $this->isNew;
    }

    public function setField(Field $field): void
    {
        $this->entity = $field;
    }
}
