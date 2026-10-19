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

    /**
     * @var ?IpAddress
     */
    private $prefixNullable;

    /**
     * @var class-string|null
     */
    private $skipClassStringPseudoType;

    /**
     * @var positive-int|null
     */
    private $skipPositiveIntPseudoType;

    /**
     * @var non-empty-string|null
     */
    private $skipNonEmptyStringPseudoType;

    /**
     * @var $this|null
     */
    private $skipThisPseudoType;
}

final class IpAddress
{
}

/**
 * @template TEntity of object
 */
trait SomeGenericTrait
{
    /**
     * @var TEntity|null
     */
    private $skipTemplateType;
}
