<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Sandbox;

use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityNotAllowedPropertyError;
use Twig\Sandbox\SecurityPolicyInterface;

/**
 * Sandbox policy for user-uploaded theme templates.
 *
 * Restricts method calls to read-only accessors on Mautic-namespaced objects
 * and blocks dangerous Twig functions/filters. Sensitive getters that could
 * leak credentials or perform I/O are explicitly denied.
 */
final class ThemeSandboxPolicy implements SecurityPolicyInterface
{
    /**
     * Namespaces whose objects may be accessed from theme templates.
     * Entities passed to the render context (Email, Lead, Page, Form, ...)
     * all live under these prefixes.
     *
     * @var string[]
     */
    private const ALLOWED_OBJECT_NAMESPACES = [
        'Mautic\\',
        'MauticPlugin\\',
    ];

    /**
     * Only read-only accessor methods are allowed. Mutators, actions, and
     * anything that could produce a side effect are refused.
     *
     * @var string[]
     */
    private const ALLOWED_METHOD_PREFIXES = [
        'get',
        'is',
        'has',
    ];

    /**
     * Specific methods that are blocked even if they match the accessor pattern.
     * These could leak sensitive data or perform I/O operations.
     *
     * @var string[]
     */
    private const DENIED_METHODS = [
        'getpassword',          // User::getPassword() - leaks password hashes
        'getfilecontents',      // Asset::getFileContents() - arbitrary file read
        'getsecret',            // Webhook/UserToken::getSecret() - API secrets
        'gettoken',             // potential auth tokens
        'getapikey',            // potential API keys
        'getcredentials',       // potential credentials
        'getprivatekey',        // potential private keys
        'getsalt',              // User::getSalt() - password salt
        'gettokenselector',     // UserInvite::getTokenSelector() - invite token
        'gettokenverifierhash', // UserInvite::getTokenVerifierHash() - invite verifier
    ];

    /**
     * Methods allowed even though they don't match accessor prefixes.
     * These are needed for form template overrides and don't pose security risks.
     *
     * @var string[]
     */
    private const ALLOWED_METHODS = [
        'showforcontact',           // Field::showForContact() - display logic for progressive profiling
        'increasedisplayedfields',  // DisplayManager::increaseDisplayedFields() - field counter
        'generateformname',         // Form::generateFormName() - unique form identifier
        'setcustomparameters',      // Field::setCustomParameters() - custom field config
        'settype',                  // Field::setType() - field type override
        'converttoarray',           // Entity conversion for template rendering
    ];

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
        'map',          // {{ ['id']|map('system')|join }} → RCE
        'reduce',       // {{ ['id']|reduce('system') }} → RCE
        'filter',       // {{ ['id']|filter('system') }} → RCE
        'less_compile', // {{ '@import (inline) "/etc/passwd";'|less_compile }} → arbitrary file read via Less @import
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

    /**
     * @param object $obj
     * @param string $method
     *
     * @throws SecurityNotAllowedMethodError
     */
    public function checkMethodAllowed($obj, $method): void
    {
        // Twig\Markup is a string wrapper used for captured blocks ({% set x %}...{% endset %});
        // __toString just returns the captured content, so it is safe to allow.
        if ($obj instanceof Markup && '__toString' === $method) {
            return;
        }

        if (!$this->isAllowedObject($obj)) {
            throw new SecurityNotAllowedMethodError(sprintf('Calling "%s" on a "%s" object is not allowed in theme templates.', $method, get_debug_type($obj)), get_debug_type($obj), $method);
        }

        $normalized = strtolower($method);

        // Block explicitly denied methods (sensitive data exposure, I/O)
        if (in_array($normalized, self::DENIED_METHODS, true)) {
            throw new SecurityNotAllowedMethodError(sprintf('Calling "%s" on a "%s" object is not allowed in theme templates.', $method, get_debug_type($obj)), $obj::class, $method);
        }

        // Allow explicitly permitted methods (form template support)
        if (in_array($normalized, self::ALLOWED_METHODS, true) && method_exists($obj, $method)) {
            return;
        }

        // Reject magic methods (__toString, __call, __get, ...) outright
        $isAccessor = !str_starts_with($normalized, '__');

        if ($isAccessor) {
            $isAccessor = false;
            foreach (self::ALLOWED_METHOD_PREFIXES as $prefix) {
                if (str_starts_with($normalized, $prefix)) {
                    $isAccessor = true;
                    break;
                }
            }
        }

        // method_exists() is false for names handled only via __call, so this
        // also blocks accessor-looking names dispatched through a magic method.
        if (!$isAccessor || !method_exists($obj, $method)) {
            throw new SecurityNotAllowedMethodError(sprintf('Calling "%s" on a "%s" object is not allowed in theme templates.', $method, get_debug_type($obj)), $obj::class, $method);
        }
    }

    /**
     * @param object $obj
     * @param string $property
     *
     * @throws SecurityNotAllowedPropertyError
     */
    public function checkPropertyAllowed($obj, $property): void
    {
        if (!$this->isAllowedObject($obj)) {
            throw new SecurityNotAllowedPropertyError(sprintf('Accessing "%s" on a "%s" object is not allowed in theme templates.', $property, get_debug_type($obj)), get_debug_type($obj), $property);
        }
    }

    private function isAllowedObject(mixed $obj): bool
    {
        if (!is_object($obj)) {
            return false;
        }

        // Include parent classes so Doctrine proxies (Proxies\__CG__\Mautic\...
        // extending the real entity) are matched by the entity they proxy.
        $classes = array_merge([$obj::class], class_parents($obj));

        foreach ($classes as $class) {
            foreach (self::ALLOWED_OBJECT_NAMESPACES as $namespace) {
                if (str_starts_with($class, $namespace)) {
                    return true;
                }
            }
        }

        return false;
    }
}
