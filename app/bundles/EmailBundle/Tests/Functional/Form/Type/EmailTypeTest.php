<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional\Form\Type;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadCategory;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\LeadBundle\Model\DoNotContact as DoNotContactModel;
use PHPUnit\Framework\Assert;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class EmailTypeTest extends MauticMysqlTestCase
{
    /**
     * @var array<mixed>
     */
    private array $contacts = [
        [
            'email'     => 'contact1@email.com',
            'firstname' => 'Contact',
            'lastname'  => 'One',
        ],
        [
            'email'     => 'contact2@email.com',
            'firstname' => 'Contact',
            'lastname'  => 'Two',
        ],
        [
            'email'     => 'contact3@email.com',
            'firstname' => 'Contact',
            'lastname'  => 'Three',
        ],
        [
            'email'     => 'contact4@email.com',
            'firstname' => 'Contact',
            'lastname'  => 'Four',
        ],
    ];

    /**
     * @dataProvider provideSendToDncValue
     */
    public function testCampaignEmailSendWithSendToDnc(bool $sendToDnc, int $expectedEmailCopiesCount): void
    {
        $category   = $this->createCategory();
        $email      = $this->createEmail($category, $sendToDnc);
        $contactIds = $this->createContacts();
        $this->addContactToDnc([$contactIds[2]]);
        $this->removeContactFromCategory((int) $contactIds[3], $category);
        $segment       = $this->createSegment();
        $commandTester = $this->testSymfonyCommand('mautic:segments:update', ['-i' => $segment->getId()]);
        Assert::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        Assert::assertStringContainsString(($contactIdsCount = count($contactIds)).' total contact(s) to be added', $commandTester->getDisplay());
        $segmentLeadCount = $this->em->getRepository(ListLead::class)->count(['list' => $segment]);
        Assert::assertSame($contactIdsCount, $segmentLeadCount);

        $campaign      = $this->createCampaign($segment, $emailId = (int) $email->getId());
        $commandTester = $this->testSymfonyCommand('mautic:campaigns:update', ['-i' => ($campaignId = $campaign->getId())]);
        Assert::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        Assert::assertStringContainsString($contactIdsCount.' total contact(s) to be added', $commandTester->getDisplay());
        $campaignLeadCount = $this->em->getRepository(CampaignLead::class)->count(['campaign' => $campaign]);
        Assert::assertSame($contactIdsCount, $campaignLeadCount);

        $this->em->clear();

        $commandTester = $this->testSymfonyCommand('mautic:campaigns:trigger', ['-i' => $campaignId]);
        Assert::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        Assert::assertStringContainsString($contactIdsCount.' total events(s) to be processed', $commandTester->getDisplay());

        $stats = $this->em->getRepository(Stat::class)->count(['email' => $emailId]);
        Assert::assertSame($expectedEmailCopiesCount, $stats);
    }

    /**
     * @return iterable<array{bool, int}>
     */
    public function provideSendToDncValue(): iterable
    {
        // this should honor Contact's DNC and unsubscribed category, hence email sent count should be 2
        yield [false, 2];
        // this should NOT honor Contact's DNC and unsubscribed category, hence email sent count should be 4
        yield [true, 4];
    }

    private function createSegment(): LeadList
    {
        $segment = new LeadList();
        $segment->setFilters([
            [
                'glue'     => 'and',
                'field'    => 'email',
                'object'   => 'lead',
                'type'     => 'email',
                'operator' => '!empty',
            ],
        ]);
        $segment->setName('Segment A');
        $segment->setPublicName('Segment A');
        $segment->setAlias('segment-a');
        $this->em->persist($segment);
        $this->em->flush();

        return $segment;
    }

    private function createCategory(): Category
    {
        $category = new Category();
        $category->setTitle('Test Category');
        $category->setAlias('test-category');
        $category->setBundle('global');
        $this->em->persist($category);
        $this->em->flush();

        return $category;
    }

    private function createEmail(Category $category, bool $sendToDnc): Email
    {
        $email = new Email();
        $email->setName('Test');
        $email->setSubject('Test');
        $email->setCustomHtml('html');
        $email->setTemplate('beefree-empty');
        $email->setEmailType('template');
        $email->setSendToDnc($sendToDnc);
        $email->setCategory($category);
        $this->em->persist($email);

        return $email;
    }

    /**
     * @return int[]
     */
    private function createContacts(): array
    {
        $this->client->request(Request::METHOD_POST, '/api/contacts/batch/new', $this->contacts);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        Assert::assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());
        Assert::assertSame(Response::HTTP_CREATED, $response['statusCodes'][0], $clientResponse->getContent());
        Assert::assertSame(Response::HTTP_CREATED, $response['statusCodes'][1], $clientResponse->getContent());
        Assert::assertSame(Response::HTTP_CREATED, $response['statusCodes'][2], $clientResponse->getContent());
        Assert::assertSame(Response::HTTP_CREATED, $response['statusCodes'][3], $clientResponse->getContent());

        return [
            $response['contacts'][0]['id'],
            $response['contacts'][1]['id'],
            $response['contacts'][2]['id'],
            $response['contacts'][3]['id'],
        ];
    }

    /**
     * @param int[] $contactIds
     */
    private function addContactToDnc(array $contactIds): void
    {
        /** @var DoNotContactModel $dncModel */
        $dncModel = self::$container->get('mautic.lead.model.dnc');

        foreach ($contactIds as $contactId) {
            $dncModel->addDncForContact($contactId, 'email', DoNotContact::MANUAL, 'Some comment');
        }
    }

    private function removeContactFromCategory(int $contactId, Category $category): void
    {
        $leadCategory = new LeadCategory();
        $leadCategory->setLead($this->em->getReference(Lead::class, $contactId));
        $leadCategory->setCategory($category);
        $leadCategory->setDateAdded(new \DateTime());
        $leadCategory->setManuallyRemoved(true);

        $this->em->persist($leadCategory);
        $this->em->flush();
    }

    private function createCampaign(LeadList $segment, int $emailId): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('Send Transactional Email to DNC');
        $campaign->addList($segment);

        $this->em->persist($campaign);
        $this->em->flush();

        $event = new Event();
        $event->setCampaign($campaign);
        $event->setName('Send email');
        $event->setType('email.send');
        $event->setEventType('action');
        $event->setTriggerMode('immediate');
        $event->setProperties($this->getEventProperties($emailId));
        $this->em->persist($event);
        $this->em->flush();

        return $campaign;
    }

    /**
     * @return array<mixed>
     */
    private function getEventProperties(int $emailId): array
    {
        return [
            'canvasSettings'             => [
                'droppedX' => '760',
                'droppedY' => '155',
            ],
            'name'                       => '',
            'triggerMode'                => 'immediate',
            'triggerDate'                => null,
            'triggerInterval'            => '1',
            'triggerIntervalUnit'        => 'd',
            'triggerHour'                => '',
            'triggerRestrictedStartHour' => '',
            'triggerRestrictedStopHour'  => '',
            'anchor'                     => 'leadsource',
            'properties'                 => [
                'email'       => $emailId,
                'email_type'  => 'transactional',
                'priority'    => '2',
                'attempts'    => '3',
            ],
            'type'                       => 'email.send',
            'eventType'                  => 'action',
            'anchorEventType'            => 'source',
            'campaignId'                 => 'mautic_544d9d435fde5977c426a3e61806f928e35b8238',
            '_token'                     => '37A9NjExY9tNuZk-KRBYjOEaJSDcaKGUUw-0mLpC05w',
            'buttons'                    => [
                'save' => '',
            ],
            'email'                      => $emailId,
            'email_type'                 => 'transactional',
            'priority'                   => 2,
            'attempts'                   => 3,
        ];
    }
}
