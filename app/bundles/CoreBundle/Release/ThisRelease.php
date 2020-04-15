<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://www.mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
