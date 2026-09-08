<?php

namespace Utils\Rector\Tests\UnserializeToSerializerDecodeRector\Fixture;

class SkipClassWhitelist
{
    public function run(string $data)
    {
        return unserialize($data, ['allowed_classes' => [\stdClass::class]]);
    }
}
