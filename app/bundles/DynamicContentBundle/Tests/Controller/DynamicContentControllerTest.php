<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\LeadBundle\Model\TagModel;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Response;

final class DynamicContentControllerTest extends MauticMysqlTestCase
{
    private DynamicContent $dwc;

    protected function setUp(): void
    {
        parent::setUp();

        $filters = [
            [
                'glue'     => 'and',
                'field'    => 'tags',
                'object'   => 'lead',
                'type'     => 'tags',
                'filter'   => null,
                'display'  => null,
                'operator' => 'in',
            ],
        ];

        $dwc = new DynamicContent();
        $dwc->setIsPublished(true)
            ->setName('Dynamic Content')
            ->setContent('<p> some content </p>')
            ->setIsCampaignBased(false)
            ->setSlotName('slot Name')
            ->setFilters($filters);

        $model = self::getContainer()->get(DynamicContentModel::class);
        $model->saveEntity($dwc);

        $this->dwc = $dwc;
    }

    public function testEditingDwcWithTagFilters(): void
    {
        $tag1 = $this->createTag('tag1');

        $crawler  = $this->client->request('POST', '/s/dwc/edit/'.$this->dwc->getId());
        $dwcForm  = $crawler->selectButton('Save & Close')->form();
        $dwcForm['dwc[filters][0][filter]']->setValue((string) $tag1->getId());
        $dwcForm['dwc[filters][0][glue]']->setValue('and');
        $dwcForm['dwc[filters][0][operator]']->setValue('in');
        $dwcForm['dwc[filters][0][object]']->setValue('lead');
        $dwcForm['dwc[filters][0][type]']->setValue('tags');
        $dwcForm['dwc[filters][0][field]']->setValue('tags');
        $this->client->submit($dwcForm);

        $clientResponse         = $this->client->getResponse();
        $model                  = self::getContainer()->get(DynamicContentModel::class);
        $dynamicContent         = $model->getEntity($this->dwc->getId());
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertContains((string) $tag1->getId(), $dynamicContent->getFilters()[0]['filter']);
    }

    private function createTag(string $tagName): Tag
    {
        $tag = new Tag();
        $tag->setTag($tagName);

        $tagModel = self::getContainer()->get(TagModel::class);
        $tagModel->saveEntity($tag);

        return $tag;
    }
}
