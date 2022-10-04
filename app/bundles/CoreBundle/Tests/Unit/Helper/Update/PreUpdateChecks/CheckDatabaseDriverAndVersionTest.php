<?php

namespace Mautic\CoreBundle\Tests\Unit\Helper\Update\PreUpdateChecks;

use Mautic\CoreBundle\Helper\Update\PreUpdateChecks\CheckDatabaseDriverAndVersion;
use Mautic\CoreBundle\Release\Metadata;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;

class CheckDatabaseDriverAndVersionTest extends MauticMysqlTestCase
{
    public function testDatabaseDriverAndVersionOk(): void
    {
        $releaseMetadata = [
            'version'                           => '10.0.1',
            'stability'                         => 'stable',
            'minimum_php_version'               => '7.4.0',
            'maximum_php_version'               => '8.0.99',
            'show_php_version_warning_if_under' => '7.4.0',
            'minimum_mautic_version'            => '3.2.0',
            'announcement_url'                  => '',
            'minimum_mysql_version'             => '5.6.0', // Our CI has a higher version than this so we're good
            'minimum_mariadb_version'           => '10.1.0', // Our CI has a higher version than this so we're good
        ];

        $check = new CheckDatabaseDriverAndVersion($this->em);
        $check->setUpdateCandidateMetadata(new Metadata($releaseMetadata));
        $result = $check->runCheck();

        // Just checking if we can properly detect the database driver + version
        $this->assertSame(true, $result->success);
    }

    public function testDatabaseDriverAndVersionTooLow(): void
    {
        $releaseMetadata = [
            'version'                           => '10.0.1',
            'stability'                         => 'stable',
            'minimum_php_version'               => '7.4.0',
            'maximum_php_version'               => '8.0.99',
            'show_php_version_warning_if_under' => '7.4.0',
            'minimum_mautic_version'            => '3.2.0',
            'announcement_url'                  => '',
            'minimum_mysql_version'             => '999.99.99', // Hopefully this version will never exist
            'minimum_mariadb_version'           => '999.99.99', // Hopefully this version will never exist
        ];

        $check = new CheckDatabaseDriverAndVersion($this->em);
        $check->setUpdateCandidateMetadata(new Metadata($releaseMetadata));
        $result = $check->runCheck();

        // The check should've failed as our current database version is lower than 99.99.99
        $this->assertSame(false, $result->success);
    }
}
