<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Model\SubmissionModel;
use Symfony\Component\HttpFoundation\Request;

/**
 * A form keeps two counts: the submission rows, which the results, the "View N Results" link and the submission
 * limit use, and the recorded total, which is aggregate analytics and feeds the "Submissions recorded" card and the
 * chart. The rows follow what's in the results table, the total only ever grows.
 */
final class FormSubmissionTotalFunctionalTest extends MauticMysqlTestCase
{
    private const LANDING_PAGE = 'https://example.com/landing';

    protected $useCleanupRollback = false;

    protected function setUp(): void
    {
        $this->configParams['aggregate_analytics'] = !str_contains($this->name(), 'Off');

        parent::setUp();
    }

    public function testASubmissionFromThePublicFormPageIsRecorded(): void
    {
        $formId = $this->createForm();

        // The public page is what the iframe embed code loads, so it is a real submission
        $this->submitFromPage('/form/'.$formId, 'iframe@example.com');

        $this->assertSubmissionCounts($formId, rows: 1, total: 1);
    }

    public function testASubmissionFromThePreviewInMauticIsAResultButIsNotRecordedInTheTotal(): void
    {
        $formId = $this->createForm();

        $this->submitFromPage('/s/forms/preview/'.$formId, 'preview@example.com');

        $this->assertSubmissionCounts($formId, rows: 1, total: 0);

        $this->assertListLinksToTheResults($formId);
        $this->assertCards($formId, rows: '1', contacts: '--', recorded: '--');
    }

    public function testSubmissionsFromAPageAreRecordedInTheTotalAndKeptWhenTheResultsAreDeleted(): void
    {
        $formId = $this->createForm();

        $this->submitFromLandingPage($formId, 'first@example.com');
        $this->submitFromLandingPage($formId, 'second@example.com');
        $this->assertSubmissionCounts($formId, rows: 2, total: 2);

        foreach ($this->em->getRepository(Submission::class)->findBy(['form' => $formId]) as $submission) {
            $this->em->remove($submission);
        }
        $this->em->flush();

        $this->assertSubmissionCounts($formId, rows: 0, total: 2);

        $this->assertCards($formId, rows: '--', contacts: '--', recorded: '2');

        $chartData = self::getContainer()->get(SubmissionModel::class)->getSubmissionTotalsLineChartData(
            null,
            new \DateTime('-1 day'),
            new \DateTime('+1 day'),
            $this->em->find(Form::class, $formId)
        );
        $this->assertSame(2, array_sum($chartData['datasets'][0]['data']), 'the chart is built from the recorded totals');
    }

    public function testASubmissionIsNotRecordedInTheTotalWhileAggregateAnalyticsAreOff(): void
    {
        $formId = $this->createForm();

        $this->submitFromLandingPage($formId, 'untracked@example.com');
        $this->assertSubmissionCounts($formId, rows: 1, total: 0);

        // Nothing is hidden, the rows are not analytics
        $this->assertListLinksToTheResults($formId);
        $this->assertCards($formId, rows: '1', contacts: '--', recorded: '--');
    }

    private function createForm(): int
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

        return (int) json_decode((string) $this->client->getResponse()->getContent(), true)['form']['id'];
    }

    private function submitFromPage(string $page, string $email): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, $page);
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('mauticform[submit]')->form();
        $form['mauticform[email]']->setValue($email);
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();
    }

    private function submitFromLandingPage(int $formId, string $email): void
    {
        $this->client->request(
            Request::METHOD_POST,
            '/form/submit?formId='.$formId,
            ['mauticform' => ['formId' => $formId, 'email' => $email]],
            [],
            ['HTTP_REFERER' => self::LANDING_PAGE]
        );
        $this->assertLessThan(400, $this->client->getResponse()->getStatusCode(), (string) $this->client->getResponse()->getContent());
    }

    private function assertSubmissionCounts(int $formId, int $rows, int $total): void
    {
        $this->em->clear();
        $form = $this->em->find(Form::class, $formId);
        $this->assertInstanceOf(Form::class, $form);

        $this->assertCount($rows, $this->em->getRepository(Submission::class)->findBy(['form' => $formId]), 'submission rows');
        $this->assertSame($rows, $form->getSubmissionCount(), 'the count the submission limit uses follows the rows');
        $this->assertSame($total, self::getContainer()->get(SubmissionModel::class)->getSubmissionTotal($form), 'the recorded total');
    }

    /**
     * The results tag in the form list is a link only when the form has results, so the link stands for "View N Results".
     */
    private function assertListLinksToTheResults(int $formId): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/forms');
        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('a#form-results-count-'.$formId.'[href$="/s/forms/results/'.$formId.'"]'), 'the results tag links to the results');
    }

    /**
     * The three cards in their order: submission rows, associated contacts, recorded total. A zero shows as "--".
     * The test form's field is not mapped to a contact field, so its submissions have no contact.
     */
    private function assertCards(int $formId, string $rows, string $contacts, string $recorded): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/forms/view/'.$formId);
        $this->assertResponseIsSuccessful();

        $values = $crawler->filter('.lead-stats .fw-b')->each(static fn ($node): string => trim($node->text()));
        $this->assertSame([$rows, $contacts, $recorded], $values);
        $this->assertCount(1, $crawler->filter('.ri-download-line'), 'the submissions chart');
    }
}
