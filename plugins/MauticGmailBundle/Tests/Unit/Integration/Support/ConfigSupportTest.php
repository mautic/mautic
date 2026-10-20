<?php

declare(strict_types=1);

namespace MauticPlugin\MauticGmailBundle\Tests\Unit\Integration\Support;

use MauticPlugin\MauticGmailBundle\Form\Type\GmailKeysType;
use MauticPlugin\MauticGmailBundle\Integration\Support\ConfigSupport;
use PHPUnit\Framework\TestCase;

final class ConfigSupportTest extends TestCase
{
    public function testGetAuthConfigFormNameReturnsGmailKeysType(): void
    {
        $configSupport = new ConfigSupport();

        $this->assertSame(GmailKeysType::class, $configSupport->getAuthConfigFormName());
    }
}
