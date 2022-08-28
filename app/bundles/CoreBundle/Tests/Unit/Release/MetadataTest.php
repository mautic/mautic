<?php

namespace Mautic\CoreBundle\Tests\Unit\Release;

use Mautic\CoreBundle\Release\Metadata;
use PHPUnit\Framework\TestCase;

class MetadataTest extends TestCase
{
    public function testStableRelease(): void
    {
        $releaseMetadata = [
            'version'                           => '3.2.1',
            'stability'                         => 'stable',
            'minimum_php_version'               => '7.2.21',
            'maximum_php_version'               => '7.3.99',
            'show_php_version_warning_if_under' => '7.3.0',
            'minimum_mautic_version'            => '3.0.0-alpha',
            'announcement_url'                  => '',
        ];

        $metadata = new Metadata($releaseMetadata);

        $this->assertEquals($releaseMetadata['version'], $metadata->getVersion());
        $this->assertEquals(3, $metadata->getMajorVersion());
        $this->assertEquals(2, $metadata->getMinorVersion());
        $this->assertEquals(1, $metadata->getPatchVersion());
        $this->assertEquals('', $metadata->getExtraVersion());
        $this->assertEquals($releaseMetadata['stability'], $metadata->getStability());
        $this->assertEquals($releaseMetadata['minimum_php_version'], $metadata->getMinSupportedPHPVersion());
        $this->assertEquals($releaseMetadata['maximum_php_version'], $metadata->getMaxSupportedPHPVersion());
        $this->assertEquals($releaseMetadata['show_php_version_warning_if_under'], $metadata->getShowPHPVersionWarningIfUnder());
        $this->assertEquals($releaseMetadata['minimum_mautic_version'], $metadata->getMinSupportedMauticVersion());
        $this->assertEquals($releaseMetadata['announcement_url'], $metadata->getAnnouncementUrl());
    }

    public function testStableReleaseWithoutPhpVersionWarning(): void
    {
        $releaseMetadata = [
            'version'                           => '3.2.1',
            'stability'                         => 'stable',
            'minimum_php_version'               => '7.2.21',
            'maximum_php_version'               => '7.3.99',
            'minimum_mautic_version'            => '3.0.0-alpha',
            'announcement_url'                  => '',
        ];

        $metadata = new Metadata($releaseMetadata);

        $this->assertEquals($releaseMetadata['version'], $metadata->getVersion());
        $this->assertEquals(3, $metadata->getMajorVersion());
        $this->assertEquals(2, $metadata->getMinorVersion());
        $this->assertEquals(1, $metadata->getPatchVersion());
        $this->assertEquals('', $metadata->getExtraVersion());
        $this->assertEquals($releaseMetadata['stability'], $metadata->getStability());
        $this->assertEquals($releaseMetadata['minimum_php_version'], $metadata->getMinSupportedPHPVersion());
        $this->assertEquals($releaseMetadata['maximum_php_version'], $metadata->getMaxSupportedPHPVersion());
        $this->assertEquals('', $metadata->getShowPHPVersionWarningIfUnder());
        $this->assertEquals($releaseMetadata['minimum_mautic_version'], $metadata->getMinSupportedMauticVersion());
        $this->assertEquals($releaseMetadata['announcement_url'], $metadata->getAnnouncementUrl());
    }

    public function testExtraVersionFound(): void
    {
        $releaseMetadata = [
            'version'                           => '3.2.1-beta',
            'stability'                         => 'beta',
            'minimum_php_version'               => '7.2.21',
            'maximum_php_version'               => '7.3.99',
            'show_php_version_warning_if_under' => '7.3.0',
            'minimum_mautic_version'            => '3.0.0-alpha',
            'announcement_url'                  => '',
            'minimum_mysql_version'             => '5.7.14',
            'minimum_mariadb_version'           => '10.3.5',
        ];

        $metadata = new Metadata($releaseMetadata);

        $this->assertEquals($releaseMetadata['version'], $metadata->getVersion());
        $this->assertEquals(3, $metadata->getMajorVersion());
        $this->assertEquals(2, $metadata->getMinorVersion());
        $this->assertEquals(1, $metadata->getPatchVersion());
        $this->assertEquals('beta', $metadata->getExtraVersion());
        $this->assertEquals($releaseMetadata['stability'], $metadata->getStability());
        $this->assertEquals($releaseMetadata['minimum_php_version'], $metadata->getMinSupportedPHPVersion());
        $this->assertEquals($releaseMetadata['maximum_php_version'], $metadata->getMaxSupportedPHPVersion());
        $this->assertEquals($releaseMetadata['show_php_version_warning_if_under'], $metadata->getShowPHPVersionWarningIfUnder());
        $this->assertEquals($releaseMetadata['minimum_mautic_version'], $metadata->getMinSupportedMauticVersion());
        $this->assertEquals($releaseMetadata['announcement_url'], $metadata->getAnnouncementUrl());
        $this->assertEquals($releaseMetadata['minimum_mysql_version'], $metadata->getMinSupportedMySqlVersion());
        $this->assertEquals($releaseMetadata['minimum_mariadb_version'], $metadata->getMinSupportedMariaDbVersion());
    }

    public function testLongerExtraVersionFound(): void
    {
        $releaseMetadata = [
            'version'                           => '3.2.1-xxx-yyy',
            'stability'                         => 'xxx',
            'minimum_php_version'               => '7.2.21',
            'maximum_php_version'               => '7.3.99',
            'show_php_version_warning_if_under' => '7.3.0',
            'minimum_mautic_version'            => '3.0.0-alpha',
            'announcement_url'                  => '',
            'minimum_mysql_version'             => '5.7.14',
            'minimum_mariadb_version'           => '10.3.5',
        ];
        $metadata = new Metadata($releaseMetadata);
        $this->assertEquals($releaseMetadata['version'], $metadata->getVersion());
        $this->assertEquals(3, $metadata->getMajorVersion());
        $this->assertEquals(2, $metadata->getMinorVersion());
        $this->assertEquals(1, $metadata->getPatchVersion());
        $this->assertEquals('xxx-yyy', $metadata->getExtraVersion());
        $this->assertEquals($releaseMetadata['stability'], $metadata->getStability());
        $this->assertEquals($releaseMetadata['minimum_php_version'], $metadata->getMinSupportedPHPVersion());
        $this->assertEquals($releaseMetadata['maximum_php_version'], $metadata->getMaxSupportedPHPVersion());
        $this->assertEquals($releaseMetadata['show_php_version_warning_if_under'], $metadata->getShowPHPVersionWarningIfUnder());
        $this->assertEquals($releaseMetadata['minimum_mautic_version'], $metadata->getMinSupportedMauticVersion());
        $this->assertEquals($releaseMetadata['announcement_url'], $metadata->getAnnouncementUrl());
        $this->assertEquals($releaseMetadata['minimum_mysql_version'], $metadata->getMinSupportedMySqlVersion());
        $this->assertEquals($releaseMetadata['minimum_mariadb_version'], $metadata->getMinSupportedMariaDbVersion());
    }
}
