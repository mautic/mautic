<?php

namespace Mautic\LeadBundle\Tests\Form\Type;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Copy;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\DomCrawler\Crawler;
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

        // Fetch the form
        $this->client->request(Request::METHOD_GET, '/s/contacts/email/'.$lead->getId());
        $this->assertTrue($this->client->getResponse()->isOk());
        $content     = $this->client->getResponse()->getContent();
        $content     = json_decode($content)->newContent;
        $crawler     = new Crawler($content, $this->client->getInternalRequest()->getUri());
        $formCrawler = $crawler->filter('form');
        $this->assertCount(1, $formCrawler);
        $form = $formCrawler->form();

        // Send email to contact
        $form->setValues([
            'lead_quickemail[fromname]' => 'Admin',
            'lead_quickemail[from]'     => 'admin@mautic.com',
            'lead_quickemail[subject]'  => 'Test Jap Mautic',
            'lead_quickemail[body]'     => '<p style="font-family: メイリオ">Test</p>',
            'lead_quickemail[list]'     => 0,
        ]);
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isOk());

        // Check the email has correct text
        $copy = $this->em->getRepository(Copy::class)->findOneBy(['subject' => 'Test Jap Mautic']);
        $this->assertStringContainsString('<p style="font-family: メイリオ">Test</p>', $copy->getBody());
    }
}
