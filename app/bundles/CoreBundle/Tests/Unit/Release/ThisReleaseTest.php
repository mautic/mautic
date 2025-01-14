<?php

namespace Mautic\CoreBundle\Tests\Unit\Release;

use Mautic\CoreBundle\Release\ThisRelease;
use PHPUnit\Framework\TestCase;

class ThisReleaseTest extends TestCase
{
    public function testMetadataParsed()
    {
        $metadata = ThisRelease::getMetadata();

        $this->assertNotEmpty($metadata->getVersion(), 'A full version is required');
        $this->assertNotEmpty($metadata->getStability(), 'A stability is required');
        $this->assertNotEmpty($metadata->getMinSupportedPHPVersion(), 'A minimum PHP version is required');
        $this->assertNotEmpty($metadata->getMaxSupportedPHPVersion(), 'A maximum PHP version is required');
        $this->assertNotEmpty($metadata->getMinSupportedMauticVersion(), 'A minimum Mautic version this version can upgrade from is required');
        $this->assertNotEmpty($metadata->getMinSupportedMySqlVersion(), 'A minimum MySQL version this version needs is required');
        $this->assertNotEmpty($metadata->getMinSupportedMariaDbVersion(), 'A minimum MariaDB version this version needs is required');
    }
}
