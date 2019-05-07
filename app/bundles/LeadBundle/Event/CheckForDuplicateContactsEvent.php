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

class CheckForDuplicateContactsEvent extends \Mautic\ChannelBundle\Event\ChannelEvent
{
    /**
     * @var array
     */
    private $fields;

    private $duplicates = [];

    /**
     * @var array
     */
    private $uniqueFields;

    /**
     * CheckForDuplicateContactsEvent constructor.
     *
     * @param array $fields
     * @param array $uniqueFields
     */
    public function __construct(array $fields, array $uniqueFields)
    {
        $this->fields       = $fields;
        $this->uniqueFields = $uniqueFields;
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
    public function getUniqueFields()
    {
        return $this->uniqueFields;
    }

    /**
     * @param array $uniqueFields
     */
    public function setUniqueFields($uniqueFields)
    {
        $this->uniqueFields = $uniqueFields;
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
        $this->duplicates = $duplicates;
    }
}
