<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper;

final class CsvHelper
{
    /**
     * @return mixed[]|false
     */
    public static function csv_to_array(string $filename = '', string $separator = ',', string $enclosure = '"', string $escape = '\\'): array|false
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            return false;
        }

        $header = null;
        $data   = [];
        if (false !== ($handle = fopen($filename, 'r'))) {
            while (false !== ($row = fgetcsv($handle, 1000, $separator, $enclosure, $escape))) {
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
     * @param string[] $headers
     *
     * @return string[]
     */
    public static function sanitizeHeaders(array $headers): array
    {
        return array_map(fn ($header) => trim($header), $headers);
    }

    /**
     * @param string[] $headers
     *
     * @return string[]
     */
    public static function convertHeadersIntoFields(array $headers): array
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

    /**
     * @param resource $stream
     * @param mixed[]  $data
     */
    public static function putCsv($stream, array $data, string $separator = ',', string $enclosure = '"', string $escape = '\\'): int|false
    {
        return fputcsv($stream, $data, $separator, $enclosure, $escape);
    }

    /**
     * @return mixed[]
     */
    public static function strGetCsv(string $string, string $separator = ',', string $enclosure = '"', string $escape = '\\'): array
    {
        return str_getcsv($string, $separator, $enclosure, $escape);
    }
}
