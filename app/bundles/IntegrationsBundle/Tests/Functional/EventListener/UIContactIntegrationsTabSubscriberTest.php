<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Functional\EventListener;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\IntegrationsBundle\Entity\ObjectMapping;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Attributes\Group;

#[Group('database')]
final class UIContactIntegrationsTabSubscriberTest extends MauticMysqlTestCase
{
    public function testIntegrationMappingIsShown(): void
    {
        $contact = new Lead();
        $this->em->persist($contact);
        $this->em->flush();

        $objectMapping = new ObjectMapping();
        $objectMapping->setIntegration('testintegration');
        $objectMapping->setIntegrationObjectName('testobject');
        $objectMapping->setIntegrationObjectId('testid');
        $objectMapping->setInternalObjectName(Contact::NAME);
        $objectMapping->setInternalObjectId($contact->getId());

        $this->em->persist($objectMapping);
        $this->em->flush();
        $this->em->clear();

        $this->client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, "/s/contacts/view/{$contact->getId()}");
        self::assertResponseIsSuccessful();
        $this->assertStringContainsString('<dt>Object</dt><dd>testobject</dd>', (string) $this->client->getResponse()->getContent());
        $this->assertStringContainsString('<dt>Object ID</dt><dd>testid</dd>', (string) $this->client->getResponse()->getContent());
        $this->assertStringContainsString('testintegration', (string) $this->client->getResponse()->getContent());
    }
}
