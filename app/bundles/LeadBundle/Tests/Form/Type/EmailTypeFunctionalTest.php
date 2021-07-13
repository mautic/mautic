<?php

namespace Mautic\LeadBundle\Tests\Form\Type;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Copy;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\HttpFoundation\Request;

final class EmailTypeFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Exception
     */
    public function testEmailWithJapanese(): void
    {
        // New contact
        $lead = new Lead();
        $lead->setEmail('test@domain.tld');
        $this->em->persist($lead);
        $this->em->flush();

        // Send email to contact
        $headers = $this->createAjaxHeaders();
        $payload = [
            'lead_quickemail' => [
                'fromname' => 'Admin',
                'from'     => 'admin@mautic.com',
                'subject'  => 'Test Jap Mautic',
                'body'     => '<p style="font-family: メイリオ">Test</p>',
                'list'     => 0,
            ],
        ];
        $this->client->request(
            Request::METHOD_POST,
            '/s/contacts/email/'.(string) $lead->getId(),
            $payload,
            [],
            $headers);
        $this->assertTrue($this->client->getResponse()->isOk());

        // Check the email has correct text
        $copy = $this->em->getRepository(Copy::class)->findOneBy(['subject' => 'Test Jap Mautic']);
        $this->assertContains('<p style="font-family: メイリオ">Test</p>', $copy->getBody());
    }
}
