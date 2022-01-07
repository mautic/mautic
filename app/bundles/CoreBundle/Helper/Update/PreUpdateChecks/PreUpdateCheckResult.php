<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper\Update\PreUpdateChecks;

use InvalidArgumentException;

class PreUpdateCheckResult
{
    public bool $success;
    public ?AbstractPreUpdateCheck $check;

    /**
     * @var PreUpdateCheckError[]
     */
    public array $errors;

    /**
     * @param PreUpdateCheckError[] $errors
     */
    public function __construct(bool $success, ?AbstractPreUpdateCheck $check, array $errors = [])
    {
        $this->success = $success;
        $this->check   = $check;

        foreach ($errors as $error) {
            if (!($error instanceof PreUpdateCheckError)) {
                throw new InvalidArgumentException('Error must be of type PreUpdateCheckError');
            }
        }

        $this->errors = $errors;
    }
}
