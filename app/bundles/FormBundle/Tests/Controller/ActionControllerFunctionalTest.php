<?php

namespace Mautic\FormBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Form;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

final class ActionControllerFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMException
     */
    public function testNewActionWithJapanese(): void
    {
        // Create new form
        $form = new Form();
        $form->setName('Test Form');
        $form->setAlias('testform');
        $this->em->persist($form);
        $this->em->flush();

        // Save new Send Form Results action
        $headers = $this->createAjaxHeaders();
        $payload = [
            'formaction' => [
                'properties' => [
                    'subject'        => 'Test Japanese',
                    'set_replyto'    => 1,
                    'message'        => '<p style="font-family: メイリオ">Test</p>',
                    'email_to_owner' => 0,
                    'copy_lead'      => 0,
                ],
                'type'   => 'form.email',
                'formId' => $form->getId(),
            ],
        ];
        $this->client->request(
            Request::METHOD_POST,
            '/s/forms/action/new',
            $payload,
            [],
            $headers);
        $this->assertTrue($this->client->getResponse()->isOk());
        $content  = json_decode($this->client->getResponse()->getContent())->actionHtml;
        $crawler  = new Crawler($content);
        $editPage = $crawler->filter('.btn-edit')->attr('href');

        // Check the content was not changed
        $this->client->request(
            Request::METHOD_GET,
            $editPage,
            [],
            [],
            $headers);
        $this->assertContains('&lt;p style=&quot;font-family: メイリオ&quot;&gt;Test&lt;/p&gt;', json_decode($this->client->getResponse()->getContent())->newContent);
    }
}
