<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Sandbox;

use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Sandbox\SecurityPolicyInterface;

/**
 * Denylist-based sandbox policy for user-uploaded theme templates.
 *
 * Blocks only dangerous functions and filters that could lead to
 * RCE or data leakage (GHSA-9fx4-7cmj-47vg), while allowing all
 * legitimate Mautic and plugin Twig functions.
 */
final class ThemeSandboxPolicy implements SecurityPolicyInterface
{
    /**
     * Twig functions that must never execute in theme templates.
     * These can leak credentials, read files, or execute OS commands.
     *
     * @var string[]
     */
    private const DENIED_FUNCTIONS = [
        'configGetParameter', // leaks DB password, secret key, etc.
        'getEntity',          // arbitrary ORM data extraction
        'getEntities',        // arbitrary ORM data extraction
        'source',             // arbitrary file read
        'ini_get',            // PHP config leakage
        'is_file',            // filesystem probing
        'get_class',          // reflection
        'method_exists',      // reflection
        'is_class',           // reflection
        'is_extension_loaded', // system probing
        'is_function',        // reflection
    ];

    /**
     * Twig filters that must never execute in theme templates.
     * map/reduce/filter accept PHP callable strings, enabling RCE.
     *
     * @var string[]
     */
    private const DENIED_FILTERS = [
        'map',    // {{ ['id']|map('system')|join }} → RCE
        'reduce', // {{ ['id']|reduce('system') }} → RCE
        'filter', // {{ ['id']|filter('system') }} → RCE
    ];

    /**
     * @param string[] $tags
     * @param string[] $filters
     * @param string[] $functions
     *
     * @throws SecurityError
     */
    public function checkSecurity($tags, $filters, $functions): void
    {
        foreach ($filters as $filter) {
            if (in_array($filter, self::DENIED_FILTERS, true)) {
                throw new SecurityNotAllowedFilterError(sprintf('Filter "%s" is not allowed in theme templates.', $filter), $filter);
            }
        }

        foreach ($functions as $function) {
            if (in_array($function, self::DENIED_FUNCTIONS, true)) {
                throw new SecurityNotAllowedFunctionError(sprintf('Function "%s" is not allowed in theme templates.', $function), $function);
            }
        }
    }

    public function checkMethodAllowed($obj, $method): void
    {
        // All method calls allowed — methods are not exploitable via SSTI
    }

    public function checkPropertyAllowed($obj, $property): void
    {
        // All property access allowed
    }
}
