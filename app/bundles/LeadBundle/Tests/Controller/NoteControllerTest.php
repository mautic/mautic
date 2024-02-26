<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\HttpFoundation\Response;

final class NoteControllerTest extends MauticMysqlTestCase
{
    protected function beforeBeginTransaction(): void
    {
        $this->resetAutoincrement([
            'leads',
            'companies',
            'campaigns',
            'categories',
            'lead_lists',
        ]);
    }

    /**
     * Quick smoke test to ensure the route is successful.
     */
    public function testIndexActionsIsSuccessful(): void
    {
        $contact = (new Lead())->setFirstname('Test');
        static::getContainer()->get('mautic.lead.model.lead')->saveEntity($contact);

        $crawler = $this->client->request('GET', '/s/contacts/notes/'.$contact->getId());
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * Quick smoke test to ensure the route is successful.
     */
    public function testNewActionsIsSuccessful(): void
    {
        $contact = (new Lead())->setFirstname('Test');
        static::getContainer()->get('mautic.lead.model.lead')->saveEntity($contact);

        $crawler = $this->client->request('GET', '/s/contacts/notes/'.$contact->getId().'/new');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }
}
