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

class AnonymizeContactCompanyData
{
    public const COLUMNS_NOT_ACCEPTED = ['submission_id', 'form_id'];

    public function __construct(
        private readonly FieldModel $fieldModel,
        private readonly LoggerInterface $logger,
        private readonly EmailModel $emailModel,
        private readonly SubmissionModel $submissionModel,
    ) {
    }

    /**
     * @param array<Lead>|array<Company> $leadsCompanies
     *
     * @return array<mixed>
     */
    public function setHashes(array $leadsCompanies, LeadField $field, bool $pseudonymize): array
    {
        foreach ($leadsCompanies as $key => $leadCompany) {
            $leadField = $leadCompany->getField($field->getAlias());
            if (false === $leadField) {
                continue;
            }
            $fieldTmp     = $this->fieldModel->getRepository()->getEntity($leadField['id']);
            if (!$fieldTmp instanceof LeadField) {
                continue;
            }
            $leadsCompanies[$key] = $this->setHash($leadCompany, $leadField, $fieldTmp, $pseudonymize);
        }

        return $leadsCompanies;
    }

    /**
     * @param array<string, string|null> $field
     */
    private function setHash(
        Company|Lead $leadOrCompany,
        array $field,
        LeadField $leadField,
        bool $isToPseudonymize,
    ): Lead|Company {
        if (!array_key_exists('value', $field) || empty($field['value'])) {
            return $leadOrCompany;
        }
        $isEmail = ('email' === ($field['type'] ?? ''));
        $limit   = (int) $leadField->getCharLengthLimit();

        try {
            if ($isEmail) {
                $valueAnonymized = AnonymizeHelper::anonymizeEmail($field['value'], $isToPseudonymize, $limit);
                $this->updateEmailStatusValues($field['value'], $valueAnonymized, $isToPseudonymize);
            } else {
                $valueAnonymized = AnonymizeHelper::anonymizeText($field['value'], $isToPseudonymize, $limit);
            }

            $leadOrCompany->addUpdatedField($leadField->getAlias(), $valueAnonymized);
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
     * @return array<mixed>
     */
    public function setLeadsCompaniesFieldNull(array $leadsCompanies, LeadField $field): array
    {
        foreach ($leadsCompanies as $key => $leadCompany) {
            $leadField = $leadCompany->getField($field->getAlias());
            if (false === $leadField) {
                continue;
            }

            $leadCompany->addUpdatedField($field->getAlias(), null);
            $leadsCompanies[$key] = $leadCompany;
        }

        return $leadsCompanies;
    }

    // To do, run tests here
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
}
