<?php

declare(strict_types=1);

namespace MauticPlugin\MauticTagManagerBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\LeadBundle\Entity\TagRepository;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\TagModel;

final class BatchControllerTest extends MauticMysqlTestCase
{
    private const BATCH_TAG_FORM_SELECTOR = 'form[name=batch_tag]';

    private TagRepository $tagRepository;

    /**
     * @var array<int, Tag>
     */
    private array $tags;

    /**
     * @var array<int, Lead>
     */
    private array $leads;

    /**
     * @var array<int, Company>
     */
    private array $companies;

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
        $tagModel            = self::getContainer()->get(TagModel::class);
        $this->tagRepository = $tagModel->getRepository();
        $this->tags          = $this->addTags($tags);
        $this->leads         = $this->addLeads();
        $this->companies     = $this->addCompanies();
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
        $form                                   = $crawler->filter(self::BATCH_TAG_FORM_SELECTOR)->form();
        $values                                 = $form->getValues();
        $values['batch_tag[tags][add_tags]']    = [$this->tags[0]->getId(), $this->tags[1]->getId()];
        $values['batch_tag[tags][remove_tags]'] = [$this->tags[2]->getId(), $this->tags[3]->getId()];
        $values['batch_tag[ids]']               = '["'.$this->leads[0]->getId().'","'.$this->leads[1]->getId().'","'.$this->leads[2]->getId().'"]';
        $form->setValues($values);
        $this->client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('3 contacts affected', (string) $this->client->getResponse()->getContent());

        /** @var LeadModel $leadModel */
        $leadModel = self::getContainer()->get(LeadModel::class);
        $lead1     = $leadModel->getEntity($this->leads[0]->getId());
        $this->assertInstanceOf(Lead::class, $lead1);
        $tagIds    = $this->getTagIds($lead1);
        $this->assertContains($this->tags[0]->getId(), $tagIds);
        $this->assertContains($this->tags[1]->getId(), $tagIds);
        $this->assertNotContains($this->tags[2]->getId(), $tagIds);
    }

    public function testAddAndRemoveBatchSetAction(): void
    {
        /** @var LeadModel $leadModel */
        $leadModel = self::getContainer()->get(LeadModel::class);
        $this->leads[0]->addTag($this->tags[1]);
        $this->leads[0]->addTag($this->tags[2]);
        $leadModel->saveEntity($this->leads[0]);

        $crawler                                = $this->client->request('GET', '/s/tags/batch/view');
        $form                                   = $crawler->filter(self::BATCH_TAG_FORM_SELECTOR)->form();
        $values                                 = $form->getValues();
        $values['batch_tag[tags][remove_tags]'] = [$this->tags[1]->getId()];
        $values['batch_tag[ids]']               = '["'.$this->leads[0]->getId().'"]';
        $form->setValues($values);
        $this->client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('1 contact affected', (string) $this->client->getResponse()->getContent());
        $lead1 = $leadModel->getEntity($this->leads[0]->getId());
        $this->assertInstanceOf(Lead::class, $lead1);
        $tagIds = $this->getTagIds($lead1);
        $this->assertNotContains($this->tags[1]->getId(), $tagIds);
        $this->assertContains($this->tags[2]->getId(), $tagIds);
    }

    public function testAddTagBatchSetActionForCompany(): void
    {
        $crawler                                = $this->client->request('GET', '/s/tags/batch/view?objectType=company');
        $form                                   = $crawler->filter(self::BATCH_TAG_FORM_SELECTOR)->form();
        $values                                 = $form->getValues();
        $values['batch_tag[tags][add_tags]']    = [$this->tags[0]->getId(), $this->tags[1]->getId()];
        $values['batch_tag[tags][remove_tags]'] = [$this->tags[2]->getId()];
        $values['batch_tag[ids]']               = '["'.$this->companies[0]->getId().'","'.$this->companies[1]->getId().'"]';
        $form->setValues($values);
        $this->client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('2 companies affected', (string) $this->client->getResponse()->getContent());

        $companyModel = self::getContainer()->get(CompanyModel::class);
        $company1     = $companyModel->getEntity($this->companies[0]->getId());
        $tagIds       = $this->getTagIds($company1);
        $this->assertContains($this->tags[0]->getId(), $tagIds);
        $this->assertContains($this->tags[1]->getId(), $tagIds);
        $this->assertNotContains($this->tags[2]->getId(), $tagIds);
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
        $leadModel = self::getContainer()->get(LeadModel::class);
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

    /**
     * @return array<int, Company>
     */
    public function addCompanies(): array
    {
        $companyModel = self::getContainer()->get(CompanyModel::class);
        $company      = $companyModel->getEntity();

        $company->setName('Company 1');
        $companyModel->saveEntity($company);
        $this->companies[] = $company;

        $company = $companyModel->getEntity();
        $company->setName('Company 2');
        $companyModel->saveEntity($company);
        $this->companies[] = $company;

        return $this->companies;
    }

    /**
     * @return array<int>
     */
    private function getTagIds(Lead|Company $entity): array
    {
        return array_map(
            static fn (Tag $tag): int => (int) $tag->getId(),
            $entity->getTags()->toArray()
        );
    }
}
