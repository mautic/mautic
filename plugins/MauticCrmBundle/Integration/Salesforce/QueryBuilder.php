<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Integration\Salesforce;

use MauticPlugin\MauticCrmBundle\Integration\Salesforce\Exception\NoObjectsToFetchException;

final class QueryBuilder
{
    /**
     * @throws NoObjectsToFetchException
     */
    public static function getLeadQuery(array $fields, array $ids): string
    {
        if ([] === $ids) {
            throw new NoObjectsToFetchException();
        }

        $fieldString = self::getFieldString($fields);
        $idString    = implode("','", $ids);

        return ($idString) ? "SELECT {$fieldString} from Lead where Id in ('{$idString}') and ConvertedContactId = NULL" : '';
    }

    /**
     * @throws NoObjectsToFetchException
     */
    public static function getContactQuery(array $fields, array $ids): string
    {
        if ([] === $ids) {
            throw new NoObjectsToFetchException();
        }

        $fieldString = self::getFieldString($fields);
        $idString    = implode("','", $ids);

        return ($idString) ? "SELECT {$fieldString} from Contact where Id in ('{$idString}')" : '';
    }

    private static function getFieldString(array $fields): string
    {
        $fields[] = 'Id';

        return implode(', ', array_unique($fields));
    }
}
