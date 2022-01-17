<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Form;

use function GuzzleHttp\json_decode;

class SourceControllerTest extends AbstractCampaignTest
{
    public function testTwoSourcesWithSameName(): void
    {
        $form1 = new Form();
        $form1->setName('test');
        $form1->setFormType('campaign');

        $form2 = new Form();
        $form2->setName('test');
        $form2->setFormType('campaign');

        $this->em->persist($form1);
        $this->em->persist($form2);

        $this->em->flush();
        $this->em->clear();

        $this->client->request('GET', '/s/campaigns/sources/new/random_object_id?sourceType=forms');
        $clientResponse = $this->client->getResponse();
        $this->assertSame(200, $clientResponse->getStatusCode(), $this->client->getResponse()->getContent());

        $html = json_decode($clientResponse->getContent(), true)['newContent'];
        $this->assertStringContainsString("<option value=\"{$form1->getId()}\">test</option></select>", $html);
        $this->assertStringContainsString("<option value=\"{$form2->getId()}\">test</option></select>", $html);
    }
}
