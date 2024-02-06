<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional;

use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use PHPUnit\Framework\Assert;

class SamlTest extends AbstractMauticTestCase
{
    public function testDiscoveryTemplateIsOverridden(): void
    {
        $twig    = static::getContainer()->get('twig');
        $content = $twig->render('@LightSamlSp/discovery.html.twig', ['parties' => []]);

        Assert::assertStringContainsString('SAML not configured or configured incorrectly.', $content);
    }
}
