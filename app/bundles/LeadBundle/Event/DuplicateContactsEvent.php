<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Event;

class DuplicateContactsEvent extends \Mautic\ChannelBundle\Event\ChannelEvent
{
    /**
     * @var array
     */
    private $fields;

    /**
     * @var array
     */
    private $duplicates = [];

    private $handledByPlugin = false;

    /**
     * CheckForDuplicateContactsEvent constructor.
     *
     * @param array $fields
     */
    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param array $fields
     */
    public function setFields($fields)
    {
        $this->fields = $fields;
    }

    /**
     * @return array
     */
    public function getDuplicates()
    {
        return $this->duplicates;
    }

    /**
     * @param array $duplicates
     */
    public function setDuplicates($duplicates)
    {
        $this->handledByPlugin = true;
        $this->duplicates      = $duplicates;
    }

    /**
     * @return bool
     */
    public function isHandledByPlugin()
    {
        return $this->handledByPlugin;
    }
}
