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
        return in_array($this->getAnniversaryString($timeframe), $this->getAnniversaryTranslationsVariants());
    }

    /**
     * Return timeframe.
     *
     * @param $timeframe
     *
     * @return string
     */
    private function getAnniversaryString($timeframe)
    {
        return trim(str_replace($this->getAnniversaryRelativeDate($timeframe), '', $timeframe));
    }

    /**
     * Return all after anniversary/birthday string, for example -1 day.
     *
     * @param $filter
     *
     * @return string
     */
    public function getAnniversaryRelativeDate($filter)
    {
        return trim(str_replace($this->getAnniversaryTranslationsVariants(), '', $filter));
    }

    /**
     * Return all possible variants for anniversary - translations + basic.
     *
     * @return array
     */
    private function getAnniversaryTranslationsVariants()
    {
        return array_merge($this->getAnniversaryTranslations(), array_keys($this->getAnniversaryTranslations()));
    }

    /**
     * @return array
     */
    private function getAnniversaryTranslations()
    {
        return  [
            'anniversary' => $this->translator->trans('mautic.lead.list.anniversary'),
            'birthday'    => $this->translator->trans('mautic.lead.list.birthday'),
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
