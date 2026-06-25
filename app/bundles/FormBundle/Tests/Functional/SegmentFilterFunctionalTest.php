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
        $contactA = $this->createContact('contact-a@example.com');
        $contactB = $this->createContact('contact-b@example.com');
        $contactC = $this->createContact('contact-c@example.com');
        $form     = $this->createForm('Test Form');
        $this->em->flush();

        $this->createSubmission($form, $contactA);
        $this->createSubmission($form, $contactB);
        $this->em->flush();

        $response = $this->buildAndRunFormSubmissionSegment('Submitted Test Form', 'submitted-test-form', 'in', [$form->getId()]);

        $this->assertEquals(2, (int) $response['total']);
        $contactIds = array_column($response['contacts'], 'id');
        $this->assertContains((int) $contactA->getId(), $contactIds);
        $this->assertContains((int) $contactB->getId(), $contactIds);
        $this->assertNotContains((int) $contactC->getId(), $contactIds);
    }

    public function testFormSubmissionSegmentFilterExclude(): void
    {
        $contactA = $this->createContact('exclude-a@example.com');
        $contactB = $this->createContact('exclude-b@example.com');
        $form     = $this->createForm('Exclude Form');
        $this->em->flush();

        $this->createSubmission($form, $contactA);
        $this->em->flush();

        $response   = $this->buildAndRunFormSubmissionSegment('Not Submitted Exclude Form', 'not-submitted-exclude-form', '!in', [$form->getId()]);
        $contactIds = array_column($response['contacts'], 'id');
        $this->assertNotContains((int) $contactA->getId(), $contactIds);
        $this->assertContains((int) $contactB->getId(), $contactIds);
    }

    /**
     * Persists a segment filtered by the form-submission filter, runs the segment update command
     * and returns the decoded contacts API response for the resulting members.
     *
     * @param array<int> $formIds
     *
     * @return array<string, mixed>
     */
    private function buildAndRunFormSubmissionSegment(string $name, string $alias, string $operator, array $formIds): array
    {
        $segment = new LeadList();
        $segment->setName($name);
        $segment->setPublicName($name);
        $segment->setAlias($alias);
        $segment->setIsPublished(true);
        $segment->setFilters([
            [
                'glue'       => 'and',
                'field'      => 'lead_form_submission',
                'object'     => 'behaviors',
                'type'       => 'lead_form_submission',
                'operator'   => $operator,
                'properties' => [
                    'filter' => $formIds,
                ],
            ],
        ]);
        $this->em->persist($segment);
        $this->em->flush();
        $this->em->clear();

        $application = new Application(self::$kernel);
        $application->setAutoExit(false);
        $applicationTester = new ApplicationTester($application);

        $exitCode = $applicationTester->run([
            'command' => 'mautic:segments:update',
            '-i'      => $segment->getId(),
        ]);

        $this->assertSame(0, $exitCode, $applicationTester->getDisplay());

        $this->client->request('GET', '/api/contacts?search=segment:'.$alias);
        $clientResponse = $this->client->getResponse();
        $this->assertResponseIsSuccessful();

        $response = json_decode($clientResponse->getContent(), true);
        \assert(is_array($response));

        return $response;
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
