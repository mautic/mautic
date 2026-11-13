<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\EventListener;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Entity\SubmissionTotalRepository;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\EventListener\SubmissionTotalSubscriber;
use Mautic\FormBundle\FormEvents;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;

final class SubmissionTotalSubscriberTest extends MauticMysqlTestCase
{
    private Form $form;

    private SubmissionTotalSubscriber $subscriber;

    private SubmissionTotalRepository $submissionTotalRepository;

    protected function setUp(): void
    {
        $this->configParams['aggregate_analytics'] = !str_contains($this->name(), 'Off');

        parent::setUp();

        $this->form = new Form();
        $this->form->setName('Counted form');
        $this->form->setAlias('counted-form');
        $this->em->persist($this->form);
        $this->em->flush();

        $this->subscriber                = self::getContainer()->get(SubmissionTotalSubscriber::class);
        $this->submissionTotalRepository = self::getContainer()->get(SubmissionTotalRepository::class);
    }

    public function testListensToTheSubmitEvent(): void
    {
        $this->assertArrayHasKey(FormEvents::FORM_ON_SUBMIT, SubmissionTotalSubscriber::getSubscribedEvents());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function countedRefererProvider(): iterable
    {
        yield 'a landing page' => ['https://example.com/landing?utm_source=newsletter'];
        yield 'the form embedded on a page' => ['https://example.com/'];
        yield 'another form\'s preview page' => ['http://localhost/form/%d0'];
        yield 'no referer' => [''];
    }

    #[DataProvider('countedRefererProvider')]
    public function testASubmissionIsCountedInTheHourItWasMade(string $referer): void
    {
        $this->subscriber->onFormSubmit($this->createEvent(sprintf($referer, $this->form->getId()), new \DateTime('2026-11-13 10:42:00')));
        $this->subscriber->onFormSubmit($this->createEvent(sprintf($referer, $this->form->getId()), new \DateTime('2026-11-13 10:58:00')));

        $this->assertSame(2, $this->submissionTotalRepository->getTotal($this->form->getId()));
        $this->assertSame(
            [['hour' => '2026-11-13 10:00:00', 'total' => '2']],
            $this->connection->fetchAllAssociative('SELECT hour, total FROM '.MAUTIC_TABLE_PREFIX.'form_submission_totals WHERE form_id = '.$this->form->getId()),
            'both submissions land on the same hour row'
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function previewRefererProvider(): iterable
    {
        yield 'the preview page' => ['http://localhost/form/%d'];
        yield 'the preview page with a stylesheet' => ['http://localhost/form/%d?css=https://example.com/a.css'];
        yield 'the preview page with a trailing slash' => ['http://localhost/form/%d/'];
        yield 'the preview page on another host' => ['https://mautic.example.com/form/%d'];
    }

    #[DataProvider('previewRefererProvider')]
    public function testASubmissionFromThePreviewPageIsNotCounted(string $referer): void
    {
        $this->subscriber->onFormSubmit($this->createEvent(sprintf($referer, $this->form->getId()), new \DateTime()));

        $this->assertSame(0, $this->submissionTotalRepository->getTotal($this->form->getId()));
    }

    public function testNothingIsCountedWhileAggregateAnalyticsAreOff(): void
    {
        $this->subscriber->onFormSubmit($this->createEvent('https://example.com/landing', new \DateTime()));

        $this->assertSame(0, $this->submissionTotalRepository->getTotal($this->form->getId()));
    }

    private function createEvent(string $referer, \DateTime $submittedAt): SubmissionEvent
    {
        $submission = new Submission();
        $submission->setForm($this->form);
        $submission->setReferer($referer);
        $submission->setDateSubmitted($submittedAt);

        return new SubmissionEvent($submission, [], [], new Request());
    }
}
