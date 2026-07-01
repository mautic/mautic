<?php

namespace Mautic\CoreBundle\Tests\Unit\Release;

use Mautic\CoreBundle\Release\Metadata;
use PHPUnit\Framework\TestCase;

final class MetadataTest extends TestCase
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

        $this->assertSame($releaseMetadata['version'], $metadata->getVersion());
        $this->assertSame(3, $metadata->getMajorVersion());
        $this->assertSame(2, $metadata->getMinorVersion());
        $this->assertSame(1, $metadata->getPatchVersion());
        $this->assertSame('', $metadata->getExtraVersion());
        $this->assertSame($releaseMetadata['stability'], $metadata->getStability());
        $this->assertSame($releaseMetadata['minimum_php_version'], $metadata->getMinSupportedPHPVersion());
        $this->assertSame($releaseMetadata['maximum_php_version'], $metadata->getMaxSupportedPHPVersion());
        $this->assertSame($releaseMetadata['show_php_version_warning_if_under'], $metadata->getShowPHPVersionWarningIfUnder());
        $this->assertSame($releaseMetadata['minimum_mautic_version'], $metadata->getMinSupportedMauticVersion());
        $this->assertSame($releaseMetadata['announcement_url'], $metadata->getAnnouncementUrl());
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

        $this->assertSame($releaseMetadata['version'], $metadata->getVersion());
        $this->assertSame(3, $metadata->getMajorVersion());
        $this->assertSame(2, $metadata->getMinorVersion());
        $this->assertSame(1, $metadata->getPatchVersion());
        $this->assertSame('', $metadata->getExtraVersion());
        $this->assertSame($releaseMetadata['stability'], $metadata->getStability());
        $this->assertSame($releaseMetadata['minimum_php_version'], $metadata->getMinSupportedPHPVersion());
        $this->assertSame($releaseMetadata['maximum_php_version'], $metadata->getMaxSupportedPHPVersion());
        $this->assertSame('', $metadata->getShowPHPVersionWarningIfUnder());
        $this->assertSame($releaseMetadata['minimum_mautic_version'], $metadata->getMinSupportedMauticVersion());
        $this->assertSame($releaseMetadata['announcement_url'], $metadata->getAnnouncementUrl());
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

        $this->assertSame($releaseMetadata['version'], $metadata->getVersion());
        $this->assertSame(3, $metadata->getMajorVersion());
        $this->assertSame(2, $metadata->getMinorVersion());
        $this->assertSame(1, $metadata->getPatchVersion());
        $this->assertSame('beta', $metadata->getExtraVersion());
        $this->assertSame($releaseMetadata['stability'], $metadata->getStability());
        $this->assertSame($releaseMetadata['minimum_php_version'], $metadata->getMinSupportedPHPVersion());
        $this->assertSame($releaseMetadata['maximum_php_version'], $metadata->getMaxSupportedPHPVersion());
        $this->assertSame($releaseMetadata['show_php_version_warning_if_under'], $metadata->getShowPHPVersionWarningIfUnder());
        $this->assertSame($releaseMetadata['minimum_mautic_version'], $metadata->getMinSupportedMauticVersion());
        $this->assertSame($releaseMetadata['announcement_url'], $metadata->getAnnouncementUrl());
        $this->assertSame($releaseMetadata['minimum_mysql_version'], $metadata->getMinSupportedMySqlVersion());
        $this->assertSame($releaseMetadata['minimum_mariadb_version'], $metadata->getMinSupportedMariaDbVersion());
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
        $this->assertSame($releaseMetadata['version'], $metadata->getVersion());
        $this->assertSame(3, $metadata->getMajorVersion());
        $this->assertSame(2, $metadata->getMinorVersion());
        $this->assertSame(1, $metadata->getPatchVersion());
        $this->assertSame('xxx-yyy', $metadata->getExtraVersion());
        $this->assertSame($releaseMetadata['stability'], $metadata->getStability());
        $this->assertSame($releaseMetadata['minimum_php_version'], $metadata->getMinSupportedPHPVersion());
        $this->assertSame($releaseMetadata['maximum_php_version'], $metadata->getMaxSupportedPHPVersion());
        $this->assertSame($releaseMetadata['show_php_version_warning_if_under'], $metadata->getShowPHPVersionWarningIfUnder());
        $this->assertSame($releaseMetadata['minimum_mautic_version'], $metadata->getMinSupportedMauticVersion());
        $this->assertSame($releaseMetadata['announcement_url'], $metadata->getAnnouncementUrl());
        $this->assertSame($releaseMetadata['minimum_mysql_version'], $metadata->getMinSupportedMySqlVersion());
        $this->assertSame($releaseMetadata['minimum_mariadb_version'], $metadata->getMinSupportedMariaDbVersion());
    }
}
