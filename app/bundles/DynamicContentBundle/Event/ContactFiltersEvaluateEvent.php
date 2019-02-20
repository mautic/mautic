<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic inc.
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DynamicContentBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\Lead;

class ContactFiltersEvaluateEvent extends CommonEvent
{
    /** @var array */
    private $filters;

    /** @var bool */
    private $isEvaluated = false;

    /** @var Lead */
    private $contact;

    /** @var bool */
    private $isMatched = false;

    /**
     * ContactFiltersEvaluateEvent constructor.
     *
     * @param array $filters
     * @param Lead  $contact
     */
    public function __construct(array $filters, Lead $contact)
    {
        $this->filters = $filters;
        $this->contact = $contact;
    }

    /**
     * @return bool
     */
    public function isMatch(): bool
    {
        return $this->isEvaluated() ? $this->isMatched : false;
    }

    /**
     * @return bool
     */
    public function isEvaluated(): bool
    {
        return $this->isEvaluated;
    }

    /**
     * @param bool $evaluated
     *
     * @return ContactFiltersEvaluateEvent
     */
    public function setIsEvaluated(bool $evaluated): ContactFiltersEvaluateEvent
    {
        $this->isEvaluated = $evaluated;

        return $this;
    }

    /**
     * @return Lead
     */
    public function getContact(): Lead
    {
        return $this->contact;
    }

    /**
     * @param Lead $contact
     *
     * @return ContactFiltersEvaluateEvent
     */
    public function setContact(Lead $contact): ContactFiltersEvaluateEvent
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @return bool
     */
    public function isMatched(): bool
    {
        return $this->isMatched;
    }

    /**
     * @param bool $isMatched
     *
     * @return ContactFiltersEvaluateEvent
     */
    public function setIsMatched(bool $isMatched): ContactFiltersEvaluateEvent
    {
        $this->isMatched = $isMatched;

        return $this;
    }
}
