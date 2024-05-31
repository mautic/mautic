<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\FormBundle\Entity\Action;
use Mautic\FormBundle\Entity\Form;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\PointBundle\Entity\Point;
use Mautic\PointBundle\Entity\Trigger;
use Mautic\PointBundle\Entity\TriggerEvent;
use Mautic\ReportBundle\Entity\Report;

final class EmailDependenciesFunctionalTest extends MauticMysqlTestCase
{
    public function testEmailUsageInSegments(): void
    {
        $email = $this->createEmail();

        $segmentRead = $this->createSegment('read-email', [
            [
                'glue'       => 'and',
                'field'      => 'lead_email_received',
                'object'     => 'behaviors',
                'type'       => 'lead_email_received',
                'operator'   => 'in',
                'properties' => [
                    'filter' => [
                        $email->getId(),
                    ],
                ],
            ],
        ]);

        $segmentSent = $this->createSegment('sent-email', [
            [
                'glue'       => 'and',
                'field'      => 'lead_email_sent',
                'object'     => 'behaviors',
                'type'       => 'lead_email_received', // it is saved like this
                'operator'   => 'in',
                'properties' => [
                    'filter' => [
                        $email->getId(),
                    ],
                ],
            ],
        ]);

        $this->createSegment('other');

        $this->em->persist($email);
        $this->em->flush();

        $this->client->request('GET', "/s/ajax?action=email:getEmailUsages&id={$email->getId()}");
        $clientResponse = $this->client->getResponse();
        $jsonResponse   = json_decode($clientResponse->getContent(), true);

        $searchIds = join(',', [$segmentRead->getId(), $segmentSent->getId()]);
        $this->assertStringContainsString("/s/segments?search=ids:{$searchIds}", $jsonResponse['usagesHtml']);
    }

    public function testEmailUsageInCampaigns(): void
    {
        $email = $this->createEmail();
        $this->em->persist($email);
        $this->em->flush();

        $campaign = $this->createCampaignWithEmailSent($email->getId());

        $this->client->request('GET', "/s/ajax?action=email:getEmailUsages&id={$email->getId()}");
        $clientResponse = $this->client->getResponse();
        $jsonResponse   = json_decode($clientResponse->getContent(), true);

        $searchIds = join(',', [$campaign->getId()]);
        $this->assertStringContainsString("/s/campaigns?search=ids:{$searchIds}", $jsonResponse['usagesHtml']);
    }

    public function testEmailUsageWithoutDuplicates(): void
    {
        $email = $this->createEmail();
        $this->em->persist($email);
        $this->em->flush();

        $formWithEmailSend = $this->createForm('form-with-email-send');
        $this->createFormActionEmailSend($formWithEmailSend, $email->getId());
        $this->createFormActionEmailSendToUser($formWithEmailSend, $email->getId());

        $this->client->request('GET', "/s/ajax?action=email:getEmailUsages&id={$email->getId()}");
        $clientResponse = $this->client->getResponse();
        $jsonResponse   = json_decode($clientResponse->getContent(), true);

        $formId = $formWithEmailSend->getId();
        $this->assertStringNotContainsString("/s/forms?search=ids:{$formId},{$formId}", $jsonResponse['usagesHtml']);
    }

    public function testEmailUsageInForms(): void
    {
        $email = $this->createEmail();
        $this->em->persist($email);
        $this->em->flush();

        $formWithEmailSend = $this->createForm('form-with-email-send');
        $this->createFormActionEmailSend($formWithEmailSend, $email->getId());

        $formWithEmailSendToUser = $this->createForm('form-with-email-send-to-user');
        $this->createFormActionEmailSendToUser($formWithEmailSendToUser, $email->getId());

        $this->client->request('GET', "/s/ajax?action=email:getEmailUsages&id={$email->getId()}");
        $clientResponse = $this->client->getResponse();
        $jsonResponse   = json_decode($clientResponse->getContent(), true);

        $searchIds = join(',', [$formWithEmailSend->getId(), $formWithEmailSendToUser->getId()]);
        $this->assertStringContainsString("/s/forms?search=ids:{$searchIds}", $jsonResponse['usagesHtml']);
    }

