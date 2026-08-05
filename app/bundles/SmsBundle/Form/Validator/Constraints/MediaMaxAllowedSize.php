<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final class MediaMaxAllowedSize extends Constraint
{
    public string $message;

    /**
     * @param string[]|null $groups
     */
    #[HasNamedArguments]
    public function __construct(
        string $message = 'mautic.sms.form.max.size.media.error',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);

        $this->message = $message;
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
