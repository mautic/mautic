<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Event;

use Symfony\Component\Process\Exception\InvalidArgumentException;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class PageBuilderEvent
 *
 * @package Mautic\PageBundle\Event
 */
class PageBuilderEvent extends Event
{
    private $tokens  = array();
    private $translator;

    public function __construct($translator)
    {
        $this->translator = $translator;
    }

    public function addTokenSection($key, $header, $content)
    {
        if (array_key_exists($key, $this->tokens)) {
            throw new InvalidArgumentException("The key, '$key' is already used by another subscriber. Please use a different key.");
        }

        $header = $this->translator->trans($header);
        $this->tokens[$key] = array(
            "header"  => $header,
            "content" => $content
        );
    }

    /**
     * Get tokens
     *
     * @return array
     */
    public function getTokenSections()
    {
        uasort($this->tokens, function ($a, $b) {
            return strnatcmp(
                $a['header'], $b['header']);
        });
        return $this->tokens;

    }
}