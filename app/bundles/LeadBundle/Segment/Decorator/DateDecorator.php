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
use Mautic\CoreBundle\Helper\DateRelativeParser;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\ContactSegmentFilterOperator;
use Mautic\LeadBundle\Services\ContactSegmentFilterDictionary;
use Mautic\LeadBundle\Services\DateAnniversaryDictionary;

class DateDecorator extends CustomMappedDecorator
{
    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * @var DateAnniversaryDictionary
     */
    private $anniversaryDictionary;

    /**
     * CustomMappedDecorator constructor.
     *
     * @param DateAnniversaryDictionary $translator
     */
    public function __construct(
        ContactSegmentFilterOperator $contactSegmentFilterOperator,
        ContactSegmentFilterDictionary $contactSegmentFilterDictionary,
        CoreParametersHelper $coreParametersHelper,
        DateAnniversaryDictionary $anniversaryDictionary
    ) {
        parent::__construct($contactSegmentFilterOperator, $contactSegmentFilterDictionary);
        $this->coreParametersHelper  = $coreParametersHelper;
        $this->anniversaryDictionary = $anniversaryDictionary;
    }

    /**
     * @throws \Exception
     */
    public function getParameterValue(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        throw new \Exception('Instance of Date option needs to implement this function');
    }

    /**
     * @param $timeframe
     *
     * @return DateRelativeParser
     */
    public function dateRelativeParser($timeframe)
    {
        return new DateRelativeParser($this->anniversaryDictionary->getTranslations(), $timeframe);
    }

    /**
     * @param string|null $relativeDate
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
