<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Integration\Interfaces;

interface ConfigFormAuthInterface
{
    /**
     * Return the name of the form type service for the authorization tab which should include all the fields required for the API to work.
     */
    public function getAuthConfigFormName(): string;
}
