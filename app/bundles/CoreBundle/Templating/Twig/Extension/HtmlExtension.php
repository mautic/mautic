<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Templating\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class HtmlExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('htmlAttributesStringToArray', [$this, 'convertHtmlAttributesToArray']),
        ];
    }

    /**
     * Takes a string of HTML attributes and returns them as a key => value array.
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
        } catch (\Exception $e) {
            return [];
        }

        /**
         * This will 1) clean whitespace and 2) convert attributes with
         * multiple values into an array (ie "one two" becomes ["one", "two"].
         */
        foreach ($attributes as $attr => $value) {
            $value = trim($value);

            if (false !== strpos($value, ' ')) {
                $dirty = explode(' ', $value);
                foreach ($dirty as $i => $v) {
                    if (empty($v)) {
                        unset($dirty[$i]);
                    }
                }
                // Keeping index as 0, 1, 2, etc instead of 0, 3, 4, 6, etc. when
                // there are too many spaces between values
                $value = array_values($dirty);
            }

            $attributes[$attr] = $value;
        }

        return $attributes;
    }
}
