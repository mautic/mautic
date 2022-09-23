<?php

namespace Mautic\CoreBundle\Release;

class ThisRelease
{
    public static function getMetadata(): Metadata
    {
        $metadata = json_decode(
            file_get_contents(__DIR__.'/../../../release_metadata.json'),
            true
        );

        return new Metadata($metadata);
    }
}
