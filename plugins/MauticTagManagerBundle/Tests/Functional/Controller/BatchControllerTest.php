<?php

namespace MauticPlugin\MauticTagManagerBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\LeadBundle\Entity\TagRepository;

class BatchControllerTest extends MauticMysqlTestCase
{
    private TagRepository $tagRepository;

    /**
     * @var array<int, Tag>
     */
    private array $tags;

    /**
     * @var array<int, Lead>
     */
    private array $leads;

    public function setUp(): void
    {
        parent::setUp();
        $tags = [
            'tag1',
            'tag2',
            'tag3',
            'tag4',
        ];
        $tagModel            = static::getContainer()->get('mautic.lead.model.tag');
        $this->tagRepository = $tagModel->getRepository();
        $this->tags          = $this->addTags($tags);
        $this->leads         = $this->addLeads();
    }

    public function testBatchViewAction(): void
    {
        $this->client->request('GET', '/s/tags/batch/view');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Add tags', $this->client->getResponse()->getContent());
        $this->assertStringContainsString('Remove tags', $this->client->getResponse()->getContent());
    }

    public function testAddTagBatchSetAction(): void
    {
        $crawler                                = $this->client->request('GET', '/s/tags/batch/view');
        $form                                   = $crawler->filter('form[name=batch_tag]')->form();
        $values                                 = $form->getValues();
        $values['batch_tag[tags][add_tags]']    = [$this->tags[0]->getId(), $this->tags[1]->getId()];
        $values['batch_tag[tags][remove_tags]'] = [$this->tags[2]->getId(), $this->tags[3]->getId()];
        $values['batch_tag[ids]']               = '["'.$this->leads[0]->getId().'","'.$this->leads[1]->getId().'","'.$this->leads[2]->getId().'"]';
        $form->setValues($values);
        $this->client->submit($form);

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('3 contacts affected', $this->client->getResponse()->getContent());

        $leadModel = static::getContainer()->get('mautic.lead.model.lead');
        $lead1     = $leadModel->getEntity($this->leads[0]->getId());
        $this->assertContains($this->tags[0], $lead1->getTags()->toArray());
        $this->assertContains($this->tags[1], $lead1->getTags()->toArray());
        $this->assertNotContains($this->tags[2], $lead1->getTags()->toArray());
    }

    public function testAddAndRemoveBatchSetAction(): void
    {
        $leadModel = static::getContainer()->get('mautic.lead.model.lead');
        $this->leads[0]->addTag($this->tags[1]);
        $this->leads[0]->addTag($this->tags[2]);
        $leadModel->saveEntity($this->leads[0]);

        $crawler                                = $this->client->request('GET', '/s/tags/batch/view');
        $form                                   = $crawler->filter('form[name=batch_tag]')->form();
        $values                                 = $form->getValues();
        $values['batch_tag[tags][remove_tags]'] = [$this->tags[1]->getId()];
        $values['batch_tag[ids]']               = '["'.$this->leads[0]->getId().'"]';
        $form->setValues($values);
        $this->client->submit($form);

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('1 contact affected', $this->client->getResponse()->getContent());
        $lead1 = $leadModel->getEntity($this->leads[0]->getId());
        $this->assertNotContains($this->tags[1], $lead1->getTags()->toArray());
        $this->assertContains($this->tags[2], $lead1->getTags()->toArray());
    }

    /**
     * @param array<string> $tags
     *
     * @return array<int, Tag>
     */
    public function addTags(array $tags): array
    {
        foreach ($tags as $tag) {
            $tag = new Tag($tag, true);
            $this->tagRepository->saveEntity($tag);
            $this->tags[] = $tag;
        }

        return $this->tags;
    }

    /**
     * @return array<int, Lead>
     */
    public function addLeads(): array
    {
        $leadModel = static::getContainer()->get('mautic.lead.model.lead');
        $lead      = $leadModel->getEntity();

        $lead->setEmail('example1@example.com');
        $lead->setFirstname('John');
        $lead->setLastname('Doe');
        $leadModel->saveEntity($lead);
        $this->leads[] = $lead;

        $lead = $leadModel->getEntity();
        $lead->setEmail('example2@example.com');
        $lead->setFirstname('Jane');
        $lead->setLastname('Doe');
        $leadModel->saveEntity($lead);
        $this->leads[] = $lead;

        $lead = $leadModel->getEntity();
        $lead->setEmail('example3@example.com');
        $lead->setFirstname('John');
        $lead->setLastname('Smith');
        $leadModel->saveEntity($lead);
        $this->leads[] = $lead;

        return $this->leads;
    }
}
