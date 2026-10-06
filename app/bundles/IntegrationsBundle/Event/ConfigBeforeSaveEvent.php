<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Event;

// Dispatched before the integration configuration is saved.
final class ConfigBeforeSaveEvent extends ConfigSaveEvent
{
}
