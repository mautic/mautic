<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class FieldApiControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testCreatingMultiselectField()
    {
        $payload = [
            'label'               => 'Shops (TB)',
            'alias'               => 'shops',
            'type'                => 'multiselect',
            'isPubliclyUpdatable' => true,
            'isUniqueIdentifier'  => false,
            'properties'          => [
                'list' => [
                    ['label' => 'label1', 'value' => 'value1'],
                    ['label' => 'label2', 'value' => 'value2'],
                ],
            ],
        ];

        $this->client->request(Request::METHOD_POST, '/api/fields/contact/new', $payload);
        $clientResponse = $this->client->getResponse();
        $fieldResponse  = json_decode($clientResponse->getContent(), true);

        Assert::assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());
        Assert::assertTrue($fieldResponse['field']['isPublished']);
        Assert::assertGreaterThan(0, $fieldResponse['field']['id']);
        Assert::assertSame($payload['label'], $fieldResponse['field']['label']);
        Assert::assertSame($payload['alias'], $fieldResponse['field']['alias']);
        Assert::assertSame($payload['type'], $fieldResponse['field']['type']);
        Assert::assertSame($payload['isPubliclyUpdatable'], $fieldResponse['field']['isPubliclyUpdatable']);
        Assert::assertSame($payload['isUniqueIdentifier'], $fieldResponse['field']['isUniqueIdentifier']);
        Assert::assertSame($payload['properties'], $fieldResponse['field']['properties']);

        // Cleanup
        $this->client->request(Request::METHOD_DELETE, '/api/fields/contact/'.$fieldResponse['field']['id'].'/delete', $payload);
        $clientResponse = $this->client->getResponse();
        Assert::assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());
    }
}
