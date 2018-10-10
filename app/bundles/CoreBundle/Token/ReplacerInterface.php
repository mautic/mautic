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
interface ReplacerInterface
{
    public function findAndReplaceTokens(Lead $contact, $content, $replace);
}
