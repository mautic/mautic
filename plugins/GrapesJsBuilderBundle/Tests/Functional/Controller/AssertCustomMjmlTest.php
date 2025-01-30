<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Entity\Email;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\Plugin;
use MauticPlugin\GrapesJsBuilderBundle\Entity\GrapesJsBuilder;
use MauticPlugin\GrapesJsBuilderBundle\Entity\GrapesJsBuilderRepository;

class AssertCustomMjmlTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createIntegration();
    }

    /**
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function testAssertCustomMjml(): void
    {
        // Create email & add GrapesJs to it.
        $email = $this->createEmail();
        $this->addToGrapesJsBuilder($email);
        $emailId = $email->getId();

        // Get the Email via API and assert customMjml.
        $this->client->request('GET', '/api/emails/'.$emailId);
        $this->assertResponseStatusCodeSame(200);
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($content['email']['grapesjsbuilder']['customMjml']);
    }

    /**
     * @throws \Doctrine\ORM\Exception\NotSupported
     */
    public function testAssertCustomHtmlAndCustomMjml(): void
    {
        // Create email using an API call and add GrapesJS into it.
        $responseData = $this->createEmailViaApi();
        $emailId      = $responseData['email']['id'];
        $email        = $this->em->getRepository(Email::class)->find($emailId);
        $this->addToGrapesJsBuilder($email);

        // Get email & check for both customHtml & customMjml in the response.
        $this->client->request('GET', '/api/emails/'.$emailId);
        $this->assertResponseStatusCodeSame(200);
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($content['email']['customHtml']);
        $this->assertNotEmpty($content['email']['grapesjsbuilder']['customMjml']);
    }

    /**
     * @throws \Doctrine\ORM\Exception\NotSupported
     */
    private function getRepository(): GrapesJsBuilderRepository
    {
        /** @var GrapesJsBuilderRepository $repository */
        $repository = $this->em->getRepository(GrapesJsBuilder::class);

        $repository->setTranslator($this->getTranslatorMock());

        return $repository;
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function createEmail(): Email
    {
        $email = new Email();
        $email->setName('Test email');
        $email->setSubject('Test email subject');
        $email->setEmailType('template');
        $email->setCustomHtml('<html></html>');
        $email->setIsPublished(true);
        $this->em->persist($email);
        $this->em->flush();

        return $email;
    }

    private function getTranslatorMock(): Translator
    {
        $translator = $this->createMock(Translator::class);
        $translator->method('hasId')
          ->will($this->returnValue(false));

        return $translator;
    }

    private function addToGrapesJsBuilder(Email $email): void
    {
        $grapesJsBuilder = new GrapesJsBuilder();
        $grapesJsBuilder->setEmail($email);
        $grapesJsBuilder->setCustomMjml('<mjml>></mjml>');
        $this->getRepository()->saveEntity($grapesJsBuilder);
    }

    private function createEmailViaApi(): mixed
    {
        $emailData = [
            'name'        => 'Test email',
            'subject'     => 'Test email subject',
            'emailType'   => 'template',
            'customHtml'  => '<html></html>',
            'isPublished' => true,
        ];

        $this->client->request('POST', '/api/emails/new', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($emailData));
        $this->assertResponseStatusCodeSame(201);

        return json_decode($this->client->getResponse()->getContent(), true);
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function createIntegration(): void
    {
        $plugin = new Plugin();
        $plugin->setName('GrapesJS Builder');
        $plugin->setBundle('GrapesJsBuilderBundle');
        $this->em->persist($plugin);

        $integration = new Integration();
        $integration->setPlugin($plugin);
        $integration->setIsPublished(true);
        $integration->setName('grapesjsbuilder');
        $this->em->persist($integration);
        $this->em->flush();
    }
}
