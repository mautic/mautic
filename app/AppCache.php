<?php

require_once __DIR__.'/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache;

/**
 * @deprecated since Mautic 5.0, to be removed in 6.0 with no replacement.
 */
class AppCache extends HttpCache
{
}
