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

    private Form $otherForm;

    private SubmissionTotalSubscriber $subscriber;

    private SubmissionTotalRepository $submissionTotalRepository;

    protected function setUp(): void
    {
        $this->configParams['aggregate_analytics'] = !str_contains($this->name(), 'Off');

        parent::setUp();

        $this->form      = $this->createForm('Counted form');
        $this->otherForm = $this->createForm('Other form');

        $this->subscriber                = self::getContainer()->get(SubmissionTotalSubscriber::class);
        $this->submissionTotalRepository = self::getContainer()->get(SubmissionTotalRepository::class);
    }

    public function testListensToTheSubmitEvent(): void
    {
        $this->assertArrayHasKey(FormEvents::FORM_ON_SUBMIT, SubmissionTotalSubscriber::getSubscribedEvents());
    }

    /**
     * The referers are templates: %1\$d is the submitted form's ID, %2\$d another form's ID.
     *
     * @return iterable<string, array{string}>
     */
    public static function countedRefererProvider(): iterable
    {
        yield 'a landing page' => ['https://example.com/landing?utm_source=newsletter'];
        yield 'the form embedded on a page' => ['https://example.com/'];
        yield 'the public page the iframe embed loads' => ['http://localhost/form/%1$d'];
        yield 'another form\'s preview in Mautic' => ['http://localhost/s/forms/preview/%2$d'];
        yield 'no referer' => [''];
    }

    #[DataProvider('countedRefererProvider')]
    public function testASubmissionIsCountedInTheHourItWasMade(string $referer): void
    {
        $this->subscriber->onFormSubmit($this->createEvent($this->referer($referer), new \DateTime('2026-11-13 10:42:00')));
        $this->subscriber->onFormSubmit($this->createEvent($this->referer($referer), new \DateTime('2026-11-13 10:58:00')));

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
        yield 'the preview in Mautic' => ['http://localhost/s/forms/preview/%1$d'];
        yield 'the preview with a query string' => ['http://localhost/s/forms/preview/%1$d?email=test@example.com'];
        yield 'the preview with a trailing slash' => ['http://localhost/s/forms/preview/%1$d/'];
        yield 'the preview on another host' => ['https://mautic.example.com/s/forms/preview/%1$d'];
    }

    #[DataProvider('previewRefererProvider')]
    public function testASubmissionFromThePreviewInMauticIsNotCounted(string $referer): void
    {
        $this->subscriber->onFormSubmit($this->createEvent($this->referer($referer), new \DateTime()));

        $this->assertSame(0, $this->submissionTotalRepository->getTotal($this->form->getId()));
    }

    public function testNothingIsCountedWhileAggregateAnalyticsAreOff(): void
    {
        $this->subscriber->onFormSubmit($this->createEvent('https://example.com/landing', new \DateTime()));

        $this->assertSame(0, $this->submissionTotalRepository->getTotal($this->form->getId()));
    }

    private function createForm(string $name): Form
    {
        $form = new Form();
        $form->setName($name);
        $form->setAlias(strtolower(str_replace(' ', '-', $name)));
        $this->em->persist($form);
        $this->em->flush();

        return $form;
    }

    private function referer(string $template): string
    {
        return sprintf($template, $this->form->getId(), $this->otherForm->getId());
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
