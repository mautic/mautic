<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AjaxControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testGetFieldsForObjectAction()
    {
        $this->client->request(
            Request::METHOD_GET,
            '/s/ajax?action=form:getFieldsForObject&mappedObject=company&mappedField=&formId=10',
            [],
            [],
            $this->createAjaxHeaders()
        );
        $clientResponse = $this->client->getResponse();
        $payload        = json_decode($clientResponse->getContent(), true);
        Assert::assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());

        // Assert some random fields exist.
        Assert::assertSame(
            [
                'label'      => 'Company Email',
                'value'      => 'companyemail',
                'isListType' => false,
            ],
            $payload['fields'][4]
        );
        Assert::assertSame(
            [
                'label'      => 'Industry',
                'value'      => 'companyindustry',
                'isListType' => true,
            ],
            $payload['fields'][9]
        );
    }
}
