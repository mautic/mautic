<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Tests\Fixtures\Model;

use Mautic\EmailBundle\Helper\EmailValidator;
use Mautic\LeadBundle\Deduplicate\CompanyDeduper;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel;

class CompanyModelStub extends CompanyModel
{
    public function setFieldModel(FieldModel $fieldModel): void
    {
        $this->leadFieldModel = $fieldModel;
    }

    public function setEmailValidator(EmailValidator $validator): void
    {
        $this->emailValidator = $validator;
    }

    public function setCompanyDeduper(CompanyDeduper $companyDeduper): void
    {
        $this->companyDeduper = $companyDeduper;
    }
}
