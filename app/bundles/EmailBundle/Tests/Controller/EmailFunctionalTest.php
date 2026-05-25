<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\ORMException;
use Mautic\CoreBundle\Entity\AuditLog;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\PageBundle\Entity\Page;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\HttpFoundation\Request;

class EmailFunctionalTest extends MauticMysqlTestCase
{
    public const SAVE_AND_CLOSE = 'Save & Close';

    public function testExcludedSegmentsConflicting(): void
    {
        $listOne   = $this->createLeadList('One');
        $listTwo   = $this->createLeadList('Two');
        $listThree = $this->createLeadList('Three');

        $this->em->flush();

        $email = $this->createEmail();
        $email->addList($listOne);
        $email->addExcludedList($listTwo);

        $this->em->flush();
        $this->em->clear();

        $crawler = $this->client->request(Request::METHOD_GET, "/s/emails/edit/{$email->getId()}");
        $this->assertResponseOk();
        $form = $crawler->selectButton(self::SAVE_AND_CLOSE)->form();

        // change lists/excludedLists and submit the form
        $form['emailform[excludedLists]']->setValue([$listOne->getId(), $listThree->getId()]); // @phpstan-ignore-line
        $crawler = $this->client->submit($form);

        $this->assertResponseOk();
        Assert::assertStringContainsString('The same segment cannot be excluded and included in the same time.', $crawler->html());
    }

    public function testExcludedSegmentsFieldIsUpdated(): void
    {
        $listOne   = $this->createLeadList('One');
        $listTwo   = $this->createLeadList('Two');
        $listThree = $this->createLeadList('Three');
        $listFour  = $this->createLeadList('Four');

        $this->em->flush();

        $email = $this->createEmail();
        $email->addList($listOne);
        $email->addExcludedList($listTwo);

        $this->em->flush();
        $this->em->clear();

        $crawler = $this->client->request(Request::METHOD_GET, "/s/emails/edit/{$email->getId()}");
        $this->assertResponseOk();

        $form = $crawler->selectButton(self::SAVE_AND_CLOSE)->form();

        /** @var ChoiceFormField $listsField */
        $listsField = $form['emailform[lists]'];
        /** @var ChoiceFormField $excludedListsField */
        $excludedListsField = $form['emailform[excludedLists]'];

        $expectedAvailableOptions = [
            $listOne->getId(),
            $listTwo->getId(),
            $listThree->getId(),
            $listFour->getId(),
        ];
        $this->assertChoiceOptions($listsField, $expectedAvailableOptions, [$listOne->getId()]);
        $this->assertChoiceOptions($excludedListsField, $expectedAvailableOptions, [$listTwo->getId()]);

        // change lists/excludedLists and submit the form
        $listsField->setValue([$listOne->getId(), $listFour->getId()]);
        $excludedListsField->setValue([$listTwo->getId(), $listThree->getId()]);
        $this->client->submit($form);

        $this->assertResponseOk();

        $email = $this->em->find(Email::class, $email->getId());

        // assert lists/excludedLists changed accordingly
        $this->assertEmailLists([
            $listOne->getId(),
            $listFour->getId(),
        ], $email->getLists());
        $this->assertEmailLists([
            $listTwo->getId(),
            $listThree->getId(),
        ], $email->getExcludedLists());

        // assert audit log
        $auditLogs = $this->em->getRepository(AuditLog::class)->findBy([
            'bundle' => 'email',
            'object' => 'email',
        ]);
        Assert::assertCount(1, $auditLogs);
        /** @var AuditLog $auditLog */
        $auditLog = reset($auditLogs);
        Assert::assertInstanceOf(AuditLog::class, $auditLog);
        $details = $auditLog->getDetails();
        Assert::assertIsArray($details);
        Assert::assertArrayHasKey('lists', $details);
        Assert::assertSame([
            [$listOne->getId()],
            [$listOne->getId(), $listFour->getId()],
        ], $details['lists']);
        Assert::assertArrayHasKey('excludedLists', $details);
        Assert::assertSame([
            [$listTwo->getId()],
            [$listTwo->getId(), $listThree->getId()],
        ], $details['excludedLists']);
    }

