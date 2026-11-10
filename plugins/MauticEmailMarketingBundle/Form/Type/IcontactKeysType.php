<?php

declare(strict_types=1);

namespace MauticPlugin\MauticEmailMarketingBundle\Form\Type;

final class IcontactKeysType extends AbstractKeysType
{
    protected function getKeys(): array
    {
        return [
            'API-AppId'    => 'mautic.icontact.keyfield.appid',
            'API-Username' => 'mautic.icontact.keyfield.username',
            'API-Password' => 'mautic.icontact.keyfield.password',
        ];
    }

    protected function getSecretKeys(): array
    {
        return ['API-Password'];
    }
}
