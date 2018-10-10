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

/**
 * Interface ReplacerInterface.
 */
interface ReplacerInterface
{
    public function findAndReplaceTokens($content);

    public function getTokenRegex();

    public function getContent();
}
