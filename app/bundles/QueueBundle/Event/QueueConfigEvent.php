<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\QueueBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;

/**
 * Class QueueConfigEvent.
 */
class QueueConfigEvent extends CommonEvent
{
    /**
     * @var array
     */
    private $options;

    /**
     * @var array
     */
    private $formFields;

    /**
     * @var array
     */
    private $protocolChoices;

    /**
     * QueueConfigEvent constructor.
     *
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->options = $options;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return array
     */
    public function getFormFields()
    {
        return $this->formFields;
    }

    /**
     * @return string
     */
    public function getProtocolChoices()
    {
        return $this->protocolChoices;
    }

    /**
     * @param string      $child
     * @param string|null $type
     * @param array       $options
     */
    public function addFormField($child, $type = null, array $options = [])
    {
        $this->formFields[] = [
            'child'   => $child,
            'type'    => $type,
            'options' => $options,
        ];
    }

    /**
     * @param string $protocol
     * @param array  $uiTranslation
     */
    public function addProtocolChoice($protocol, $uiTranslation)
    {
        $this->protocolChoices[$protocol] = $uiTranslation;
    }
}
