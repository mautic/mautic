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

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\ContactSegmentFilterOperator;
use Mautic\LeadBundle\Services\ContactSegmentFilterDictionary;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class DateDecorator.
 */
class DateDecorator extends CustomMappedDecorator
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * DateDecorator constructor.
     *
     * @param ContactSegmentFilterOperator   $contactSegmentFilterOperator
     * @param ContactSegmentFilterDictionary $contactSegmentFilterDictionary
     * @param TranslatorInterface            $translator
     */
    public function __construct(
        ContactSegmentFilterOperator $contactSegmentFilterOperator,
        ContactSegmentFilterDictionary $contactSegmentFilterDictionary, TranslatorInterface $translator
    ) {
        parent::__construct($contactSegmentFilterOperator, $contactSegmentFilterDictionary);
        $this->translator = $translator;
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
     * @param $timeframe
     *
     * @return bool
     */
    public function hasAnniversaryDate($timeframe)
    {
        return $timeframe && (
                false !== strpos($timeframe, $this->getAnniversaryTranslation('anniversary')) ||
                false !== strpos($timeframe, $this->getAnniversaryTranslation('birthday'))
            );
    }

    public function getAnniversaryDateFilter($filter)
    {
        return trim(str_replace($this->getAnniversaryTranslations(), '', $filter));
    }

    /**
     * @param string $key
     *
     * @return string
     */
    private function getAnniversaryTranslation($key)
    {
        if (isset($this->getAnniversaryTranslations()[$key])) {
            return $this->getAnniversaryTranslations()[$key];
        }
    }

    /**
     * @return array
     */
    private function getAnniversaryTranslations()
    {
        return  [
            'anniversary' => $this->translator->trans('mautic.lead.list.anniversary'),
            'birthday'    => $this->translator->trans('mautic.lead.list.anniversary'),
        ];
    }

    /**
     * @param null|string $relativeDate
     *
     * @return DateTimeHelper
     */
    public function getDefaultDate($relativeDate = null)
    {
        if ($relativeDate) {
            return new DateTimeHelper($relativeDate, null, 'local');
        } else {
            return new DateTimeHelper('midnight today', null, 'local');
        }
    }
}
