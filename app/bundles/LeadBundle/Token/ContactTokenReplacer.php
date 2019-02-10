<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Token;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Token\TokenReplacer;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\Translation\TranslatorInterface;

class ContactTokenReplacer extends TokenReplacer
{
    private $tokenList = [];

    /** @var array */
    private $regex = ['{contactfield=(.*?)}', '{leadfield=(.*?)}'];

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * @var DateTimeHelper
     */
    private $dateTimeHelper;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param CoreParametersHelper $coreParametersHelper
     * @param DateTimeHelper       $dateTimeHelper
     * @param TranslatorInterface  $translator
     */
    public function __construct(
        CoreParametersHelper $coreParametersHelper,
        DateTimeHelper $dateTimeHelper,
        TranslatorInterface $translator
    ) {
        $this->coreParametersHelper = $coreParametersHelper;
        $this->dateTimeHelper       = $dateTimeHelper;
        $this->translator           = $translator;
    }

    /**
     * @param string          $content
     * @param array|Lead|null $options
     *
     * @return array
     */
    public function getTokens($content, $options = null)
    {
        foreach ($this->searchTokens($content, $this->regex) as $token => $tokenAttribute) {
            $this->tokenList[$token] = $this->getContactTokenValue(
                $options,
                $tokenAttribute->getAlias(),
                $tokenAttribute->getModifier()
            );
        }

        return $this->tokenList;
    }

    /**
     * @param array  $fields
     * @param string $alias
     * @param string $modifier
     *
     * @return mixed|string
     */
    private function getContactTokenValue(array $fields, $alias, $modifier)
    {
        $value = '';
        if (isset($fields[$alias])) {
            $value = $fields[$alias];
        } elseif (isset($fields['companies'][0][$alias])) {
            $value = $fields['companies'][0][$alias];
        }

        if ($value) {
            switch ($modifier) {
                case 'true':
                    $value = urlencode($value);
                    break;
                case 'datetime':
                case 'date':
                case 'time':
                case $modifier && $this->hasAnniversary($modifier):
                    $this->dateTimeHelper->setDateTime($value);
                    $date = $this->dateTimeHelper->getString(
                        $this->coreParametersHelper->getParameter('date_format_dateonly')
                    );
                    $time = $this->dateTimeHelper->getDateTime()->format(
                        $this->coreParametersHelper->getParameter('date_format_timeonly')
                    );
                    switch ($modifier) {
                        case 'datetime':
                            $value = $date.' '.$time;
                            break;
                        case 'date':
                            $value = $date;
                            break;
                        case 'time':
                            $value = $time;
                            break;
                        case $modifier && $this->hasAnniversary($modifier):
                            break;
                    }
                    break;
            }
        }
        if (in_array($modifier, ['true', 'date', 'time', 'datetime'])) {
            return $value;
        } else {
            return $value ?: $modifier;
        }
    }

    /**
     * @param $modifier
     *
     * @return bool
     */
    private function hasAnniversary($modifier)
    {
        foreach ($this->getAnniversaryDictionary() as $string) {
            if (false !== strpos($modifier, 'date '.$string)) {
                return true;
            }
        }

        return false;
    }

    private function getAnniversaryPart($timeframe)
    {
        return  trim(str_replace($this->getAnniversaryRelativeDate($timeframe), '', $timeframe));
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
        return trim(str_replace($this->getAnniversaryDictionary(), '', $filter));
    }

    /**
     * @return array
     */
    private function getAnniversaryDictionary()
    {
        return [
            'anniversary',
            $this->translator->trans('mautic.lead.list.anniversary'),
        ];
    }

    /**
     * @return array
     */
    public function getRegex()
    {
        return $this->regex;
    }
}
