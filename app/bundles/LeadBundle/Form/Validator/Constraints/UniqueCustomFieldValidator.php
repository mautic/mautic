<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\Validator\Constraints;

use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueCustomFieldValidator extends ConstraintValidator
{
    private LeadModel $leadModel;
    private CompanyModel $companyModel;
    private FieldModel $fieldModel;

    public function __construct(LeadModel $leadModel, CompanyModel $companyModel, FieldModel $fieldModel)
    {
        $this->leadModel    = $leadModel;
        $this->companyModel = $companyModel;
        $this->fieldModel   = $fieldModel;
    }

    /**
     * @param Lead|Company|mixed $object
     */
    public function validate($object, Constraint $constraint): void
    {
        \assert($constraint instanceof UniqueCustomField);
        \assert($object instanceof Lead || $object instanceof Company);

        $form = $this->context->getRoot();
        \assert($form instanceof Form);

        $publishedUniqueFields = [];
        $leadFields            = $this->fieldModel->getPublishedFieldArrays($constraint->object);
        foreach ($leadFields as $field) {
            if (false === $field['isPublished'] || $constraint->object !== $field['object']) {
                continue;
            }

            if ($field['isUniqueIdentifer']) {
                $publishedUniqueFields[] = $field['alias'];
            }
        }

        $publishedUniqueFields = array_unique($publishedUniqueFields);

        foreach ($publishedUniqueFields as $publishedUniqueField) {
            if (!$form->has($publishedUniqueField)) {
                continue;
            }

            $data = $form->get($publishedUniqueField)->getData();
            if (null === $data || '' === $data) {
                continue;
            }

            if ($object instanceof Lead && $this->isLeadFieldValid($object, $publishedUniqueField, $data)) {
                return;
            }

            if ($object instanceof Company && $this->isCompanyFieldValid($object, $publishedUniqueField, $data)) {
                return;
            }

            $this->context->buildViolation($constraint->message)
                ->setCode((string) Response::HTTP_UNPROCESSABLE_ENTITY)
                ->atPath($publishedUniqueField)
                ->addViolation();
        }
    }

    /**
     * @param mixed $data
     */
    private function isLeadFieldValid(Lead $lead, string $fieldName, $data): bool
    {
        // Can't use getEntities, because it refreshes some field data, that can be used in the form
        $leads = $this->leadModel->getRepository()->getLeadIdsByUniqueFields([
            $fieldName => $data,
        ]);

        $leadsCount = count($leads);
        if (0 === $leadsCount) {
            return true;
        }

        if ($leadsCount > 1) {
            return false;
        }

        if ((int) $leads[0]['id'] === (int) $lead->getId()) {
            return true;
        }

        return false;
    }

    /**
     * @param mixed $data
     */
    private function isCompanyFieldValid(Company $company, string $fieldName, $data): bool
    {
        // Can't use getEntities, because it refreshes some field data, that can be used in the form
        $companies = $this->companyModel->getRepository()->getCompaniesByUniqueFields([
            $fieldName => $data,
        ]);

        $companiesCount = count($companies);
        if (0 === $companiesCount) {
            return true;
        }

        if ($companiesCount > 1) {
            return false;
        }

        if ((int) $companies[0]['id'] === (int) $company->getId()) {
            return true;
        }

        return false;
    }
}
