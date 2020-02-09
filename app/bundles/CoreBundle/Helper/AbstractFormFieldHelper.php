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

use Mautic\CoreBundle\Helper\ListParser\ArrayListParser;
use Mautic\CoreBundle\Helper\ListParser\BarListParser;
use Mautic\CoreBundle\Helper\ListParser\Exception\FormatNotSupportedException;
use Mautic\CoreBundle\Helper\ListParser\JsonListParser;
use Mautic\CoreBundle\Helper\ListParser\ListParserInterface;
use Mautic\CoreBundle\Helper\ListParser\ValueListParser;
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
     * @var TranslatorInterface
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

    public function __construct()
    {
        $this->setTranslationKeyPrefix();
    }

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
            $choices[$this->translator->transConditional("mautic.core.type.{$v}", "{$this->translationKeyPrefix}{$v}")] = $v;
        }

        foreach ($customFields as $v => $f) {
            $choices[$this->translator->trans($f['label'])] = $v;
        }

        ksort($choices);

        return $choices;
    }

    /**
     * Format a string into an array.
     *
     * @param mixed $list                      List to parse
     * @param bool  $removeEmpty               @deprecated Kept for BC with method signature
     * @param bool  $deprecatedIgnoreNumerical @deprecated Flag was introduced to support boolean choice lists; use parseBooleanList instead
     *
     * @return array
     */
    public static function parseList($list, $removeEmpty = true, $deprecatedIgnoreNumerical = false)
    {
        if ($deprecatedIgnoreNumerical) {
            // BC support for support
            return static::parseBooleanList($list);
        }

        /** @var ListParserInterface[] $parsers */
        $parsers = [
            new JsonListParser(),
            new BarListParser(),
            new ValueListParser(),
            new ArrayListParser(),
        ];

        $listParser = null;
        foreach ($parsers as $parser) {
            try {
                $list = $parser->parse($list);
            } catch (FormatNotSupportedException $exception) {
                continue;
            }
        }

        return static::parseChoiceList($list);
    }

    public static function parseBooleanList($list): array
    {
        /** @var ListParserInterface[] $parsers */
        $parsers = [
            new JsonListParser(),
            new BarListParser(),
            new ValueListParser(),
        ];

        $listParser = null;
        foreach ($parsers as $parser) {
            try {
                $list = $parser->parse($list);
            } catch (FormatNotSupportedException $exception) {
                continue;
            }
        }

        return static::parseChoiceList($list);
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
                if (isset($choices[0]) && isset($choices[0]['label'])) {
                    $array = [];

                    foreach ($choices as $choice) {
                        $array[$choice['value']] = $choice['label'];
                    }

                    return $array;
                }

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

    protected static function parseChoiceList(array $list)
    {
        $choices = [];
        foreach ($list as $value => $label) {
            if (is_array($label) && isset($label['value'])) {
                $value = $label['value'];
                $label = $label['label'];

                if ('' === $value || null === $value) {
                    // Value is empty which can't work as a key
                    continue;
                }

                $choices[trim(html_entity_decode($value, ENT_QUOTES))] = trim(html_entity_decode($label, ENT_QUOTES));
                continue;
            }

            if (('' === $label || null === $label) && ('' === $value || null === $value)) {
                // Both label and value are empty which can't work as choices
                continue;
            }

            if (is_array($label)) {
                // Process the label as an array as this is likely an option group
                $key = trim(html_entity_decode($value, ENT_QUOTES));

                $choices[$key] = static::parseChoiceList($label);
                continue;
            }

            $choices[trim(html_entity_decode($value, ENT_QUOTES))] = trim(html_entity_decode($label, ENT_QUOTES));
        }

        return $choices;
    }
}
