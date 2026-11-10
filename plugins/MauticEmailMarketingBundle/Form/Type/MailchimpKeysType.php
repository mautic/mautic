<?php

declare(strict_types=1);

namespace MauticPlugin\MauticEmailMarketingBundle\Form\Type;

final class MailchimpKeysType extends AbstractKeysType
{
    protected function getKeys(): array
    {
        return [
            'password' => 'mautic.integration.keyfield.api',
        ];
    }

    protected function getSecretKeys(): array
    {
        return ['password'];
    }

    protected function getMissingKeys(array $keys): array
    {
        // Accounts connected through the former OAuth2 flow keep working with their saved token
        if (!empty($keys['access_token']) && !empty($keys['api_endpoint'])) {
            return [];
        }

        return parent::getMissingKeys($keys);
    }
}
