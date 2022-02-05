<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;

class PublicControllerTest extends MauticMysqlTestCase
{
    /**
     * Tests to ensure that xss is prevented on password reset page.
     */
    public function testXssFilterOnPasswordReset(): void
    {
        $this->client->request('GET', '/passwordreset?bundle=%27-alert("XSS%20TEST%20Mautic")-%27');
        $clientResponse = $this->client->getResponse();
        $this->assertSame(200, $clientResponse->getStatusCode(), 'Return code must be 200.');
        $responseData = $clientResponse->getContent();
        // Tests that actual string is not present.
        $this->assertStringNotContainsString('-alert("xss test mautic")-', $responseData, 'XSS injection attempt is filtered.');
        // Tests that sanitized string is passed.
        $this->assertStringContainsString('alertxsstestmautic', $responseData, 'XSS sanitized string is present.');
    }
}
