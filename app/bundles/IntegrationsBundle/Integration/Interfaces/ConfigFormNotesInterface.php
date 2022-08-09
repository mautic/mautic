<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Integration\Interfaces;

use Mautic\IntegrationsBundle\DTO\Note;

interface ConfigFormNotesInterface
{
    public function getAuthorizationNote(): ?Note;

    public function getFeaturesNote(): ?Note;

    public function getFieldMappingNote(): ?Note;
}
