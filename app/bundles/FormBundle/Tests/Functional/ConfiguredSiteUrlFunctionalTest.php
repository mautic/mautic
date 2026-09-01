<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Form;
use Symfony\Component\HttpFoundation\Request;

final class ConfiguredSiteUrlFunctionalTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams['site_url'] = 'https://forms.example.com/mautic';
        parent::setUp();
    }

    public function testEmbeddedFormSnippetsUseConfiguredSiteUrl(): void
    {
        $form = new Form();
        $form->setName('Configured site URL form');
        $form->setAlias('configured-site-url-form');
        $form->setPostActionProperty('Success');
        $this->em->persist($form);
        $this->em->flush();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/forms/view/'.$form->getId());

        self::assertResponseIsSuccessful();

        $snippets = $crawler->filter('#modal-automatic-copy .code-snippet--single code');
        $this->assertCount(2, $snippets);
        $this->assertStringContainsString(
            'src="https://forms.example.com/mautic/form/generate.js?id='.$form->getId().'"',
            $snippets->eq(0)->text(),
        );
        $this->assertStringContainsString(
            'src="https://forms.example.com/mautic/form/'.$form->getId().'"',
            $snippets->eq(1)->text(),
        );
    }
}
