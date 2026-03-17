<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\Submission;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;

class SegmentFilterFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testFormSubmissionSegmentFilter(): void
    {
        $application = new Application(self::$kernel);
        $application->setAutoExit(false);
        $applicationTester = new ApplicationTester($application);

        $contactA = $this->createContact('contact-a@example.com');
        $contactB = $this->createContact('contact-b@example.com');
        $contactC = $this->createContact('contact-c@example.com');
        $form     = $this->createForm('Test Form');
        $this->em->flush();

        $this->createSubmission($form, $contactA);
        $this->createSubmission($form, $contactB);
        $this->em->flush();

        $segment = new LeadList();
        $segment->setName('Submitted Test Form');
        $segment->setPublicName('Submitted Test Form');
        $segment->setAlias('submitted-test-form');
        $segment->setIsPublished(true);
        $segment->setFilters([
            [
                'glue'       => 'and',
                'field'      => 'lead_form_submission',
                'object'     => 'behaviors',
                'type'       => 'lead_form_submission',
                'operator'   => 'in',
                'properties' => [
                    'filter' => [$form->getId()],
                ],
            ],
        ]);
        $this->em->persist($segment);
        $this->em->flush();
        $this->em->clear();

        $exitCode = $applicationTester->run([
            'command' => 'mautic:segments:update',
            '-i'      => $segment->getId(),
        ]);

        $this->assertSame(0, $exitCode, $applicationTester->getDisplay());

        $this->client->request('GET', '/api/contacts?search=segment:submitted-test-form');
        $clientResponse = $this->client->getResponse();
        $this->assertTrue($clientResponse->isOk());
        $response = json_decode($clientResponse->getContent(), true);
        $this->assertEquals(2, (int) $response['total']);
        $contactIds = array_column($response['contacts'], 'id');
        $this->assertContains((int) $contactA->getId(), $contactIds);
        $this->assertContains((int) $contactB->getId(), $contactIds);
        $this->assertNotContains((int) $contactC->getId(), $contactIds);
    }

    public function testFormSubmissionSegmentFilterExclude(): void
    {
        $application = new Application(self::$kernel);
        $application->setAutoExit(false);
        $applicationTester = new ApplicationTester($application);

        $contactA = $this->createContact('exclude-a@example.com');
        $contactB = $this->createContact('exclude-b@example.com');
        $form     = $this->createForm('Exclude Form');
        $this->em->flush();

        $this->createSubmission($form, $contactA);
        $this->em->flush();

        $segment = new LeadList();
        $segment->setName('Not Submitted Exclude Form');
        $segment->setPublicName('Not Submitted Exclude Form');
        $segment->setAlias('not-submitted-exclude-form');
        $segment->setIsPublished(true);
        $segment->setFilters([
            [
                'glue'       => 'and',
                'field'      => 'lead_form_submission',
                'object'     => 'behaviors',
                'type'       => 'lead_form_submission',
                'operator'   => '!in',
                'properties' => [
                    'filter' => [$form->getId()],
                ],
            ],
        ]);
        $this->em->persist($segment);
        $this->em->flush();
        $this->em->clear();

        $exitCode = $applicationTester->run([
            'command' => 'mautic:segments:update',
            '-i'      => $segment->getId(),
        ]);

        $this->assertSame(0, $exitCode, $applicationTester->getDisplay());

        $this->client->request('GET', '/api/contacts?search=segment:not-submitted-exclude-form');
        $clientResponse = $this->client->getResponse();
        $this->assertTrue($clientResponse->isOk());
        $response   = json_decode($clientResponse->getContent(), true);
        $contactIds = array_column($response['contacts'], 'id');
        $this->assertNotContains((int) $contactA->getId(), $contactIds);
        $this->assertContains((int) $contactB->getId(), $contactIds);
    }

    private function createContact(string $email): Lead
    {
        $lead = new Lead();
        $lead->setEmail($email);
        $this->em->persist($lead);

        return $lead;
    }

    private function createForm(string $name): Form
    {
        $form = new Form();
        $form->setName($name);
        $form->setAlias(strtolower(str_replace(' ', '-', $name)));
        $form->setIsPublished(true);
        $this->em->persist($form);

        return $form;
    }

    private function createSubmission(Form $form, Lead $lead): Submission
    {
        $submission = new Submission();
        $submission->setForm($form);
        $submission->setLead($lead);
        $submission->setDateSubmitted(new \DateTime());
        $submission->setReferer('https://example.com');
        $this->em->persist($submission);

        return $submission;
    }
}
