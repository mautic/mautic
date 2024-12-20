<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

final class AjaxControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testGetFieldsForObjectAction(): void
    {
        $this->client->xmlHttpRequest(
            Request::METHOD_GET,
            '/s/ajax?action=form:getFieldsForObject&mappedObject=company&mappedField=&formId=10'
        );
        $clientResponse = $this->client->getResponse();
        $payload        = json_decode($clientResponse->getContent(), true);
        self::assertResponseIsSuccessful();

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
