<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\BoolNullPropertyDocblockToNativeType;

class SomeClass extends SomeParent
{
    /**
     * @var bool|null
     */
    private $isPreferenceCenter;

    /**
     * @var ?bool
     */
    protected $shortNullable = false;

    /**
     * @var bool|null
     */
    private ?bool $alreadyTyped = null;

    /**
     * @var bool
     */
    private $plainBool = false;

    /**
     * @var string|null
     */
    private $otherNullable;

    /**
     * @var bool|null
     */
    protected $inherited;
}

class SomeParent
{
    /**
     * @var bool|null
     */
    protected $inherited;
}
