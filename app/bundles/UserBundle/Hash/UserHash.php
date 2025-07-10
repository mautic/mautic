<?php

namespace Mautic\UserBundle\Hash;

class UserHash
{
    public const FAKE_USER_HASH = 'xxxxxxxxxxxxxx';

    /**
     * Return fake user hash for emails etc. Users does not have hash, only Contacts.
     */
    public static function getFakeUserHash(): string
    {
        return self::FAKE_USER_HASH;
    }
}
