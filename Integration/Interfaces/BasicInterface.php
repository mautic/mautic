<?php

namespace MauticPlugin\IntegrationsBundle\Integration\Interfaces;

interface BasicInterface {
    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return string
     */
    public function getDisplayName(): string;

    /**
     * @return int
     */
    public function getPriority(): int;

    /**
     * @return bool
     */
    public function isCoreIntegration(): bool;
}