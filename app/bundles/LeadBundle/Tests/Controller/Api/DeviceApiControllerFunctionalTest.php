<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DeviceApiControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testPutEditWithInexistingIdSoItShouldCreate(): void
    {
        $contact = new Lead();
        $this->em->persist($contact);
        $this->em->flush();

        $this->client->request(Request::METHOD_PUT, '/api/devices/99999/edit', [
            'device'            => 'desktop',
            'deviceOsName'      => 'Ubuntu',
            'deviceOsShortName' => 'UBT',
            'deviceOsPlatform'  => 'x64',
            'lead'              => $contact->getId(),
        ]);

        $clientResponse = $this->client->getResponse();

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }
}
