<?php

namespace Mautic\FormBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;

class FormControllerFunctionalTest extends MauticMysqlTestCase
{
    /**
     * Filtering should return status code 200.
     */
    public function testIndexActionWhenFiltering(): void
    {
        $this->client->request('GET', '/s/forms?search=has%3Aresults&tmpl=list');
        $clientResponse = $this->client->getResponse();
        $this->assertSame(200, $clientResponse->getStatusCode(), 'Return code must be 200.');
    }
}
