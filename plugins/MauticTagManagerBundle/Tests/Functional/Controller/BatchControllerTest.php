<?php

declare(strict_types=1);

namespace MauticPlugin\MauticTagManagerBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\LeadBundle\Entity\TagRepository;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\TagModel;

final class BatchControllerTest extends MauticMysqlTestCase
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

    protected function setUp(): void
    {
        parent::setUp();
        $tags = [
            'tag1',
            'tag2',
            'tag3',
            'tag4',
        ];

        /** @var TagModel $tagModel */
        $tagModel            = static::getContainer()->get(TagModel::class);
        $this->tagRepository = $tagModel->getRepository();
        $this->tags          = $this->addTags($tags);
        $this->leads         = $this->addLeads();
    }

    public function testBatchViewAction(): void
    {
        $this->client->request('GET', '/s/tags/batch/view');
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Add tags', (string) $this->client->getResponse()->getContent());
        $this->assertStringContainsString('Remove tags', (string) $this->client->getResponse()->getContent());
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

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('3 contacts affected', (string) $this->client->getResponse()->getContent());

        /** @var LeadModel $leadModel */
        $leadModel = static::getContainer()->get(LeadModel::class);
        $lead1     = $leadModel->getEntity($this->leads[0]->getId());
        $this->assertInstanceOf(Lead::class, $lead1);
        $this->assertContains($this->tags[0], $lead1->getTags()->toArray());
        $this->assertContains($this->tags[1], $lead1->getTags()->toArray());
        $this->assertNotContains($this->tags[2], $lead1->getTags()->toArray());
    }

    public function testAddAndRemoveBatchSetAction(): void
    {
        /** @var LeadModel $leadModel */
        $leadModel = static::getContainer()->get(LeadModel::class);
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

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('1 contact affected', (string) $this->client->getResponse()->getContent());
        $lead1 = $leadModel->getEntity($this->leads[0]->getId());
        $this->assertInstanceOf(Lead::class, $lead1);
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
        /** @var LeadModel $leadModel */
        $leadModel = static::getContainer()->get(LeadModel::class);
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
