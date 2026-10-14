<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\NullableObjectPropertyMustHaveNativeType;

final class SomeClass
{
    /**
     * @var IpAddress|null
     */
    private $ipAddress;

    /**
     * @var \DateTimeInterface|null
     */
    private $createdAt;

    private ?IpAddress $skipNativeTyped = null;

    /**
     * @var IpAddress|null
     */
    private $skipWithDefault = null;

    /**
     * @var IpAddress
     */
    private $skipNonNullable;

    /**
     * @var string|null
     */
    private $skipScalar;

    /**
     * @var IpAddress|\DateTimeInterface|null
     */
    private $skipTwoObjects;

    /**
     * @var IpAddress|null
     */
    protected $skipProtected;
}

final class IpAddress
{
}
