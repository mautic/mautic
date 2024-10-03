<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Tests\Functional\Command;

use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use Mautic\MarketplaceBundle\Api\Connection;
use Mautic\MarketplaceBundle\Command\ListCommand;
use Mautic\MarketplaceBundle\Service\PluginCollector;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;

final class ListCommandTest extends AbstractMauticTestCase
{
    public function testCommand(): void
    {
        /** @var MockObject&Connection $connection */
        $connection = $this->createMock(Connection::class);
        $connection->method('getPlugins')
            ->willReturn(json_decode(file_get_contents(__DIR__.'/../../ApiResponse/list.json'), true));

        $pluginCollector = new PluginCollector($connection);
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
}
