<?php

namespace Mautic\LeadBundle\Segment\Query;

/**
 * DBAL 4 turned Doctrine\DBAL\Exception from a base class into an interface, so this
 * extends \Exception and implements it instead - keeping existing
 * catch (\Doctrine\DBAL\Exception) sites working as before.
 *
 * @since 2.1.4
 */
final class QueryException extends \Exception implements \Doctrine\DBAL\Exception
{
    public static function unknownAlias($alias, $registeredAliases): self
    {
        return new self("The given alias '".$alias."' is not part of ".
            'any FROM or JOIN clause table. The currently registered '.
            'aliases are: '.implode(', ', $registeredAliases).'.');
    }

    public static function nonUniqueAlias($alias, $registeredAliases): self
    {
        return new self("The given alias '".$alias."' is not unique ".
            'in FROM and JOIN clause table. The currently registered '.
            'aliases are: '.implode(', ', $registeredAliases).'.');
    }
}
