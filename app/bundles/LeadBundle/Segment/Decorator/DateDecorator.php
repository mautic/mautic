<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment\Decorator;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\ContactSegmentFilterOperator;
use Mautic\LeadBundle\Services\ContactSegmentFilterDictionary;

/**
 * Class DateDecorator.
 */
class DateDecorator extends CustomMappedDecorator
{
    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * CustomMappedDecorator constructor.
     *
     * @param ContactSegmentFilterOperator   $contactSegmentFilterOperator
     * @param ContactSegmentFilterDictionary $contactSegmentFilterDictionary
     * @param CoreParametersHelper           $coreParametersHelper
     */
    public function __construct(
        ContactSegmentFilterOperator $contactSegmentFilterOperator,
        ContactSegmentFilterDictionary $contactSegmentFilterDictionary,
        CoreParametersHelper $coreParametersHelper
    ) {
        parent::__construct($contactSegmentFilterOperator, $contactSegmentFilterDictionary);
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
     * @throws \Exception
     */
    public function getParameterValue(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        throw new \Exception('Instance of Date option needs to implement this function');
    }

    /**
     * @param null|string $relativeDate
     *
     * @return DateTimeHelper
     */
    public function getDefaultDate($relativeDate = null)
    {
        $timezone = $this->coreParametersHelper->getParameter('default_timezone', 'local');

        if ($relativeDate) {
            return new DateTimeHelper($relativeDate, null, $timezone);
        } else {
            return new DateTimeHelper('midnight today', null, $timezone);
        }
    }
}
