<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper\Update\PreUpdateChecks;

use Mautic\CoreBundle\Release\Metadata;

abstract class AbstractPreUpdateCheck
{
    private Metadata $updateCandidateMetadata;

    abstract public function runCheck(): PreUpdateCheckResult;

    /**
     * Sets the Metadata object so that pre-update checks have the new
     * version's metadata available to perform checks against.
     */
    public function setUpdateCandidateMetadata(Metadata $metadata): void
    {
        $this->updateCandidateMetadata = $metadata;
    }

    /**
     * Gets the metadata of the Mautic version that we're trying to update to.
     */
    public function getUpdateCandidateMetadata(): Metadata
    {
        return $this->updateCandidateMetadata;
    }
}
