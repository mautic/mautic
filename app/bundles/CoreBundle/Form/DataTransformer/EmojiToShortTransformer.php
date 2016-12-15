<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\DataTransformer;

use Mautic\CoreBundle\Helper\EmojiHelper;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Class EmojiToShortTransformer.
 */
class EmojiToShortTransformer implements DataTransformerInterface
{
    /**
     * Convert short to unicode.
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
            $content = EmojiHelper::toEmoji($content, 'short');
        }

        return $content;
    }

    /**
     * Convert emoji to short bytes.
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
            $content = EmojiHelper::toShort($content);
        }

        return $content;
    }
}
