<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\DynamicContentBundle\Tests\Functional\DynamicContentReOrderingTrait;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Symfony\Component\HttpFoundation\Request;

final class AjaxControllerTest extends MauticMysqlTestCase
{
    use DynamicContentReOrderingTrait;

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

        $this->em->persist($dwc);
        $this->em->flush();
    }

    public function testSlotNameListAction(): void
    {
        $this->client->request('GET', '/s/ajax?action=dynamicContent:slotNameList&filter=test-');
        $clientResponse = $this->client->getResponse();
        $this->assertEquals(200, $clientResponse->getStatusCode());
        $this->assertJson($clientResponse->getContent());
        $this->assertJsonStringEqualsJsonString('[{"label":"test-dwc-1","value":"test-dwc-1"}]', $clientResponse->getContent());
    }

    public function testGetDwcTokensBySlotNameAction(): void
    {
        $this->createDynamicContent('DC-1', 'slot-1', 0);
        $this->createDynamicContent('DC-2', 'slot-1', 1);
        $this->createDynamicContent('DC-3', 'slot-1', 2);

        $parameters = http_build_query([
            'slotName'             => 'slot-1',
            'includeDefaultOption' => true,
        ]);

        $this->client->request('GET', 's/ajax?action=dynamicContent:getDwcTokensBySlotName&'.$parameters);
        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        $result  = json_decode($content, true);
        $this->assertJson($content);
        $this->assertCount(5, $result['display_orders']);
    }

    public function testGetDwcTokensBySlotNameActionWithIdParam(): void
    {
        $this->createDynamicContent('DC-1', 'slot-1', 0);
        $dwc = $this->createDynamicContent('DC-2', 'slot-1', 1);
        $this->createDynamicContent('DC-3', 'slot-1', 2);

        // Verify when dwc id is passed it should not return current DWC displayOrder in response.
        $parameters = http_build_query([
            'id'                   => $dwc->getId(),
            'slotName'             => 'slot-1',
            'includeDefaultOption' => true,
        ]);

        $this->client->request('GET', 's/ajax?action=dynamicContent:getDwcTokensBySlotName&'.$parameters);
        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        $result  = json_decode($content, true);
        $this->assertJson($content);
        $this->assertCount(4, $result['display_orders']);
        $this->assertTrue($result['display_orders']['(1) DC-1']['selected']);
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
        $field->setAlias('dwc-test-alias');
        $field->setLabel('dwc-test');
        $field->setType('text');

        $form = new Form();
        $form->setName('Test form- dwc');
        $form->setAlias('test-form-dwc');
        $form->addField(0, $field);
        $field->setForm($form);

        $this->em->persist($field);
        $this->em->persist($form);
        $this->em->flush();

        return $form;
    }
}
