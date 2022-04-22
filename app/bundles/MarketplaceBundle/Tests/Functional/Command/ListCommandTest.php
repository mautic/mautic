<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Tests\Functional\Command;

use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use Mautic\MarketplaceBundle\Api\Connection;
use Mautic\MarketplaceBundle\Command\ListCommand;
use Mautic\MarketplaceBundle\DTO\Allowlist as DTOAllowlist;
use Mautic\MarketplaceBundle\Service\Allowlist;
use Mautic\MarketplaceBundle\Service\PluginCollector;
use PHPUnit\Framework\Assert;

final class ListCommandTest extends AbstractMauticTestCase
{
    public function testCommand(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('getPlugins')
            ->willReturn(json_decode(file_get_contents(__DIR__.'/../../ApiResponse/list.json'), true));

        $allowlist = $this->createMock(Allowlist::class);
        $allowlist->method('getAllowlist')->willReturn(null);

        $pluginCollector = new PluginCollector($connection, $allowlist);
        $command         = new ListCommand($pluginCollector);

        $result = $this->testSymfonyCommand(
            ListCommand::NAME,
            [
                '--page'   => 1,
                '--limit'  => 5,
                '--filter' => 'mautic',
            ],
            $command
        );

        $expected = <<<EOF
        +--------------------------------------------------------+-----------+--------+
        | name                                                   | downloads | favers |
        +--------------------------------------------------------+-----------+--------+
        | mautic/mautic-saelos-bundle                            | 10586     | 11     |
        | koco/mautic-recaptcha-bundle                           | 2012      | 20     |
        |     This plugin brings reCAPTCHA integration to        |           |        |
        |     mautic.                                            |           |        |
        | monogramm/mautic-ldap-auth-bundle                      | 307       | 8      |
        |     This plugin enables LDAP authentication for        |           |        |
        |     mautic.                                            |           |        |
        | maatoo/mautic-referrals-bundle                         | 527       | 5      |
        |     This plugin enables referrals in mautic.           |           |        |
        | thedmsgroup/mautic-do-not-contact-extras-bundle        | 532       | 9      |
        |     Adds custom DNC list items to be added to standard |           |        |
        |     Mautic DNC lists and creates phpne and sms         |           |        |
        |     channels                                           |           |        |
        +--------------------------------------------------------+-----------+--------+
        Total packages: 58
        Execution time:
        EOF;

        Assert::assertStringContainsString($expected, $result->getDisplay());
        Assert::assertSame(0, $result->getStatusCode());
    }

    public function testCommmandWithAllowlist(): void
    {
        $page  = 1;
        $limit = 5;
        $query = 'mautic';

        $plugin1 = <<<EOF
        {
            "results": [
                {
                    "name": "koco\/mautic-recaptcha-bundle",
                    "description": "This plugin brings reCAPTCHA integration to mautic.",
                    "url": "https:\/\/packagist.org\/packages\/koco\/mautic-recaptcha-bundle",
                    "repository": "https:\/\/github.com\/KonstantinCodes\/mautic-recaptcha",
                    "downloads": 2012,
                    "favers": 20
                }
            ]
        }
        EOF;

        $plugin2 = <<<EOF
        {
            "results": [
                {
                    "name": "maatoo\/mautic-referrals-bundle",
                    "description": "This plugin enables referrals in mautic.",
                    "url": "https:\/\/packagist.org\/packages\/maatoo\/mautic-referrals-bundle",
                    "repository": "https:\/\/github.com\/maatoo-io\/MauticReferralsBundle",
                    "downloads": 527,
                    "favers": 5
                }
            ]
        }
        EOF;

        $connection = $this->createMock(Connection::class);

        $connection->method('getPlugins')
            ->withConsecutive(
                [1, 1, 'koco/mautic-recaptcha-bundle'],
                [1, 1, 'maatoo/mautic-referrals-bundle'])
            ->willReturnOnConsecutiveCalls(
                json_decode($plugin1, true),
                json_decode($plugin2, true)
            );

        $allowlistPayload = DTOAllowlist::fromArray(json_decode(file_get_contents(__DIR__.'/../../ApiResponse/allowlist.json'), true));
        $allowlist        = $this->createMock(Allowlist::class);
        $allowlist->method('getAllowList')->willReturn($allowlistPayload);

        $pluginCollector = new PluginCollector($connection, $allowlist);
        $command         = new ListCommand($pluginCollector);

        $result = $this->testSymfonyCommand(
            ListCommand::NAME,
            [
                '--page'   => $page,
                '--limit'  => $limit,
                '--filter' => $query,
            ],
            $command
        );

        $expected = <<<EOF
        +-------------------------------------------------+-----------+--------+
        | name                                            | downloads | favers |
        +-------------------------------------------------+-----------+--------+
        | koco/mautic-recaptcha-bundle                    | 2012      | 20     |
        |     This plugin brings reCAPTCHA integration to |           |        |
        |     mautic.                                     |           |        |
        | maatoo/mautic-referrals-bundle                  | 527       | 5      |
        |     This plugin enables referrals in mautic.    |           |        |
        +-------------------------------------------------+-----------+--------+
        Total packages: 2
        Execution time:
        EOF;

        Assert::assertStringContainsString($expected, $result->getDisplay());
        Assert::assertSame(0, $result->getStatusCode());
    }
}
