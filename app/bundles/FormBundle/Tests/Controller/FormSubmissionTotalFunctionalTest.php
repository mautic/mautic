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

    public function testASubmissionFromThePreviewPageIsAResultButIsNotRecordedInTheTotal(): void
    {
        $formId = $this->createForm();

        $crawler = $this->client->request(Request::METHOD_GET, '/form/'.$formId);
        $form    = $crawler->selectButton('mauticform[submit]')->form();
        $form['mauticform[email]']->setValue('preview@example.com');
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        $this->assertSubmissionCounts($formId, rows: 1, total: 0);

        $this->client->request(Request::METHOD_GET, '/s/forms');
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('View One Result', (string) $this->client->getResponse()->getContent());

        $crawler = $this->assertDetailsPageHasTheThreeCards($formId);
        $this->assertSame('--', trim($crawler->filter('.lead-stats .fw-b')->eq(2)->text()), 'the recorded total card shows nothing yet');
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

        $crawler = $this->assertDetailsPageHasTheThreeCards($formId);
        $this->assertSame('--', trim($crawler->filter('.lead-stats .fw-b')->eq(0)->text()), 'the rows card follows the deleted results');
        $this->assertSame('2', trim($crawler->filter('.lead-stats .fw-b')->eq(2)->text()), 'the recorded total card keeps the deleted results');

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
        $this->client->request(Request::METHOD_GET, '/s/forms');
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('View One Result', (string) $this->client->getResponse()->getContent());
        $this->assertDetailsPageHasTheThreeCards($formId);
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

    private function assertDetailsPageHasTheThreeCards(int $formId): \Symfony\Component\DomCrawler\Crawler
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/forms/view/'.$formId);
        $this->assertResponseIsSuccessful();

        $titles = $crawler->filter('.lead-stats .text-secondary.ellipsis')->each(static fn ($node): string => trim($node->text()));
        $this->assertSame(['Total submissions', 'Associated contacts', 'Submissions recorded'], $titles);
        $this->assertCount(1, $crawler->filter('.ri-download-line'), 'the submissions chart');

        return $crawler;
    }
}
