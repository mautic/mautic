<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCompanyEnrichBundle\Integration\Support;

use Mautic\IntegrationsBundle\Integration\DefaultConfigFormTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormAuthInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;
use MauticPlugin\MauticCompanyEnrichBundle\Form\Type\CompanyEnrichKeysType;
use MauticPlugin\MauticCompanyEnrichBundle\Integration\CompanyEnrichIntegration;

final class ConfigSupport extends CompanyEnrichIntegration implements ConfigFormInterface, ConfigFormAuthInterface
{
    use DefaultConfigFormTrait;

    public function getAuthConfigFormName(): string
    {
        return CompanyEnrichKeysType::class;
    }
}
