<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\Submission;
use Symfony\Component\HttpFoundation\Request;

/**
 * Form submission counts are aggregate analytics, shown only while aggregate analytics are on.
 */
final class FormSubmissionCountAnalyticsFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    protected function setUp(): void
    {
        $this->configParams['aggregate_analytics'] = !str_contains($this->name(), 'Off');

        parent::setUp();
    }

    public function testSubmissionCountsAreShownWhileAggregateAnalyticsAreOn(): void
    {
        $formId = $this->createSubmittedForm();

        $this->client->request(Request::METHOD_GET, '/s/forms');
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('View One Result', (string) $this->client->getResponse()->getContent());

        $crawler = $this->client->request(Request::METHOD_GET, '/s/forms/view/'.$formId);
        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('.ri-download-line'), 'the submissions chart');
    }

    public function testSubmissionCountsAreHiddenWhileAggregateAnalyticsAreOff(): void
    {
        $formId = $this->createSubmittedForm();

        // A list sorted by the submission count would still show the counts in their order
        $this->client->request(Request::METHOD_GET, '/s/forms?orderby=submission_count');
        $this->assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        $this->assertStringNotContainsString('View One Result', $content);
        $this->assertStringContainsString('View Results', $content);
        $this->assertStringNotContainsString("reorderTableData('form','submission_count'", $content);

        $crawler = $this->client->request(Request::METHOD_GET, '/s/forms/view/'.$formId);
        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $crawler->filter('.ri-download-line'), 'the submissions chart');

        // The submission and the results stay, and so does the count the submission limit uses
        $this->assertCount(1, $this->em->getRepository(Submission::class)->findBy(['form' => $formId]));
        $this->em->clear();
        $this->assertSame(1, $this->em->find(Form::class, $formId)->getSubmissionCount());
        $this->client->request(Request::METHOD_GET, '/s/forms/results/'.$formId);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('counted@example.com', (string) $this->client->getResponse()->getContent());
    }

    private function createSubmittedForm(): int
    {
        $this->client->request(Request::METHOD_POST, '/api/forms/new', [
            'name'       => 'Counted form',
            'formType'   => 'standalone',
            'postAction' => 'return',
            'fields'     => [
                ['label' => 'Email', 'alias' => 'email', 'type' => 'email'],
                ['label' => 'Submit', 'alias' => 'submit', 'type' => 'button'],
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $formId = (int) json_decode((string) $this->client->getResponse()->getContent(), true)['form']['id'];

        $crawler = $this->client->request(Request::METHOD_GET, '/form/'.$formId);
        $form    = $crawler->selectButton('mauticform[submit]')->form();
        $form['mauticform[email]']->setValue('counted@example.com');
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        return $formId;
    }
}