    public function testEmailUsageInPointActions(): void
    {
        $email = $this->createEmail();
        $this->em->persist($email);
        $this->em->flush();

        $pointActionIsSent = $this->createEmailPointAction($email->getId(), 'email.send');
        $pointActionIsOpen = $this->createEmailPointAction($email->getId(), 'email.open');

        $this->client->request('GET', "/s/ajax?action=email:getEmailUsages&id={$email->getId()}");
        $clientResponse = $this->client->getResponse();
        $jsonResponse   = json_decode($clientResponse->getContent(), true);

        $searchIds = join(',', [$pointActionIsSent->getId(), $pointActionIsOpen->getId()]);
        $this->assertStringContainsString("/s/points?search=ids:{$searchIds}", $jsonResponse['usagesHtml']);
    }

    public function testEmailUsageInPointTriggers(): void
    {
        $email = $this->createEmail();
        $this->em->persist($email);
        $this->em->flush();

        $pointActionIsSent = $this->createPointTriggerWithEmailSendEvent($email->getId(), 'email.send');

        $this->client->request('GET', "/s/ajax?action=email:getEmailUsages&id={$email->getId()}");
        $clientResponse = $this->client->getResponse();
        $jsonResponse   = json_decode($clientResponse->getContent(), true);

        $searchIds = join(',', [$pointActionIsSent->getId()]);
        $this->assertStringContainsString("/s/points/triggers?search=ids:{$searchIds}", $jsonResponse['usagesHtml']);
    }

    public function testEmailUsageInReports(): void
    {
        $email = $this->createEmail();
        $this->em->persist($email);
        $this->em->flush();

        $emailReport      = $this->createEmailReport($email->getId());
        $emailStatsReport = $this->createEmailStatsReport($email->getId());

        $this->client->request('GET', "/s/ajax?action=email:getEmailUsages&id={$email->getId()}");
        $clientResponse = $this->client->getResponse();
        $jsonResponse   = json_decode($clientResponse->getContent(), true);

        $searchIds = join(',', [$emailReport->getId(), $emailStatsReport->getId()]);
        $this->assertStringContainsString("/s/reports?search=ids:{$searchIds}", $jsonResponse['usagesHtml']);
    }

    private function createEmailReport(int $emailId): Report
    {
        $report = new Report();
        $report->setName('Contact report');
        $report->setSource('emails');
        $report->setColumns([
            'e.id',
            'e.name',
        ]);
        $report->setFilters([
            [
                'column'    => 'e.id',
                'glue'      => 'and',
                'dynamic'   => null,
                'condition' => 'eq',
                'value'     => $emailId,
            ],
        ]);
        $this->em->persist($report);
        $this->em->flush();

        return $report;
    }

    private function createEmailStatsReport(int $emailId): Report
    {
        $report = new Report();
        $report->setName('Contact report');
        $report->setSource('email.stats');
        $report->setColumns([
            'l.id',
            'es.date_read',
            'es.date_sent',
            'e.id',
            'e.name',
        ]);
        $report->setFilters([
            [
                'column'    => 'e.id',
                'glue'      => 'and',
                'dynamic'   => null,
                'condition' => 'eq',
                'value'     => $emailId,
            ],
        ]);
        $this->em->persist($report);
        $this->em->flush();

        return $report;
    }

    private function createEmailPointAction(int $emailId, string $type): Point
    {
        $pointAction = new Point();
        $pointAction->setName('Is sent email');
        $pointAction->setDelta(1);
        $pointAction->setType($type);
        $pointAction->setProperties(['emails' => [$emailId]]);
        $this->em->persist($pointAction);
        $this->em->flush();

        return $pointAction;
    }

    private function createPointTriggerWithEmailSendEvent(int $emailId, string $type): Trigger
    {
        $pointTrigger = new Trigger();
        $pointTrigger->setName('trigger');
        $this->em->persist($pointTrigger);
        $this->em->flush();

        $triggerEvent = new TriggerEvent();
        $triggerEvent->setTrigger($pointTrigger);
        $triggerEvent->setName('event');
        $triggerEvent->setType($type);
        $triggerEvent->setProperties(['email'=>$emailId]);
        $this->em->persist($triggerEvent);
        $this->em->flush();

        return $pointTrigger;
    }

