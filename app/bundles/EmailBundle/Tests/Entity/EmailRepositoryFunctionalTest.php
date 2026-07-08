<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Entity;

use Doctrine\ORM\ORMException;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailRepository;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadCategory;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;

final class EmailRepositoryFunctionalTest extends MauticMysqlTestCase
{
    private EmailRepository $emailRepository;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var EmailRepository $repository */
        $repository = $this->em->getRepository(Email::class);

        $this->emailRepository = $repository;
    }

    public function testGetDoNotEmailListEmpty(): void
    {
        $result = $this->emailRepository->getDoNotEmailList();

        $this->assertSame([], $result);
    }

    public function testGetDoNotEmailListNotEmpty(): void
    {
        $lead = new Lead();
        $lead->setEmail('name@domain.tld');
        $this->em->persist($lead);

        $doNotContact = new DoNotContact();
        $doNotContact->setLead($lead);
        $doNotContact->setDateAdded(new \DateTime());
        $doNotContact->setChannel('email');
        $this->em->persist($doNotContact);

        $this->em->flush();

        // no $leadIds
        $result = $this->emailRepository->getDoNotEmailList();
        $this->assertSame([$lead->getId() => $lead->getEmail()], $result);

        // matching $leadIds
        $result = $this->emailRepository->getDoNotEmailList([$lead->getId()]);
        $this->assertSame([$lead->getId() => $lead->getEmail()], $result);

        // mismatching $leadIds
        $result = $this->emailRepository->getDoNotEmailList([-1]);
        $this->assertSame([], $result);
    }

    public function testCheckDoNotEmailNonExistent(): void
    {
        $result = $this->emailRepository->checkDoNotEmail('name@domain.tld');

        $this->assertFalse($result);
    }

    public function testCheckDoNotEmailExistent(): void
    {
        $lead = new Lead();
        $lead->setEmail('name@domain.tld');
        $this->em->persist($lead);

        $doNotContact = new DoNotContact();
        $doNotContact->setLead($lead);
        $doNotContact->setDateAdded(new \DateTime());
        $doNotContact->setChannel('email');
        $doNotContact->setReason(1);
        $doNotContact->setComments('Some comment');
        $this->em->persist($doNotContact);

        $this->em->flush();

        $result = $this->emailRepository->checkDoNotEmail('name@domain.tld');
        $this->assertNotFalse($result);

        $this->assertSame([
            'id'           => (string) $doNotContact->getId(),
            'unsubscribed' => true,
            'bounced'      => false,
            'manual'       => false,
            'comments'     => $doNotContact->getComments(),
        ], $result);
    }

    public function testGetEmailPendingQueryWithSubscribedCategory(): void
    {
        // create some leads
        $leadOne   = $this->createLead('one');
        $leadTwo   = $this->createLead('two');
        $leadThree = $this->createLead('three');
        $leadFour  = $this->createLead('four');
        $leadFive  = $this->createLead('five');
        $leadSix   = $this->createLead('six');

        // create some categories
        $catOne     = $this->createCategory('one');
        $catTwo     = $this->createCategory('two');
        $catThree   = $this->createCategory('three');

        // lead to subscribe categories
        $this->subscribeCategory($leadOne, true, $catOne, $catTwo);
        $this->subscribeCategory($leadTwo, true, $catOne, $catThree);
        $this->subscribeCategory($leadThree, true, $catTwo, $catThree);
        $this->subscribeCategory($leadFour, true, $catOne, $catThree);

        // lead to unsubscribe categories
        $this->subscribeCategory($leadOne, false, $catThree);

        // add some leads in lists for inclusion
        $sourceListOne  = $this->createLeadList('Source One', $leadOne, $leadTwo, $leadThree);
        $sourceListTwo  = $this->createLeadList('Source Two', $leadOne, $leadFour, $leadFive, $leadSix);

        // add some leads in lists for exclusion
        $excludeListOne = $this->createLeadList('Exclude One', $leadTwo, $leadSix);
        $excludeListTwo = $this->createLeadList('Exclude Two', $leadTwo, $leadThree);

        // create an email with included/excluded lists
        $email = new Email();
        $email->setName('Email');
        $email->setSubject('Subject');
        $email->setEmailType('list');
        $email->addList($sourceListOne);
        $email->addList($sourceListTwo);
        $email->addExcludedList($excludeListOne);
        $email->addExcludedList($excludeListTwo);
        $email->setCategory($catThree);
        $this->em->persist($email);

        $this->em->flush();
        $this->em->clear();

        $result = $this->emailRepository->getEmailPendingQuery($email->getId())
            ->executeQuery()
            ->fetchAllAssociative();

        $actualLeadIds  = array_map(intval(...), array_column($result, 'id'));
        sort($actualLeadIds);

        $expectedLeadIds = [$leadFour->getId(), $leadFive->getId()];
        sort($expectedLeadIds);

        $this->assertSame($expectedLeadIds, $actualLeadIds);
    }

    public function testGetEmailPendingQueryWithExcludedLists(): void
    {
        // create some leads
        $leadOne   = $this->createLead('one');
        $leadTwo   = $this->createLead('two');
        $leadThree = $this->createLead('three');
        $leadFour  = $this->createLead('four');
        $leadFive  = $this->createLead('five');
        $leadSix   = $this->createLead('six');

        // add some leads in lists for inclusion
        $sourceListOne  = $this->createLeadList('Source', $leadOne, $leadTwo, $leadThree);
        $sourceListTwo  = $this->createLeadList('Source', $leadOne, $leadFour, $leadFive, $leadSix);

        // add some leads in lists for exclusion
        $excludeListOne = $this->createLeadList('Exclude', $leadTwo, $leadSix);
        $excludeListTwo = $this->createLeadList('Exclude', $leadTwo, $leadThree);

        // create an email with included/excluded lists
        $email = new Email();
        $email->setName('Email');
        $email->setSubject('Subject');
        $email->setEmailType('list');
        $email->addList($sourceListOne);
        $email->addList($sourceListTwo);
        $email->addExcludedList($excludeListOne);
        $email->addExcludedList($excludeListTwo);
        $this->em->persist($email);

        $this->em->flush();
        $this->em->clear();

        $actualLeadIds = $this->emailRepository->getEmailPendingQuery($email->getId())
            ->executeQuery()
            ->fetchFirstColumn();
        sort($actualLeadIds);

        $expectedLeadIds = [$leadOne->getId(), $leadFour->getId(), $leadFive->getId()];
        $expectedLeadIds = array_map(fn (int $id): string => (string) $id, $expectedLeadIds);
        sort($expectedLeadIds);

        $this->assertSame($expectedLeadIds, $actualLeadIds);
    }

    /**
     * A published segment (list) email that has no publish up date must still be
     * picked up for broadcast. Previously such an email was shown as active in the
     * UI but silently excluded from sending because a publish up date was required.
     *
     * @see https://github.com/mautic/mautic/issues/16165
     */
    public function testGetPublishedBroadcastsIncludesSegmentEmailWithoutPublishUp(): void
    {
        // Published segment email without any start date - should be broadcast now.
        $noPublishUp = $this->createSegmentEmail('No publish up', true, null);

        // Published segment email whose start date is already in the past - should be broadcast.
        $pastPublishUp = $this->createSegmentEmail('Past publish up', true, new \DateTime('-1 day'));

        // Published segment email scheduled to start in the future - must be held back.
        $futurePublishUp = $this->createSegmentEmail('Future publish up', true, new \DateTime('+1 day'));

        // Unpublished segment email without a start date - must not be broadcast.
        $unpublished = $this->createSegmentEmail('Unpublished', false, null);

        // Template email without a start date - must not be broadcast.
        $template = $this->createSegmentEmail('Template', true, null);
        $template->setEmailType('template');
        $this->em->persist($template);

        $this->em->flush();
        $this->em->clear();

        $broadcastIds = [];
        foreach ($this->emailRepository->getPublishedBroadcastsIterable() as $email) {
            $broadcastIds[] = $email->getId();
        }
        sort($broadcastIds);

        $expectedIds = [$noPublishUp->getId(), $pastPublishUp->getId()];
        sort($expectedIds);

        Assert::assertSame($expectedIds, $broadcastIds);
        Assert::assertNotContains($futurePublishUp->getId(), $broadcastIds);
        Assert::assertNotContains($unpublished->getId(), $broadcastIds);
        Assert::assertNotContains($template->getId(), $broadcastIds);
    }

    private function createSegmentEmail(string $name, bool $isPublished, ?\DateTimeInterface $publishUp): Email
    {
        $email = new Email();
        $email->setName($name);
        $email->setSubject($name);
        $email->setEmailType('list');
        $email->setIsPublished($isPublished);
        $email->setPublishUp($publishUp);
        $this->em->persist($email);

        return $email;
    }

    /**
     * @throws ORMException
     */
    private function createLead(string $lastName): Lead
    {
        $lead = new Lead();
        $lead->setLastname($lastName);
        $lead->setEmail(sprintf('%s@mail.tld', $lastName));
        $this->em->persist($lead);

        return $lead;
    }

    /**
     * @throws ORMException
     */
    private function createLeadList(string $name, Lead ...$leads): LeadList
    {
        $leadList = new LeadList();
        $leadList->setName($name);
        $leadList->setPublicName($name);
        $leadList->setAlias(mb_strtolower($name));
        $this->em->persist($leadList);

        foreach ($leads as $lead) {
            $this->addLeadToList($lead, $leadList);
        }

        return $leadList;
    }

    private function addLeadToList(Lead $leadOne, LeadList $sourceList): void
    {
        $listLead = new ListLead();
        $listLead->setLead($leadOne);
        $listLead->setList($sourceList);
        $listLead->setDateAdded(new \DateTime());
        $this->em->persist($listLead);
    }

    private function createCategory(string $string): Category
    {
        $category = new Category();
        $category->setTitle('Category '.$string);
        $category->setAlias('category-'.$string);
        $category->setBundle('global');
        $this->em->persist($category);

        return $category;
    }

    private function subscribeCategory(Lead $lead, bool $subscribed, Category ...$categories): void
    {
        foreach ($categories as $category) {
            $leadCategory = new LeadCategory();
            $leadCategory->setLead($lead);
            $leadCategory->setCategory($category);
            $leadCategory->setDateAdded(new \DateTime());
            $leadCategory->setManuallyAdded($subscribed);
            $leadCategory->setManuallyRemoved(!$subscribed);
            $this->em->persist($leadCategory);
        }

        $this->em->flush();
    }
}
