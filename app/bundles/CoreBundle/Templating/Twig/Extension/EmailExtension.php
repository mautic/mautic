<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Templating\Twig\Extension;

use Mautic\CoreBundle\Helper\AbstractFormFieldHelper;
use Mautic\CoreBundle\Helper\ListParser\ArrayListParser;
use Mautic\CoreBundle\Helper\ListParser\BarListParser;
use Mautic\CoreBundle\Helper\ListParser\Exception\FormatNotSupportedException;
use Mautic\CoreBundle\Helper\ListParser\JsonListParser;
use Mautic\CoreBundle\Helper\ListParser\ListParserInterface;
use Mautic\CoreBundle\Helper\ListParser\ValueListParser;
use Mautic\CoreBundle\Templating\Helper\AssetsHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class EmailExtension extends AbstractExtension
{
    protected AssetsHelper $helper;

    public function __construct(AssetsHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('parseBooleanList', [AbstractFormFieldHelper::class, 'parseBooleanList']),
            new TwigFunction('parseListForChoices', [$this, 'parseListForChoices']),
            new TwigFunction('fileExists', [$this, 'checkFileExists']),
            new TwigFunction('getUrl', [$this, 'getUrl']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('truncate', [$this, 'truncate']),
        ];
    }

    /**
     * @param string $path
     * @return string
     */
    public function getUrl(string $path): string
    {
        return $this->helper->getUrl($path);
    }

    /**
     * @param string $file
     * @return bool
     */
    public function checkFileExists(string $file): bool
    {
        return file_exists($file);
    }

    /**
     * @param string $string
     * @param int $length
     * @return false|string
     */
    public function truncate(string $string, int $length)
    {
        return substr($string, 0, $length);
    }

    /**
     * Same as parseList method above but it will return labels as keys.
     *
     * @param mixed[] $list
     * @return mixed[]|string[]
     */
    public function parseListForChoices(array $list): array
    {
        return self::parseChoiceList(
            self::parseListsWithParsers(
                $list,
                [
                    new JsonListParser(),
                    new BarListParser(),
                    new ValueListParser(),
                    new ArrayListParser(),
                ]
            ),
            true
        );
    }

    /**
     * @param mixed[] $list
     * @param bool $labelsAsKeys
     * @return mixed[]|string[]
     */
    public function parseChoiceList(array $list, bool $labelsAsKeys = false): array
    {
        $choices = [];
        foreach ($list as $value => $label) {
            if (is_array($label) && array_key_exists('value', $label)) {
                $value = $label['value'] ?? null;
                $label = $label['label'] ?? null;

                if ('' === $value || null === $value) {
                    // Value is empty which can't work as a key
                    continue;
                }

                $choices = self::appendChoice($choices, $label, $value, $labelsAsKeys);

                continue;
            }

            if ('' === $label && '' === $value) {
                // Both label and value are empty which can't work as choices
                continue;
            }

            if (is_array($label)) {
                // Process the label as an array as this is likely an option group
                $key = trim(html_entity_decode($value, ENT_QUOTES));

                $choices[$key] = self::parseChoiceList($label);
                continue;
            }

            $choices = self::appendChoice($choices, $label, $value, $labelsAsKeys);
        }

        return $choices;
    }

    /**
     * @param mixed[] $choices
     * @param string $label
     * @param string $value
     * @param bool $labelsAsKeys
     * @return mixed[]
     */
    private function appendChoice(array $choices, string $label, string $value, bool $labelsAsKeys = false): array
    {
        $label = trim(html_entity_decode($label, ENT_QUOTES));
        $value = trim(html_entity_decode($value, ENT_QUOTES));

        if ($labelsAsKeys) {
            $choices[$label] = $value;
        } else {
            $choices[$value] = $label;
        }

        return $choices;
    }

    /**
     * @param mixed $list
     * @param ListParserInterface[] $parsers
     * @return mixed[]
     */
    private static function parseListsWithParsers($list, array $parsers): array
    {
        foreach ($parsers as $parser) {
            try {
                $list = $parser->parse($list);
            } catch (FormatNotSupportedException $exception) {
                continue;
            }
        }

        return $list;
    }
}
