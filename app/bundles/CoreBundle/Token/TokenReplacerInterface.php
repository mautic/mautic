<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Token;

interface TokenReplacerInterface
{
    /**
     * Return content replaced tokens.
     *
     * @param string     $content
     * @param array|null $options
     *
     * @return string
     */
    public function replaceTokens($content, $options);

    /**
     * Return tokens array with replaced data.
     *
     * @param string     $content
     * @param array|null $options
     *
     * @return string
     */
    public function getTokens($content, $options);

    /**
     * Return tokens array with raw not-replaced data     *.
     *
     * @param string       $content
     * @param array|string $regex
     *
     * @return array
     */
    public function searchTokens($content, $regex);
}
