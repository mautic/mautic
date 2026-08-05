<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCloudStorageBundle\Integration\Support;

use Mautic\IntegrationsBundle\Integration\DefaultConfigFormTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormAuthInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormFeaturesInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;
use MauticPlugin\MauticCloudStorageBundle\Form\Type\AmazonS3KeysType;
use MauticPlugin\MauticCloudStorageBundle\Integration\AmazonS3Integration;

final class AmazonS3ConfigSupport extends AmazonS3Integration implements ConfigFormInterface, ConfigFormAuthInterface, ConfigFormFeaturesInterface
{
    use DefaultConfigFormTrait;

    public function getAuthConfigFormName(): string
    {
        return AmazonS3KeysType::class;
    }
}
