<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Form\FormBuilder;

class SmsPropertiesEvent extends Event
{
    /**
     * @var FormBuilder
     */
    private $formBuilder;

    /**
     * @var array
     */
    private $data;

    /**
     * @var array
     */
    private $fields = [];

    public function __construct(FormBuilder $builder, array $data)
    {
        $this->formBuilder = $builder;
        $this->data        = $data;
    }

    /**
     * Get the FormBuilder for monitored_mailboxes FormType.
     *
     * @return FormBuilder
     */
    public function getFormBuilder()
    {
        return $this->formBuilder;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param string      $child
     * @param string|null $type
     */
    public function addField($child, $type = null, array $options = [])
    {
        $this->fields[] = [
            'child'   => $child,
            'type'    => $type,
            'options' => $options,
        ];
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }
}
