<?php

namespace Mautic\LeadBundle\Segment\Query;

/**
 * @since 2.1.4
 */
class QueryException extends \Doctrine\DBAL\Exception
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
