<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\GenericTraitUse;

class SkipDocumentedAndPlainTraitUse
{
    /** @use SomeGenericTrait<SkipDocumentedAndPlainTraitUse> */
    use SomeGenericTrait;

    use SomePlainTrait;
}
