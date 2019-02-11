<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
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
     * @param array  $dictionary
     * @param string $timeframe
     */
    public function __construct(array $dictionary, $timeframe, $prefixes = [])
    {
        $this->dictionary = $dictionary;
        $this->timeframe  = trim(str_replace($prefixes, '', $timeframe));
        $this->prefix     = trim(str_replace([$this->getRelativeString(), $this->getRelativeDate()], '', $timeframe));
    }

    /**
     * @return bool
     */
    public function hasRelativeDate()
    {
        if (empty($this->timeframe)) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function hasDateFromDictionary()
    {
        return in_array($this->getRelativeString(), $this->getDictionaryVariants());
    }

    /**
     * Return timeframe.
     *
     * @return string
     */
    private function getRelativeString()
    {
        return trim(str_replace($this->getRelativeDate(), '', $this->timeframe));
    }

    /**
     * Return all after /birthday string, for example -1 day.
     *
     * @return string
     */
    public function getRelativeDate()
    {
        return trim(str_replace($this->getDictionaryVariants(), '', $this->timeframe));
    }

    /**
     * Return all possible variants for dates.
     *
     * @return array
     */
    private function getDictionaryVariants()
    {
        return array_merge($this->dictionary, array_keys($this->dictionary));
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }
}
