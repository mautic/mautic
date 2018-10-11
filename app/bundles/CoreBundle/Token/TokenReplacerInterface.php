<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Token;

use Mautic\LeadBundle\Entity\Lead;

/**
 * Interface ReplacerInterface.
 */
interface TokenReplacerInterface
{
    /**
     * Return content replaced tokens.
     *
     * @param string          $content
     * @param Lead|array|null $contact
     *
     * @return string
     */
    public function replaceTokens($content, $contact);

    /**
     * Return tokens array with replaced data.
     *
     * @param string          $content
     * @param Lead|array|null $contact
     *
     * @return string
     */
    public function findTokens($content, $contact);

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
