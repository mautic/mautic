<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

use Symfony\Component\Translation\TranslatorInterface;

abstract class AbstractFormFieldHelper
{
    /**
     * Json encoded format.
     */
    const FORMAT_JSON = 'json';

    /**
     * Bar format value1|value2.
     */
    const FORMAT_BAR = 'bar';

    /**
     * Simple value => label array.
     */
    const FORMAT_SIMPLE_ARRAY = 'simple_array';

    /**
     * Array [['value' => 'value', 'label' => 'label'] ..].
     */
    const FORMAT_ARRAY = 'array';

    /**
     * @var
     */
    protected $translationKeyPrefix;

    /**
     * @var
     */
    protected $translator;

    /**
     * @return mixed
     */
    abstract public function setTranslationKeyPrefix();

    /**
     * @return mixed
     */
    abstract public function getTypes();

    /**
     * AbstractFormFieldHelper constructor.
     */
    public function __construct()
    {
        $this->setTranslationKeyPrefix();
    }

    /**
     * Set translator.
     *
     * @param TranslatorInterface $translator
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param array $customFields
     *
     * @return array
     */
    public function getChoiceList($customFields = [])
    {
        $choices = [];

        foreach ($this->getTypes() as $v => $type) {
            $choices[$v] = $this->translator->transConditional("mautic.core.type.{$v}", "{$this->translationKeyPrefix}{$v}");
        }

        foreach ($customFields as $v => $f) {
            $choices[$v] = $this->translator->trans($f['label']);
        }

        natcasesort($choices);

        return $choices;
    }

    /**
     * Format a string into an array.
     *
     * @param      $list
     * @param bool $removeEmpty
     *
     * @return array
     */
    public static function parseList($list, $removeEmpty = true, $ignoreNumerical = false)
    {
        // Note if this was an array to start and if we need to determine if the keys are sequentially numerical
        // for BC purposes
        $checkNumericalKeys = true;
        if (!is_array($list)) {
            // Try to json decode first
            if (strpos($list, '{') === 0 && $json = json_decode($list, true)) {
                $list = $json;
            } else {
                if (strpos($list, '|') !== false) {
                    $checkNumericalKeys = false;
                    $parts              = explode('||', $list);
                    if (count($parts) > 1) {
                        $labels = explode('|', $parts[0]);
                        $values = explode('|', $parts[1]);
                        $list   = array_combine($values, $labels);
                    } else {
                        $labels = explode('|', $list);
                        $values = $labels;
                        $list   = array_combine($values, $labels);
                    }
                } elseif (!empty($list) && !is_array($list)) {
                    $list = [$list => $list];
                }
            }
        }

        if (!$ignoreNumerical && $checkNumericalKeys && isset($list[0]) && !is_array($list[0]) && array_keys($list) === range(0, count($list) - 1)) {
            // Numberical array so set labels as values
            $list = array_combine($list, $list);
        }

        $valueFormatting = function ($list) use ($removeEmpty) {
            $choices = [];
            foreach ($list as $val => $label) {
                if (is_array($label) && isset($label['value'])) {
                    $val   = $label['value'];
                    $label = $label['label'];
                }
                if ($removeEmpty && empty($val) && empty($label)) {
                    continue;
                } elseif (empty($label)) {
                    $label = $val;
                }
                if (!is_array($label)) {
                    $choices[trim(html_entity_decode($val, ENT_QUOTES))] = trim(html_entity_decode($label, ENT_QUOTES));
                }
            }

            return $choices;
        };

        $formatList = $list;
        $choices    = [];

        if (is_array($list)) {
            foreach ($list as $val => $label) {
                if (is_array($label) && !isset($label['label'])) {
                    $choices[$val] = $valueFormatting($label);
                    unset($formatList[$val]);
                }
            }
            if (!empty($formatList)) {
                $choices = $valueFormatting($formatList);
            }
        }

        return $choices;
    }

    /**
     * @param $format
     * @param $choices
     *
     * @return array|string
     */
    public static function formatList($format, $choices)
    {
        switch ($format) {
            case self::FORMAT_JSON:
                return json_encode($choices);
            case self::FORMAT_BAR:
                return implode('|', $choices);
            case self::FORMAT_SIMPLE_ARRAY:
                return $choices;
            case self::FORMAT_ARRAY:
                $array = [];
                foreach ($choices as $value => $label) {
                    $array[] = [
                        'label' => $label,
                        'value' => $value,
                    ];
                }

                return $array;
        }
    }

    /**
     * @deprecated  to be removed in 3.0; use parseList instead
     *
     * @param $list
     *
     * @return array|string
     */
    public static function parseListStringIntoArray($list)
    {
        return self::parseList($list);
    }
}
