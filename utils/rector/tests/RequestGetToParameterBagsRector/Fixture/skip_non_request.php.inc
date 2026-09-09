<?php

namespace Utils\Rector\Tests\RequestGetToParameterBagsRector\Fixture;

use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;

class SkipNonRequest
{
    public function run(InputBag $bag, Request $request, string $key)
    {
        // Not a Request instance
        $value = $bag->get('key');

        // Dynamic key cannot be migrated safely
        $other = $request->get($key);
    }
}

?>
