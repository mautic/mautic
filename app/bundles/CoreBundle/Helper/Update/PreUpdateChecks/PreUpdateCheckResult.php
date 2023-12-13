<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper\Update\PreUpdateChecks;

class PreUpdateCheckResult
{
    /**
     * @var PreUpdateCheckError[]
     */
    public array $errors;

    /**
     * @param PreUpdateCheckError[] $errors
     */
    public function __construct(
        public bool $success,
        public ?AbstractPreUpdateCheck $check,
        array $errors = []
    ) {
        foreach ($errors as $error) {
            if (!($error instanceof PreUpdateCheckError)) {
                throw new \InvalidArgumentException('Error must be of type PreUpdateCheckError');
            }
        }

        $this->errors = $errors;
    }
}
