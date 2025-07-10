<?php

namespace Mautic\LeadBundle\Event;

use Mautic\CategoryBundle\Entity\Category;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Contracts\EventDispatcher\Event;

class CategoryChangeEvent extends Event
{
    private ?Lead $lead = null;

    /**
     * @var Lead[]|null
     */
    private ?array $leads = null;

    /**
     * @param Lead|Lead[] $leads
     */
    public function __construct(
        Lead|array $leads,
        private Category $category,
        private bool $added = true,
    ) {
        if (is_array($leads)) {
            $this->leads = $leads;
        } else {
            $this->lead = $leads;
        }
    }

    /**
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

    public function wasAdded(): bool
    {
        return $this->added;
    }

    public function wasRemoved(): bool
    {
        return !$this->added;
    }
}
