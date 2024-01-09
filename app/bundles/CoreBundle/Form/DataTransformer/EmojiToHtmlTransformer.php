<?php

namespace Mautic\CoreBundle\Form\DataTransformer;

use Mautic\CoreBundle\Helper\EmojiHelper;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * @deprecated since Mautic 5.0, to be removed in 6.0 with no replacement.
 */
class EmojiToHtmlTransformer implements DataTransformerInterface
{
    /**
     * Convert emoji unicode to HTML.
     *
     * @param array|string $content
     *
     * @return string|array
     */
    public function transform($content)
    {
        if (is_array($content)) {
            foreach ($content as &$convert) {
                $convert = $this->transform($convert);
            }
        } else {
            $content = EmojiHelper::toHtml($content);
        }

        return $content;
    }

    /**
     * Convert HTML emoji to unicode bytes.
     *
     * @param array|string $content
     *
     * @return array|string
     */
    public function reverseTransform($content)
    {
        if (is_array($content)) {
            foreach ($content as &$convert) {
                $convert = $this->reverseTransform($convert);
            }
        } else {
            $content = EmojiHelper::toEmoji($content);
        }

        return $content;
    }
}
