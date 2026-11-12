<?php

declare(strict_types=1);

namespace MauticPlugin\MauticEmailMarketingBundle\Form\Type;

final class ConstantContactKeysType extends AbstractKeysType
{
    protected function getKeys(): array
    {
        return [
            'client_id'    => 'mautic.integration.keyfield.api',
            'access_token' => 'mautic.constantcontact.keyfield.access_token',
        ];
    }

    protected function getSecretKeys(): array
    {
        return ['access_token'];
    }
}
