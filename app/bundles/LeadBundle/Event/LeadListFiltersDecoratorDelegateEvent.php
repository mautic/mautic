<?php
/*
 * @copyright  2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
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
    public function getDecorator()
    {
        return $this->decorator;
    }

    /**
     * @param FilterDecoratorInterface $decorator
     *
     * @return LeadListFiltersDecoratorDelegateEvent
     */
    public function setDecorator(FilterDecoratorInterface $decorator)
    {
        $this->decorator = $decorator;

        return $this;
    }

    /**
     * @return ContactSegmentFilterCrate
     */
    public function getCrate()
    {
        return $this->crate;
    }
}
