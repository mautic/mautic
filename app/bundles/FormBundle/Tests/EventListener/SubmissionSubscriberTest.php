<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\FormRepository;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\EventListener\SubmissionSubscriber;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class SubmissionSubscriberTest extends TestCase
{
    public function testPostPersistIncrementsCount(): void
    {
        $repository = $this->createMock(FormRepository::class);
        $repository->expects($this->once())->method('incrementSubmissionCount')->with(42);
        $repository->expects($this->never())->method('decrementSubmissionCount');

        new SubmissionSubscriber($repository)->postPersist($this->args($this->submissionForForm(42)));
    }

    public function testPostRemoveDecrementsCount(): void
    {
        $repository = $this->createMock(FormRepository::class);
        $repository->expects($this->once())->method('decrementSubmissionCount')->with(7);
        $repository->expects($this->never())->method('incrementSubmissionCount');

        new SubmissionSubscriber($repository)->postRemove($this->args($this->submissionForForm(7)));
    }

    public function testIgnoresNonSubmissionEntity(): void
    {
        $repository = $this->createMock(FormRepository::class);
        $repository->expects($this->never())->method('incrementSubmissionCount');
        $repository->expects($this->never())->method('decrementSubmissionCount');

        $subscriber = new SubmissionSubscriber($repository);
        $subscriber->postPersist($this->args(new \stdClass()));
        $subscriber->postRemove($this->args(new \stdClass()));
    }

    public function testIgnoresSubmissionWithoutForm(): void
    {
        $submission = new Submission();

        $repository = $this->createMock(FormRepository::class);
        $repository->expects($this->never())->method('incrementSubmissionCount');
        $repository->expects($this->never())->method('decrementSubmissionCount');

        $subscriber = new SubmissionSubscriber($repository);
        $subscriber->postPersist($this->args($submission));
        $subscriber->postRemove($this->args($submission));
    }

    private function submissionForForm(int $formId): Submission
    {
        $form = new Form();
        new \ReflectionProperty($form, 'id')->setValue($form, $formId);

        $submission = new Submission();
        $submission->setForm($form);

        return $submission;
    }

    private function args(object $entity): LifecycleEventArgs
    {
        $args = $this->createMock(LifecycleEventArgs::class);
        $args->method('getObject')->willReturn($entity);

        return $args;
    }
}
