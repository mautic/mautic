<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Form\DataTransformer;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\Dsn\Dsn;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * @implements DataTransformerInterface<string, string>
 */
class DsnTransformer implements DataTransformerInterface
{
    private const PASSWORD_MASK = '🔒';

    public function __construct(private CoreParametersHelper $coreParametersHelper)
    {
    }

    public function transform($value): string
    {
        $dsn = Dsn::fromString((string) $value);

        if ($dsn->getPassword()) {
            $dsn = $dsn->setPassword(self::PASSWORD_MASK);
        }

        return (string) $dsn;
    }

    public function reverseTransform($value): string
    {
        $dsn = Dsn::fromString($value);

        if (self::PASSWORD_MASK === $dsn->getPassword()) {
            $previousDsn = Dsn::fromString($this->coreParametersHelper->get('mailer_dsn'));
            $dsn         = $dsn->setPassword($previousDsn->getPassword());
        }

        return (string) $dsn;
    }
}
