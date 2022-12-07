<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\Validator\Constraints;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueLeadFieldValidator extends ConstraintValidator
{
    private LeadModel $leadModel;

    public function __construct(LeadModel $leadModel)
    {
        $this->leadModel = $leadModel;
    }

    /**
     * @param Lead $lead
     */
    public function validate($lead, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueLeadField) {
            throw new UnexpectedTypeException($constraint, UniqueLeadField::class);
        }

        $form = $this->context->getRoot();
        if (!$form instanceof Form) {
            throw new UnexpectedTypeException($form, Form::class);
        }

        // Can't use getEntities, because it refreshes some field data, that can be used in the form
        $leads = $this->leadModel->getRepository()->getLeadIdsByUniqueFields([
            'email' => $form->get($constraint->field)->getData(),
        ]);

        if (0 === count($leads)) {
            return;
        }

        if ((int) $leads[0]['id'] === (int) $lead->getId()) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setCode((string) Response::HTTP_UNPROCESSABLE_ENTITY)
            ->atPath($constraint->field)
            ->addViolation();
    }
}
