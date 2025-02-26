<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class InfiniteLoopValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof InfiniteLoop) {
            throw new UnexpectedTypeException($constraint, InfiniteLoop::class);
        }

        $data = $this->context->getRoot()->getData();

        $this->validateEvent($this->context, $data['triggerMode'] ?? '', $value, (int) ($data['triggerInterval'] ?? 0), $data['triggerIntervalUnit'] ?? '');
    }

    /**
     * @param string[] $addTo
     */
    public function validateEvent(ExecutionContextInterface $context, string $triggerMode, array $addTo, int $triggerInterval, string $triggerIntervalUnit): void
    {
        if (!in_array('this', $addTo)) {
            return;
        }

        if ('immediate' === $triggerMode) {
            $context->buildViolation('mautic.campaign.infiniteloop.immediate')->addViolation();

            return;
        }

        if ('interval' === $triggerMode && 'i' === $triggerIntervalUnit && $triggerInterval < 30) {
            $context->buildViolation('mautic.campaign.infiniteloop.interval')
                ->setParameter('%count%', (string) $triggerInterval)
                ->addViolation();
        }
    }
}
