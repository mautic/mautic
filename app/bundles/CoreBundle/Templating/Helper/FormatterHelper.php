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

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\Templating\Helper\Helper;

/**
 * Class FormatHelper.
 */
class FormatterHelper extends Helper
{
    /**
     * @var DateHelper
     */
    private $dateHelper;
    private $version;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->dateHelper = $factory->getHelper('template.date');
        $this->version    = $factory->getVersion();
    }

    /**
     * Format a string.
     *
     * @param $val
     * @param $type
     */
    public function _($val, $type = 'html', $textOnly = false, $round = 1)
    {
        if (empty($val)) {
            return $val;
        }

        switch ($type) {
            case 'array':
                if (!is_array($val)) {
                    //assume that it's serialized
                    $unserialized = unserialize($val);
                    if ($unserialized) {
                        $val = $unserialized;
                    }
                }

                $stringParts = [];
                foreach ($val as $k => $v) {
                    if (is_array($v)) {
                        $stringParts = $this->_($v, 'array', $textOnly, $round + 1);
                    } else {
                        $stringParts[] = $v;
                    }
                }
                if ($round === 1) {
                    $string = implode('; ', $stringParts);
                } else {
                    $string = implode(', ', $stringParts);
                }
                break;
            case 'datetime':
                $string = $this->dateHelper->toFull($val, 'utc');
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
     * @return string
     */
    public function getName()
    {
        return 'formatter';
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }
}
