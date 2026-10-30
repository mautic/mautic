<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Doctrine\Common\Collections\ArrayCollection;

final class NonEntityCollectionParam
{
    public function setItems(ArrayCollection $items): void
    {
    }
}
