<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Hash;

class UserHash
{
    const FAKE_USER_HASH = 'xxxxxxxxxxxxxx';

    /**
     * Return fake user hash for emails etc. Users does not have hash, only Contacts.
     *
     * @return string
     */
    public static function getFakeUserHash()
    {
        return self::FAKE_USER_HASH;
    }
}
