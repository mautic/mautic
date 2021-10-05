<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Templating\Helper;

use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\Serializer;
use Symfony\Component\Templating\Helper\Helper;
use Symfony\Component\Translation\TranslatorInterface;

class FormatterHelper extends Helper
{
    /**
     * @var DateHelper
     */
    private $dateHelper;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(DateHelper $dateHelper, TranslatorInterface $translator)
    {
        $this->dateHelper = $dateHelper;
        $this->translator = $translator;
    }

    /**
     * Format a string.
     *
     * @param $val
     * @param string $type
     * @param bool   $textOnly
     * @param int    $round
     *
     * @return string
     */
    public function _($val, $type = 'html', $textOnly = false, $round = 1)
    {
        if (empty($val) && 'bool' !== $type) {
            return $val;
        }

        switch ($type) {
            case 'array':
                if (!is_array($val)) {
                    //assume that it's serialized
                    $unserialized = Serializer::decode($val);
                    if ($unserialized) {
                        $val = $unserialized;
                    }
                }

                $stringParts = [];
                foreach ($val as $v) {
                    if (is_array($v)) {
                        $stringParts = $this->_($v, 'array', $textOnly, $round + 1);
                    } else {
                        $stringParts[] = $v;
                    }
                }
                if (1 === $round) {
                    $string = implode('; ', $stringParts);
                } else {
                    $string = implode(', ', $stringParts);
                }
                break;
            case 'datetime':
                $string = $this->dateHelper->toFullConcat($val, 'utc');
                break;
            case 'time':
                $string = $this->dateHelper->toTime($val, 'utc');
                break;
            case 'date':
                $string = $this->dateHelper->toDate($val, 'utc');
                break;
            case 'url':
                $string = ($textOnly) ? $val : '<a href="'.$val.'" target="_new">'.$val.'</a>';
                break;
            case 'email':
                $string = ($textOnly) ? $val : '<a href="mailto:'.$val.'">'.$val.'</a>';
                break;
            case 'int':
                $string = (int) $val;
                break;
            case 'html':
                $string = InputHelper::strict_html($val);
                break;
            case 'bool':
                $translate = $val ? 'mautic.core.yes' : 'mautic.core.no';
                $string    = $this->translator->trans($translate);
                break;
            default:
                $string = InputHelper::clean($val);
                break;
        }

        return $string;
    }

    /**
     * Converts array to string with provided delimiter
     * Internally, the method uses conversion to json
     * instead of simple implode to cover multidimensional arrays.
     *
     * @param mixed  $array
     * @param string $delimiter
     *
     * @return string
     */
    public function arrayToString($array, $delimiter = ', ')
    {
        if (is_array($array)) {
            $replacements = [
                '{'    => '(',
                '}'    => ')',
                '"'    => '',
                ','    => $delimiter,
                '[]'   => 'undefined',
                'null' => 'undefined',
                ':'    => ' = ',
            ];
            $json = json_encode($array);

            return trim(str_replace(array_keys($replacements), array_values($replacements), $json), '()[]');
        }

        return $array;
    }

    /**
     * @param string $delimeter
     *
     * @return string
     */
    public function simpleArrayToHtml(array $array, $delimeter = '<br />')
    {
        $pairs = [];
        foreach ($array as $key => $value) {
            $pairs[] = "$key: $value";
        }

        return implode($delimeter, $pairs);
    }

    /**
     * Takes a simple csv list like 1,2,3,4 and returns as an array.
     *
     * @param $csv
     *
     * @return array
     */
    public function simpleCsvToArray($csv, $type = null)
    {
        if (!$csv) {
            return [];
        }

        return array_map(
            function ($value) use ($type) {
                $value = trim($value);

                return $this->_($value, $type);
            },
            explode(',', $csv)
        );
    }

    /**
     * Takes a string and returns a normalized representation of it.
     *
     * @param $string string
     *
     * @return string
     */
    public function normalizeStringValue($string)
    {
        $stringIsDate = \DateTime::createFromFormat('Y-m-d H:i:s', $string, new \DateTimeZone('UTC'));

        if ($stringIsDate && ($string === $stringIsDate->format('Y-m-d H:i:s'))) {
            return $this->_($stringIsDate, 'datetime');
        }

        return $this->_($string);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'formatter';
    }
}
