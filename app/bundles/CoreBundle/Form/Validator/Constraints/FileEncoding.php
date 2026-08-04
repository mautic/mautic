<?php

namespace Mautic\CoreBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class FileEncoding extends Constraint
{
    public string $encodingFormatMessage;

    /**
     * @var string|string[]
     */
    public string|array $encodingFormat;

    /**
     * @param string|string[] $encodingFormat
     * @param string[]|null   $groups
     */
    #[HasNamedArguments]
    public function __construct(
        string $encodingFormatMessage = 'mautic.core.invalid_file_encoding',
        string|array $encodingFormat = '[UTF-8]',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);

        $this->encodingFormatMessage = $encodingFormatMessage;
        $this->encodingFormat        = $encodingFormat;
    }

    public function validatedBy(): string
    {
        return FileEncodingValidator::class;
    }
}
