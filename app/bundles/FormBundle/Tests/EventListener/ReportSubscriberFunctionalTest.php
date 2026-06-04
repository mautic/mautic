<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\EventListener;

use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\ReportBundle\Tests\Functional\AbstractReportSubscriberTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportSubscriberFunctionalTest extends AbstractReportSubscriberTestCase
{
    public function testLeadReportWithDncListColumn(): void
    {
        $leads[] = $this->createContact('test1@example.com');
        $leads[] = $this->createContact('test2@example.com');
        $leads[] = $this->createContact('test3@example.com');
        $this->em->flush();

        $this->createDnc('email', $leads[0], DoNotContact::BOUNCED);
        $this->createDnc('email', $leads[1], DoNotContact::MANUAL);
        $this->createDnc('email', $leads[2], DoNotContact::UNSUBSCRIBED);
        $this->createDnc('sms', $leads[2], DoNotContact::MANUAL);
        $this->em->flush();

        $formId = $this->createFormThroughApi();
        $this->submitForm($formId, [
            'email'     => 'test1@example.com',
            'firstname' => 'test1',
        ]);
        $this->submitForm($formId, [
            'email'     => 'test2@example.com',
            'firstname' => 'test2',
        ]);

        $report = $this->createReport(
            source: 'form.submissions',
            columns: [
                'l.id',
                'l.firstname',
                'dnc_preferences',
            ],
            filters: [
                [
                    'column'    => 'f.id',
                    'glue'      => 'and',
                    'dynamic'   => null,
                    'condition' => 'eq',
                    'value'     => $formId,
                ],
            ],
            order: [['column' => 'l.id', 'direction' => 'ASC']]
        );

        $expectedReport = [
            // id, firstname, dnc_preferences
            [(string) $leads[0]->getId(), 'test1', 'DNC Bounced: Email'],
            [(string) $leads[1]->getId(), 'test2', 'DNC Manually Unsubscribed: Email'],
        ];
        $this->verifyReport($report->getId(), $expectedReport);
        $this->verifyApiReport($report->getId(), $expectedReport);
    }

    public function testLeadReportWithDncListFilterIn(): void
    {
        $leads[] = $this->createContact('test1@example.com');
        $leads[] = $this->createContact('test2@example.com');
        $leads[] = $this->createContact('test3@example.com');
        $this->em->flush();

        $this->createDnc('email', $leads[0], DoNotContact::BOUNCED);
        $this->createDnc('email', $leads[1], DoNotContact::MANUAL);
        $this->createDnc('email', $leads[2], DoNotContact::UNSUBSCRIBED);
        $this->createDnc('sms', $leads[2], DoNotContact::MANUAL);
        $this->em->flush();

        $formId = $this->createFormThroughApi();
        $this->submitForm($formId, [
            'email'     => 'test1@example.com',
            'firstname' => 'test1',
        ]);
        $this->submitForm($formId, [
            'email'     => 'test2@example.com',
            'firstname' => 'test2',
        ]);
        $this->submitForm($formId, [
            'email'     => 'test3@example.com',
            'firstname' => 'test3',
        ]);

        $report = $this->createReport(
            source: 'form.submissions',
            columns: [
                'l.id',
                'l.firstname',
                'dnc_preferences',
            ],
            filters: [
                [
                    'column'    => 'f.id',
                    'glue'      => 'and',
                    'dynamic'   => null,
                    'condition' => 'eq',
                    'value'     => $formId,
                ],
                [
                    'column'    => 'dnc_preferences',
                    'glue'      => 'and',
                    'dynamic'   => null,
                    'condition' => 'in',
                    'value'     => [
                        'email:'.DoNotContact::UNSUBSCRIBED,
                        'email:'.DoNotContact::BOUNCED,
                    ],
                ],
            ],
            order: [['column' => 'l.id', 'direction' => 'ASC']]
        );

        $expectedReport = [
            // id, firstname, dnc_preferences
            [(string) $leads[0]->getId(), 'test1', 'DNC Bounced: Email'],
            [(string) $leads[2]->getId(), 'test3', 'DNC Manually Unsubscribed: Text Message, DNC Unsubscribed: Email'],
        ];
        $this->verifyReport($report->getId(), $expectedReport);
        $this->verifyApiReport($report->getId(), $expectedReport);
    }

    private function createFormThroughApi(): int
    {
        $formPayload = [
            'name'        => 'Submission test form',
            'description' => 'Form created via submission test',
            'formType'    => 'standalone',
            'isPublished' => true,
            'fields'      => [
                [
                    'label'        => 'Email',
                    'type'         => 'email',
                    'alias'        => 'email',
                    'leadField'    => 'email',
                    'mappedField'  => 'email',
                    'mappedObject' => 'contact',
                ],
                [
                    'label'        => 'Firstname',
                    'type'         => 'text',
                    'alias'        => 'firstname',
                    'leadField'    => 'firstname',
                    'mappedField'  => 'firstname',
                    'mappedObject' => 'contact',
                ],
                [
                    'label' => 'Submit',
                    'type'  => 'button',
                ],
            ],
            'postAction'  => 'return',
        ];

        // Create the form
        $this->client->request(Request::METHOD_POST, '/api/forms/new', $formPayload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $formId         = (int) $response['form']['id'];

        $this->assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());

        return $formId;
    }

    /**
     * @param array<string, mixed> $submissionData
     */
    private function submitForm(int $formId, array $submissionData): Crawler
    {
        // Submit the form
        $crawler     = $this->client->request(Request::METHOD_GET, "/form/{$formId}");
        $formCrawler = $crawler->filter('form[id=mauticform_submissiontestform]');
        $this->assertCount(1, $formCrawler);
        $form = $formCrawler->form();

        $formData = [];
        foreach ($submissionData as $key => $value) {
            $formData["mauticform[{$key}]"] = $value;
        }
        $form->setValues($formData);

        return $this->client->submit($form);
    }

    public function createDnc(string $channel, Lead $contact, int $reason): DoNotContact
    {
        $dnc = new DoNotContact();
        $dnc->setChannel($channel);
        $dnc->setLead($contact);
        $dnc->setReason($reason);
        $dnc->setDateAdded(new \DateTime());
        $this->em->persist($dnc);

        return $dnc;
    }

    private function createContact(string $email): Lead
    {
        $lead = new Lead();
        $lead->setEmail($email);
        $this->em->persist($lead);

        return $lead;
    }
}
