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
        if (false !== ($handle = fopen($filename, 'r'))) {
            while (false !== ($row = fgetcsv($handle, 1000, $delimiter))) {
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
     * @return array
     */
    public static function sanitizeHeaders(array $headers)
    {
        return array_map('trim', $headers);
    }

    public static function convertHeadersIntoFields(array $headers): array
    {
        sort($headers);

        $importedFields = [];

        foreach ($headers as $header) {
            $fieldName = strtolower(InputHelper::alphanum($header, false, '_'));

            // Skip columns with empty names as they cannot be mapped.
            if (!empty($fieldName)) {
                // Add prefix to field names to avoid errors in form rendering,
                // e.g. header with 'file' will be h_file.
                $importedFields['h_'.$fieldName] = $header;
            }
        }

        return $importedFields;
    }
}
