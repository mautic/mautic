<?php

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
