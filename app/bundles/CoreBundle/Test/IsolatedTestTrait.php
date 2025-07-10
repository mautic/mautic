<?php

namespace Mautic\CoreBundle\Test;

trait IsolatedTestTrait
{
    /**
     * Ensure the MAUTIC_TABLE_PREFIX const is correctly set in isolated tests.
     *
     * Those test runs don't get the constant set in MauticExtension::executeBeforeFirstTest(), so we need to redefine it.
     */
    public static function setUpBeforeClass(): void
    {
        if (!defined('MAUTIC_TABLE_PREFIX')) {
            EnvLoader::load();
            $prefix = false === getenv('MAUTIC_DB_PREFIX') ? 'test_' : getenv('MAUTIC_DB_PREFIX');
            define('MAUTIC_TABLE_PREFIX', $prefix);
        }
    }
}
