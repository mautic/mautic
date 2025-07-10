<?php

namespace Mautic\CoreBundle\Tests\Unit\Helper\Update\PreUpdateChecks;

use Mautic\CoreBundle\Helper\Update\PreUpdateChecks\CheckPhpVersion;
use Mautic\CoreBundle\Release\Metadata;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;

class CheckPhpVersionTest extends MauticMysqlTestCase
{
    public function testPhpVersionOk(): void
    {
        $releaseMetadata = [
            'version'                           => '10.0.1',
            'stability'                         => 'stable',
            'minimum_php_version'               => '7.4.0', // Our CI is always this or a higher version so we're good
            'maximum_php_version'               => '999.99.99', // Hopefully this version will never exist
            'show_php_version_warning_if_under' => '7.4.0',
            'minimum_mautic_version'            => '3.2.0',
            'announcement_url'                  => '',
            'minimum_mysql_version'             => '5.6.0',
            'minimum_mariadb_version'           => '10.1.0',
        ];

        $check = new CheckPhpVersion();
        $check->setUpdateCandidateMetadata(new Metadata($releaseMetadata));
        $result = $check->runCheck();

        // Just checking if we can properly detect the PHP version
        $this->assertTrue($result->success);
    }
}
