<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment\Decorator;

use Mautic\LeadBundle\Segment\Decorator\Date\DateOptionFactory;
use Mautic\LeadBundle\Segment\LeadSegmentFilterCrate;
use Mautic\LeadBundle\Services\LeadSegmentFilterDescriptor;

class DecoratorFactory
{
    /**
     * @var LeadSegmentFilterDescriptor
     */
    private $leadSegmentFilterDescriptor;
    /**
     * @var BaseDecorator
     */
    private $baseDecorator;

    /**
     * @var CustomMappedDecorator
     */
    private $customMappedDecorator;

    /**
     * @var DateOptionFactory
     */
    private $dateOptionFactory;

    public function __construct(
        LeadSegmentFilterDescriptor $leadSegmentFilterDescriptor,
        BaseDecorator $baseDecorator,
        CustomMappedDecorator $customMappedDecorator,
        DateOptionFactory $dateOptionFactory
    ) {
        $this->baseDecorator               = $baseDecorator;
        $this->customMappedDecorator       = $customMappedDecorator;
        $this->dateOptionFactory           = $dateOptionFactory;
        $this->leadSegmentFilterDescriptor = $leadSegmentFilterDescriptor;
    }

    /**
     * @param LeadSegmentFilterCrate $leadSegmentFilterCrate
     *
     * @return FilterDecoratorInterface
     */
    public function getDecoratorForFilter(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        if ($leadSegmentFilterCrate->isDateType()) {
            return $this->dateOptionFactory->getDateOption($leadSegmentFilterCrate);
        }

        $originalField = $leadSegmentFilterCrate->getField();

        if (empty($this->leadSegmentFilterDescriptor[$originalField])) {
            return $this->baseDecorator;
        }

        return $this->customMappedDecorator;
    }
}
