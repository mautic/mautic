<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper\Update\PreUpdateChecks;

class PreUpdateCheckError
{
    /**
     * Create a new error. Errors are supposed to be translatable, so please provide proper translation keys. If that's not possible, please provide a clear error message in English.
     *
     * @param string               $key        The translation key (e.g. mautic.core.update.check.phpversion)
     * @param array<string,string> $parameters Optional parameters that you want to pass to the translation key, e.g. ['%currentversion%' => '7.4.2']
     */
    public function __construct(
        public string $key,
        public array $parameters = [],
    ) {
    }
}
