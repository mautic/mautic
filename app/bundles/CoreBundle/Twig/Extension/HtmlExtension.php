<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class HtmlExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('htmlAttributesStringToArray', [$this, 'convertHtmlAttributesToArray']),
            new TwigFunction('htmlEntityDecode', [$this, 'htmlEntityDecode']),
        ];
    }

    /**
     * Takes a string of HTML attributes and returns them as a key => value array.
     * Attribute strings which represent a single value are still output as a string
     * An exception is made for html classes, which can either be single or multiple,
     * so should always use an array to avoid overhead in the Twig templates having to write for 2 scenarios.
     *
     * <example>
     *   $attributes = 'id="test-id" class="class-one class-two"';
     *   // ...
     *   $return = [
     *     'id'    => 'test-id',
     *     'class' => ['class-one', 'class-two'],
     *   ];
     * </example>
     *
     * @return array<string, mixed>
     */
    public function convertHtmlAttributesToArray(string $attributes): array
    {
        if (empty($attributes)) {
            return [];
        }

        try {
            $attributes = current((array) new \SimpleXMLElement("<element $attributes />"));
        } catch (\Exception) {
            return [];
        }

        /**
         * This will 1) clean whitespace and 2) convert attributes with
         * multiple values into an array (ie "one two" becomes ["one", "two"].
         */
        foreach ($attributes as $attr => $value) {
            $value = trim($value);

            if (str_contains($value, ' ')) {
                $dirty = explode(' ', $value);
                foreach ($dirty as $i => $v) {
                    if (empty($v)) {
                        unset($dirty[$i]);
                    }
                }
                // Keeping index as 0, 1, 2, etc instead of 0, 3, 4, 6, etc. when
                // there are too many spaces between values
                $value = array_values($dirty);
            } elseif ('class' === $attr && !empty($value)) {
                // for 'class' attribute, we convert single value to an array
                $value = [$value];
            }

            $attributes[$attr] = $value;
        }

        return $attributes;
    }

    public function htmlEntityDecode(string $content): string
    {
        return html_entity_decode($content);
    }
}