    private function createForm(string $alias): Form
    {
        $form = new Form();
        $form->setName($alias);
        $form->setAlias($alias);
        $this->em->persist($form);
        $this->em->flush();

        return $form;
    }

    private function createFormActionEmailSend(Form $form, int $emailId): Action
    {
        $action = new Action();
        $action->setName('send email');
        $action->setForm($form);
        $action->setType('email.send.lead');
        $action->setProperties(['email'=> $emailId]);
        $this->em->persist($action);
        $this->em->flush();

        return $action;
    }

    private function createFormActionEmailSendToUser(Form $form, int $emailId): Action
    {
        $action = new Action();
        $action->setName('send email');
        $action->setForm($form);
        $action->setType('email.send.lead');
        $action->setProperties([
            'useremail' => ['email' => $emailId],
            'user_id'   => [1],
        ]);
        $this->em->persist($action);
        $this->em->flush();

        return $action;
    }

    private function createEmail(): Email
    {
        $email = new Email();
        $email->setName('Test email');
        $email->setSubject('Test email subject');
        $email->setEmailType('template');
        $email->setIsPublished(true);
        $this->em->persist($email);
        $this->em->flush();

        return $email;
    }

    /**
     * @param array<int, array<string, mixed>> $filters
     */
    private function createSegment(string $alias, array $filters = []): LeadList
    {
        $segment = new LeadList();
        $segment->setName($alias);
        $segment->setPublicName($alias);
        $segment->setAlias($alias);
        $segment->setFilters($filters);
        $this->em->persist($segment);
        $this->em->flush();

        return $segment;
    }

    /**
     * Creates campaign with email sent action.
     *
     * Campaign diagram:
     * -------------------
     * -  Start segment  -
     * -------------------
     *         |
     * -------------------
     * -   Send email    -
     * -------------------
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function createCampaignWithEmailSent(int $emailId): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('Test Update contact');

        $this->em->persist($campaign);
        $this->em->flush();

        $event1 = new Event();
        $event1->setCampaign($campaign);
        $event1->setName('Send email');
        $event1->setType('email.send');
        $event1->setChannel('email');
        $event1->setChannelId($emailId);
        $event1->setEventType('action');
        $event1->setTriggerMode('immediate');
        $event1->setOrder(1);
        $event1->setProperties(
            [
                'canvasSettings' => [
                    'droppedX' => '549',
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
                    'email'      => $emailId,
                    'email_type' => 'transactional',
                    'priority'   => '2',
                    'attempts'   => '3',
                ],
                'type'            => 'email.send',
                'eventType'       => 'action',
                'anchorEventType' => 'source',
                'campaignId'      => 'mautic_ce6c7dddf8444e579d741c0125f18b33a5d49b45',
                '_token'          => 'HgysZwvH_n0uAp47CcAcsGddRnRk65t-3crOnuLx28Y',
                'buttons'         => [
                    'save' => '',
                ],
                'email'      => $emailId,
                'email_type' => 'transactional',
                'priority'   => 2,
                'attempts'   => 3.0,
            ]
        );

        $this->em->persist($event1);
        $this->em->flush();

        $campaign->setCanvasSettings(
            [
                'nodes'       => [
                    [
                        'id'        => $event1->getId(),
                        'positionX' => '549',
                        'positionY' => '155',
                    ],
                    [
                        'id'        => 'lists',
                        'positionX' => '796',
                        'positionY' => '50',
                    ],
                ],
                'connections' => [
                    [
                        'sourceId' => 'lists',
                        'targetId' => $event1->getId(),
                        'anchors'  => [
                            'source' => 'leadsource',
                            'target' => 'top',
                        ],
                    ],
                ],
            ]
        );

        $this->em->persist($campaign);
        $this->em->flush();

        return $campaign;
    }
}
