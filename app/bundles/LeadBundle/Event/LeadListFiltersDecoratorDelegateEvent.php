<?php
/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Decorator\FilterDecoratorInterface;

class LeadListFiltersDecoratorDelegateEvent extends CommonEvent
{
    /**
     * @var FilterDecoratorInterface
     */
    private $decorator;

    /**
     * @var ContactSegmentFilterCrate
     */
    private $crate;

    /**
     * LeadListFiltersDecoratorDelegateEvent constructor.
     *
     * @param ContactSegmentFilterCrate $crate
     */
    public function __construct(ContactSegmentFilterCrate $crate)
    {
        $this->crate = $crate;
    }

    /**
     * @return FilterDecoratorInterface|null
     */
    public function getDecorator(): ?FilterDecoratorInterface
    {
        return $this->decorator;
    }

    /**
     * @param FilterDecoratorInterface $decorator
     *
     * @return LeadListFiltersDecoratorDelegateEvent
     */
    public function setDecorator(FilterDecoratorInterface $decorator): LeadListFiltersDecoratorDelegateEvent
    {
        $this->decorator = $decorator;

        return $this;
    }

    /**
     * @return ContactSegmentFilterCrate
     */
    public function getCrate(): ContactSegmentFilterCrate
    {
        return $this->crate;
    }
}
