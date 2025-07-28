<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

class AjaxControllerTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $filters = [
            [
                'glue'     => 'and',
                'field'    => 'city',
                'object'   => 'lead',
                'type'     => 'text',
                'filter'   => 'Pune',
                'display'  => null,
                'operator' => '=',
            ],
        ];

        $dwc = new DynamicContent();
        $dwc->setIsPublished(true)
            ->setName('Dynamic Content')
            ->setContent('<p> some content </p>')
            ->setIsCampaignBased(false)
            ->setSlotName('test-dwc-1')
            ->setDisplayOrder(1)
            ->setFilters($filters);

        $model = self::getContainer()->get('mautic.dynamicContent.model.dynamicContent');
        $model->saveEntity($dwc);
    }

    public function testSlotNameListAction(): void
    {
        $this->client->request('GET', '/s/ajax?action=dynamicContent:slotNameList&filter=test-');
        $clientResponse = $this->client->getResponse();
        Assert::assertEquals(200, $clientResponse->getStatusCode());
        Assert::assertJson($clientResponse->getContent());
        Assert::assertJsonStringEqualsJsonString('[{"label":"test-dwc-1","value":"test-dwc-1"}]', $clientResponse->getContent());
    }

    public function testGetDwcTokensBySlotNameAction(): void
    {
        $this->client->request('GET', 's/ajax?action=dynamicContent:getDwcTokensBySlotName&slotName=test-dwc-1');
        $clientResponse = $this->client->getResponse();
        Assert::assertEquals(200, $clientResponse->getStatusCode());
        Assert::assertJson($clientResponse->getContent());
    }

    public function testGetBuilderTokensAction(): void
    {
        $form = $this->createForm();
        $this->client->request(Request::METHOD_POST, '/s/ajax?action=dynamicContent:getBuilderTokens');
        $tokens = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('tokens', $tokens);
        // verify email token is available
        $this->assertArrayHasKey('{contactfield=email}', $tokens['tokens']);
        // verify page token is available
        $this->assertArrayHasKey('{form='.$form->getId().'}', $tokens['tokens']);
    }

    private function createForm(): Form
    {
        $field = new Field();
        $field->setAlias('test');
        $field->setLabel('test');
        $field->setType('text');

        $form = new Form();
        $form->setName('Test form');
        $form->setAlias('test-form');
        $form->addField(0, $field);
        $field->setForm($form);

        $this->em->persist($field);
        $this->em->persist($form);

        return $form;
    }
}
