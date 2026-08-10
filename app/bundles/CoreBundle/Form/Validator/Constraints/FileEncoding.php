<?php

namespace Mautic\CoreBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class FileEncoding extends Constraint
{
    /**
     * @param string|string[] $encodingFormat
     * @param string[]|null   $groups
     */
    #[HasNamedArguments]
    public function __construct(
        public string $encodingFormatMessage = 'mautic.core.invalid_file_encoding',
        public string|array $encodingFormat = '[UTF-8]',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);
    }

    public function validatedBy(): string
    {
        return FileEncodingValidator::class;
    }
}