    public function testPreferenceCenterChangeIsTrackedInAuditLog(): void
    {
        $preferenceCenterOne = $this->createPreferenceCenterPage('Preference Center One');
        $preferenceCenterTwo = $this->createPreferenceCenterPage('Preference Center Two');
        $listOne             = $this->createLeadList('One');

        $this->em->flush();

        $email = $this->createEmail();
        $email->addList($listOne);
        $email->setPreferenceCenter($preferenceCenterOne);

        $this->em->flush();
        $this->em->clear();

        $crawler = $this->client->request(Request::METHOD_GET, "/s/emails/edit/{$email->getId()}");
        $this->assertResponseOk();

        $form = $crawler->selectButton(self::SAVE_AND_CLOSE)->form();

        $preferenceCenterField = $form['emailform[preferenceCenter]'];
        Assert::assertSame((string) $preferenceCenterOne->getId(), $preferenceCenterField->getValue());

        $preferenceCenterField->setValue((string) $preferenceCenterTwo->getId());
        $this->client->submit($form);

        $this->assertResponseOk();

        $email = $this->em->find(Email::class, $email->getId());

        Assert::assertSame($preferenceCenterTwo->getId(), $email->getPreferenceCenter()->getId());

        $auditLogs = $this->em->getRepository(AuditLog::class)->findBy([
            'bundle' => 'email',
            'object' => 'email',
        ]);
        Assert::assertCount(1, $auditLogs);

        /** @var AuditLog $auditLog */
        $auditLog = reset($auditLogs);
        Assert::assertInstanceOf(AuditLog::class, $auditLog);

        $details = $auditLog->getDetails();
        Assert::assertIsArray($details);
        Assert::assertArrayHasKey('preferenceCenter', $details);
        Assert::assertSame([
            $preferenceCenterOne->getId(),
            $preferenceCenterTwo->getId(),
        ], $details['preferenceCenter']);
    }

    /**
     * @throws ORMException
     */
    private function createLeadList(string $name): LeadList
    {
        $leadList = new LeadList();
        $leadList->setName($name);
        $leadList->setPublicName($name);
        $leadList->setAlias(mb_strtolower($name));
        $this->em->persist($leadList);

        return $leadList;
    }

    /**
     * @param mixed[] $expected
     * @param mixed[] $actual
     */
    private function assertArrayValuesEquals(array $expected, array $actual): void
    {
        sort($expected);
        sort($actual);

        Assert::assertEquals($expected, $actual);
    }

    /**
     * @param mixed[] $expectedAvailableOptions
     * @param mixed[] $expectedValue
     */
    private function assertChoiceOptions(ChoiceFormField $field, array $expectedAvailableOptions, array $expectedValue): void
    {
        $this->assertArrayValuesEquals($expectedAvailableOptions, $field->availableOptionValues());
        $this->assertArrayValuesEquals($expectedValue, $field->getValue());
    }

    /**
     * @param mixed[] $expectedListIds
     */
    private function assertEmailLists(array $expectedListIds, Collection $collection): void
    {
        $this->assertArrayValuesEquals($expectedListIds, $collection->map(function (LeadList $leadList) {
            return $leadList->getId();
        })->toArray());
    }

    private function createEmail(): Email
    {
        $email = new Email();
        $email->setName('Email name');
        $email->setSubject('Email subject');
        $email->setEmailType('list');
        $email->setTemplate('some-template');
        $email->setCustomHtml('{}');
        $this->em->persist($email);

        return $email;
    }

    /**
     * @throws ORMException
     */
    private function createPreferenceCenterPage(string $name): Page
    {
        $page = new Page();
        $page->setTitle($name);
        $page->setAlias(mb_strtolower(str_replace(' ', '-', $name)));
        $page->setIsPreferenceCenter(true);
        $page->setCustomHtml('<html><body>Preference Center Page</body></html>');
        $page->setIsPublished(true);
        $this->em->persist($page);

        return $page;
    }

    private function assertResponseOk(): void
    {
        Assert::assertTrue($this->client->getResponse()->isOk());
    }
}
