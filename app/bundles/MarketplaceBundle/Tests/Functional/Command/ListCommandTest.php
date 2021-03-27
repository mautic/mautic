<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Tests\Functional\Command;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\MarketplaceBundle\Command\ListCommand;
use PHPUnit\Framework\Assert;

final class ListCommandTest extends MauticMysqlTestCase
{
    public function testCommand(): void
    {
        $requests     = [];
        $history      = Middleware::history($requests);
        $response     = new Response(200, [], file_get_contents(__DIR__.'/../../ApiResponse/list.json'));
        $handlerStack = HandlerStack::create(new MockHandler([$response]));
        $handlerStack->push($history);
        self::$container->set('mautic.http.client', new Client(['handler' => $handlerStack]));

        $result = $this->runCommand(
            ListCommand::NAME,
            [
                '--page'   => 1,
                '--limit'  => 5,
                '--filter' => 'mautic',
            ]
        );

        $expected = '+--------------------------------------------------------+-----------+--------+
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
Execution time: ';

        /** @var Request $request */
        $request = $requests[0]['request'];

        Assert::assertStringContainsString($expected, $result);
        Assert::assertSame('GET', $request->getMethod());
        Assert::assertSame('https://packagist.org/search.json?page=1&per_page=5&type=mautic-plugin&q=mautic', $request->getUri()->__toString());
    }
}
