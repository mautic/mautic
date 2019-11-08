<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Form\FormInterface;

class FilterPropertiesTypeEvent extends Event
{
    /**
     * @var FormInterface
     */
    private $form;

    /**
     * @var string
     */
    private $fieldName;

    /**
     * @var string
     */
    private $fieldObject;

    public function __construct(FormInterface $form, string $fieldName, string $fieldObject)
    {
        $this->form        = $form;
        $this->fieldName   = $fieldName;
        $this->fieldObject = $fieldObject;
    }

    public function getFilterPropertiesForm(): FormInterface
    {
        return $this->form;
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    public function getFieldObject(): string
    {
        return $this->fieldObject;
    }
}
