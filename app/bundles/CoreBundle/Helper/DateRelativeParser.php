<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

class DateRelativeParser
{
    /** @var array */
    private $dictionary;

    /** @var string */
    private $timeframe;

    /** @var string */
    private $prefix;

    /**
     * DateRelativeParser constructor.
     *
     * @param string $timeframe
     */
    public function __construct(array $dictionary, $timeframe, $prefixes = [])
    {
        $this->dictionary = $dictionary;
        $this->timeframe  = trim(str_replace($prefixes, '', $timeframe));
        $this->prefix     = trim(str_replace([$this->getStringPart(), $this->getTimeframePart()], '', $timeframe));
    }

    /**
     * Relative date from dictionary or start with +/-.
     *
     * @return bool
     */
    public function hasRelativeDate()
    {
        if (empty($this->timeframe) || (!$this->hasDateFromDictionary() && !in_array(substr($this->timeframe, 0, 1), ['+', '-']))) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function hasDateFromDictionary()
    {
        return in_array($this->getStringPart(), $this->getDictionaryVariants());
    }

    /**
     * Return string part of time frame, for exampele: anniversary.
     *
     * @return string
     */
    private function getStringPart()
    {
        return trim(str_replace($this->getTimeframePart(), '', $this->timeframe));
    }

    /**
     * Return timeframe part, for example +1 day.
     *
     * @return string
     */
    public function getTimeframePart()
    {
        return trim(str_replace($this->getDictionaryVariants(), '', $this->timeframe));
    }

    /**
     * Return all possible variants with translations.
     *
     * @return array
     */
    private function getDictionaryVariants()
    {
        return array_merge($this->dictionary, array_keys($this->dictionary));
    }

    /**
     * Return date with current year.
     *
     * @param $value
     *
     * @return false|string
     */
    public function getAnniversaryDate($value)
    {
        switch ($this->prefix) {
            case 'datetime':
                return date(date('Y').'-m-d H:i:s', strtotime($value));
            case 'date':
                return date(date('Y').'-m-d', strtotime($value));
        }

        return $value;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }
}
