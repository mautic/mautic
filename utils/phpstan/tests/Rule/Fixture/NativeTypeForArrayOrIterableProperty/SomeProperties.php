<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\NativeTypeForArrayOrIterableProperty;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

final class SomeProperties
{
    /**
     * @var string[]
     */
    private $items = [];

    /**
     * @var iterable<int>
     */
    private $values = [];

    /**
     * @var string[]|null
     */
    private $skipNullable = null;

    /**
     * @var string[]
     */
    private mixed $mixedItems = [];

    /**
     * @var string[]
     */
    private $skipNoDefault;

    /**
     * @var string[]
     */
    private array $skipNativeItems = [];

    /**
     * @var Collection<int, string>
     */
    private $skipCollection = null;

    /**
     * @var string
     */
    private $skipScalar = '';

    /**
     * @var string[]
     */
    #[ORM\Column]
    private $skipOrmMapped = [];
}
