<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Form\DataTransformer;

use Mautic\ConfigBundle\Form\Type\EscapeTransformer;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\Dsn\Dsn;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * @implements DataTransformerInterface<string, array>
 */
class DsnTransformer implements DataTransformerInterface
{
    private const PASSWORD_MASK = '🔒';

    public function __construct(private CoreParametersHelper $coreParametersHelper, private EscapeTransformer $escapeTransformer)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function transform($value): array
    {
        // unescape the DSN before the transformation to array
        $value = $this->escapeTransformer->transform((string) $value);

        try {
            $dsn = Dsn::fromString($value);
        } catch (\InvalidArgumentException) {
            return [];
        }

        return [
            'scheme'   => $dsn->getScheme(),
            'host'     => $dsn->getHost(),
            'user'     => $dsn->getUser(),
            'password' => $dsn->getPassword() ? self::PASSWORD_MASK : null,
            'port'     => $dsn->getPort(),
            'path'     => $dsn->getPath(),
            'options'  => $dsn->getOptions(),
        ];
    }

    /**
     * @param array<string, mixed> $value
     */
    public function reverseTransform($value): string
    {
        // unescape the values as they are escaped by the escape transformer applied to the child elements
        $value = $this->escapeTransformer->transform($value);

        $dsn = new Dsn(
            (string) $value['scheme'],
            (string) $value['host'],
            $value['user'],
            $value['password'],
            $value['port'] ? (int) $value['port'] : null,
            $value['path'],
            $value['options'],
        );

        if (self::PASSWORD_MASK === $dsn->getPassword()) {
            $previousDsn = Dsn::fromString($this->coreParametersHelper->get('mailer_dsn'));
            $dsn         = $dsn->setPassword($previousDsn->getPassword());
        }

        // escape the DSN to prevent "missing parameter" errors
        return $this->escapeTransformer->reverseTransform((string) $dsn);
    }
}
