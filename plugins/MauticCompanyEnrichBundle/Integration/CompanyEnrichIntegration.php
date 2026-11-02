<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCompanyEnrichBundle\Integration;

use Mautic\IntegrationsBundle\Integration\BasicIntegration;
use Mautic\IntegrationsBundle\Integration\Interfaces\BasicInterface;

class CompanyEnrichIntegration extends BasicIntegration implements BasicInterface
{
    public function getName(): string
    {
        return 'CompanyEnrich';
    }

    public function getIcon(): string
    {
        return 'plugins/MauticCompanyEnrichBundle/Assets/img/companyenrich.svg';
    }
}
