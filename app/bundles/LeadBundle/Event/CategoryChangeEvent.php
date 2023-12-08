<?php

namespace Mautic\LeadBundle\Event;

use Mautic\CategoryBundle\Entity\Category;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Contracts\EventDispatcher\Event;

class CategoryChangeEvent extends Event
{
    private $lead;
    private ?array $leads = null;

    /**
     * CategoryChangeEvent constructor.
     *
     * @param bool $added
     */
    public function __construct($leads, private Category $category, private $added = true)
    {
        if (is_array($leads)) {
            $this->leads = $leads;
        } else {
            $this->lead = $leads;
        }
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

    public function wasRemoved(): bool
    {
        return !$this->added;
    }
}
