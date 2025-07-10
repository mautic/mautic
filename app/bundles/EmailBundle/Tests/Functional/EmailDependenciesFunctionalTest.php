<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional;

use Mautic\CampaignBundle\Tests\Functional\Fixtures\FixtureHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Tests\Functional\Fixtures\EmailFixturesHelper;
use Mautic\FormBundle\Entity\Action;
use Mautic\FormBundle\Entity\Form;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\PointBundle\Entity\Point;
use Mautic\PointBundle\Entity\Trigger;
use Mautic\PointBundle\Entity\TriggerEvent;
use Mautic\ReportBundle\Entity\Report;

final class EmailDependenciesFunctionalTest extends MauticMysqlTestCase
{
    private FixtureHelper $campaignFixturesHelper;
    private EmailFixturesHelper $emailFixturesHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->campaignFixturesHelper = new FixtureHelper($this->em);
        $this->emailFixturesHelper    = new EmailFixturesHelper($this->em);
    }

    public function testEmailUsageInSegments(): void
    {
        $email = $this->emailFixturesHelper->createEmail();
        $this->em->flush();

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
        $email = $this->emailFixturesHelper->createEmail();
        $this->em->flush();

        $campaign = $this->campaignFixturesHelper->createCampaignWithEmailSent($email->getId());

        $this->client->request('GET', "/s/ajax?action=email:getEmailUsages&id={$email->getId()}");
        $clientResponse = $this->client->getResponse();
        $jsonResponse   = json_decode($clientResponse->getContent(), true);

        $searchIds = join(',', [$campaign->getId()]);
        $this->assertStringContainsString("/s/campaigns?search=ids:{$searchIds}", $jsonResponse['usagesHtml']);
    }

    public function testEmailUsageWithoutDuplicates(): void
    {
        $email = $this->emailFixturesHelper->createEmail();
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
        $email = $this->emailFixturesHelper->createEmail();
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
        $email = $this->emailFixturesHelper->createEmail();
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
        $email = $this->emailFixturesHelper->createEmail();
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
        $email = $this->emailFixturesHelper->createEmail();
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
}
