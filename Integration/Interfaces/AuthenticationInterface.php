<?php

namespace MauticPlugin\IntegrationsBundle\Integration\Interfaces;

interface AuthenticationInterface
{
    public function getRequiredKeyFields(): array;

    public function getClientIdKey(): string;

    public function getClientSecretKey(): string;

    public function getAuthTokenKey(): string;

    public function getApiUrl(): string;
}
