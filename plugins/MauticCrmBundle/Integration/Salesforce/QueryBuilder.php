<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCrmBundle\Integration\Salesforce;

use MauticPlugin\MauticCrmBundle\Integration\Salesforce\Exception\NoObjectsToFetchException;

class QueryBuilder
{
    /**
     * @return string
     *
     * @throws NoObjectsToFetchException
     */
    public static function getLeadQuery(array $fields, array $ids)
    {
        if (empty($ids)) {
            throw new NoObjectsToFetchException();
        }

        $fieldString = self::getFieldString($fields);
        $idString    = implode("','", $ids);

        return ($idString) ? "SELECT $fieldString from Lead where Id in ('$idString') and ConvertedContactId = NULL" : '';
    }

    /**
     * @return string
     *
     * @throws NoObjectsToFetchException
     */
    public static function getContactQuery(array $fields, array $ids)
    {
        if (empty($ids)) {
            throw new NoObjectsToFetchException();
        }

        $fieldString = self::getFieldString($fields);
        $idString    = implode("','", $ids);

        return ($idString) ? "SELECT $fieldString from Contact where Id in ('$idString')" : '';
    }

    /**
     * @return string
     */
    private static function getFieldString(array $fields)
    {
        $fields[] = 'Id';

        return implode(', ', array_unique($fields));
    }
}
