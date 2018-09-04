<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

/**
 * Class CsvHelper.
 */
class CsvHelper
{
    /**
     * @param string $filename
     * @param string $delimiter
     *
     * @return array
     */
    public static function csv_to_array($filename = '', $delimiter = ',')
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            return false;
        }

        $header = null;
        $data   = [];
        if (($handle = fopen($filename, 'r')) !== false) {
            while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
                if (!$header) {
                    $header = $row;
                } else {
                    $data[] = array_combine($header, $row);
                }
            }
            fclose($handle);
        }

        return $data;
    }

    /**
     * @param array $headers
     *
     * @return array
     */
    public static function sanitizeHeaders(array $headers)
    {
        return array_map('trim', $headers);
    }

    /**
     * @param array $headers
     *
     * @return array
     */
    public static function convertHeadersIntoFields(array $headers)
    {
        sort($headers);

        $importedFields = [];

        foreach ($headers as $header) {
            $fieldName = strtolower(InputHelper::alphanum($header, false, '_'));

            // Skip columns with empty names as they cannot be mapped.
            if (!empty($fieldName)) {
                $importedFields[$fieldName] = $header;
            }
        }

        return $importedFields;
    }
}
