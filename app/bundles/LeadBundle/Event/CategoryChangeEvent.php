<?php

namespace Mautic\LeadBundle\Event;

use Mautic\CategoryBundle\Entity\Category;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class CategoryChangeEvent.
 */
class CategoryChangeEvent extends Event
{
    private $lead;
    private $leads;
    private $category;
    private $added;

    /**
     * CategoryChangeEvent constructor.
     *
     * @param      $leads
     * @param bool $added
     */
    public function __construct($leads, Category $category, $added = true)
    {
        if (is_array($leads)) {
            $this->leads = $leads;
        } else {
            $this->lead = $leads;
        }
        $this->category = $category;
        $this->added    = $added;
    }

    /**
     * Returns the Lead entity.
     *
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * Returns batch array of leads.
     *
     * @return array
     */
    public function getLeads()
    {
        return $this->leads;
    }

    /**
     * @return Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @return bool
     */
    public function wasAdded()
    {
        return $this->added;
    }

    /**
     * @return bool
     */
    public function wasRemoved()
    {
        return !$this->added;
    }
}
