<?php

namespace Mautic\LeadBundle\Services;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\FormBundle\Model\SubmissionModel;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Helper\AnonymizeHelper;
use Mautic\LeadBundle\Model\FieldModel;
use Psr\Log\LoggerInterface;

use function Symfony\Component\String\s;

class AnonymizeContactCompanyData
{
    public const COLUMNS_NOT_ACCEPTED = ['submission_id', 'form_id'];

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly EmailModel $emailModel,
        private readonly SubmissionModel $submissionModel,
        private readonly FieldModel $fieldModel,
    ) {
    }

    /**
     * @param array<Lead>|array<Company>||ArrayCollection<Company> $leadsCompanies
     *
     * @return array<mixed>|ArrayCollection<mixed>
     */
    public function setHashes($leadsCompanies, LeadField $field, bool $pseudonymize)
    {
        foreach ($leadsCompanies as $key => $leadCompany) {
            $alias = $field->getAlias();

            $leadFieldValue = $leadCompany->getFieldValue($alias);
            if (null === $leadFieldValue) {
                if (!str_contains($field->getAlias(), 'company')) {
                    continue;
                }
                $alias          = s($field->getAlias())->replace('company', '')->toString();
                $leadFieldValue = $leadCompany->getFieldValue($alias);
                if (null === $leadFieldValue) {
                    continue;
                }
            }

            $leadsCompanies[$key] = $this->setHash($leadCompany, $leadFieldValue, $field, $pseudonymize, $alias);
        }

        return $leadsCompanies;
    }

    private function setHash(
        Company|Lead $leadOrCompany,
        string $fieldValue,
        LeadField $leadField,
        bool $isToPseudonymize,
        string $alias,
    ): Lead|Company {
        $isEmail = ('email' === $leadField->getType());
        $limit   = (int) $leadField->getCharLengthLimit();

        try {
            if ($isEmail) {
                $valueAnonymized = AnonymizeHelper::anonymizeEmail($fieldValue, $isToPseudonymize, $limit);
                $this->updateEmailStatusValues($fieldValue, $valueAnonymized, $isToPseudonymize);
            } else {
                $valueAnonymized = AnonymizeHelper::anonymizeText($fieldValue, $isToPseudonymize, $limit);
            }

            if (
                (
                    $leadOrCompany instanceof Lead && $this->isObjectField('lead', $alias)
                )
                || (
                    $leadOrCompany instanceof Company && $this->isObjectField('company', $alias)
                )
            ) {
                $leadOrCompany->addUpdatedField($alias, $valueAnonymized);
            }
        } catch (\Exception $e) {
            // Do nothing
            $this->logger->error('AnonymizeUserDataSubscriber setHash fail: '.$e->getMessage());
        }

        return $leadOrCompany;
    }

    private function updateEmailStatusValues(string $email, string $hash, bool $pseudonymize): void
    {
        // email_stats.email_address
        $emailStats = $this->emailModel->getStatRepository()->findBy(['emailAddress' => $email]);
        foreach ($emailStats as $emailStat) {
            if (!$pseudonymize) {
                $hash = AnonymizeHelper::anonymizeEmail($email, $pseudonymize);
            }

            $emailStat->setEmailAddress($hash);
            $this->emailModel->getStatRepository()->saveEntity($emailStat);
        }
    }

    /**
     * @param array<Lead|Company> $leadsCompanies
     *
     * @return array<mixed>|ArrayCollection<mixed>
     */
    public function setLeadsCompaniesFieldNull($leadsCompanies, LeadField $field)
    {
        foreach ($leadsCompanies as $key => $leadCompany) {
            //            $leadField = $leadCompany->getField($field->getAlias());
            $alias          = $field->getAlias();
            $leadFieldValue = $leadCompany->getFieldValue($alias);
            if (null === $leadFieldValue) {
                if (!str_contains($field->getAlias(), 'company')) {
                    continue;
                }
                $alias          = s($field->getAlias())->replace('company', '')->toString();
                $leadFieldValue = $leadCompany->getFieldValue($alias);
                if (null === $leadFieldValue) {
                    continue;
                }
            }

            if (
                (
                    $leadCompany instanceof Lead && $this->isObjectField('lead', $alias)
                )
                || (
                    $leadCompany instanceof Company && $this->isObjectField('company', $alias)
                )
            ) {
                $leadCompany->addUpdatedField($alias, null);
                $leadsCompanies[$key] = $leadCompany;
            }
        }

        return $leadsCompanies;
    }

    public function updateFormResults(ArrayCollection $leads, bool $pseudonymize): void
    {
        $valueSubmissionForm = [];
        foreach ($leads as $lead) {
            $submissionForms = $this->submissionModel->getRepository()->findBy(['lead' => $lead]);

            foreach ($submissionForms as $submissionForm) {
                $id                    = $submissionForm->getForm()->getId();
                $alias                 = $submissionForm->getForm()->getAlias();
                if ($pseudonymize) {
                    $formsToUpdate            = $this->submissionModel->getSubmissionsByForm($id, $alias, $submissionForm);
                    $newValueSubmissionForm   = $this->anonymizeForms($formsToUpdate);
                    $valueSubmissionForm      = array_merge($valueSubmissionForm, $newValueSubmissionForm);
                }

                $this->submissionModel->updateSubmissionAnonymizeByLead($id, $alias, $submissionForm, $valueSubmissionForm);
            }
        }
    }

    /**
     * @param array<array<string>> $forms
     *
     * @return array<string>
     */
    private function anonymizeForms(array $forms): array
    {
        if (empty($forms)) {
            return [];
        }
        $results = [];
        foreach ($forms as $form) {
            foreach ($form as $keyColumn => $value) {
                if (!in_array($keyColumn, self::COLUMNS_NOT_ACCEPTED)) {
                    $results[$value] = AnonymizeHelper::anonymizeText($value, true);
                }
            }
        }

        return $results;
    }

    private function isObjectField(string $object, string $alias): bool
    {
        $fields = $this->fieldModel->getPublishedFieldArrays($object);
        if (!$fields instanceof \Doctrine\ORM\Tools\Pagination\Paginator) {
            return false;
        }
        if (0 === $fields->count()) {
            return false;
        }
        $fields = $fields->getIterator()->getArrayCopy();
        $fields = array_column($fields, null, 'alias');

        return array_key_exists($alias, $fields);
    }
}
