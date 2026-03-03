<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper;

use Symfony\Component\Intl\Countries;

class MapHelper
{
    /**
     * @param array<string, string> $legendValues
     */
    public static function getOptionLegendText(string $legendText, array $legendValues): string
    {
        return str_replace(array_keys($legendValues), array_values($legendValues), $legendText);
    }

    /**
     * @param array<string, array<int, array<string, int|string>>> $statsCountries
     * @param array<string, array<string, string>>                 $mapOptions
     *
     * @return array<int, array<string, mixed>>
     */
    public static function buildMapData(array $statsCountries, array $mapOptions, string $legendText): array
    {
        foreach ($mapOptions as $key => $value) {
            $mappedData = empty($statsCountries[$key]) ? [] : self::mapCountries($statsCountries[$key], $key);

            $result[] = [
                'data'       => $mappedData['data'] ?? [],
                'label'      => $value['label'],
                'legendText' => MapHelper::getOptionLegendText(
                    $legendText,
                    [
                        '%total'       => (string) ($mappedData['total'] ?? 0),
                        '%withCountry' => (string) ($mappedData['totalWithCountry'] ?? 0),
                    ]
                ),
                'unit'       => $value['unit'],
            ];
        }

        return $result ?? [];
    }

    /**
     * @param array<int, array<string, int|string>> $stats
     *
     * @return array<string, int|array<string, int>>
     */
    public static function mapCountries(array $stats, string $countKey): array
    {
        $countries = array_flip(Countries::getNames('en'));
        $results   = [
            'data'             => [],
            'total'            => 0,
            'totalWithCountry' => 0,
        ];

        foreach ($stats as $s) {
            $countryName = $s['country'];
            $results['total'] += $s[$countKey];

            if (isset($countries[$countryName])) {
                $countryCode                   = $countries[$countryName];

                if (!empty($s[$countKey])) {
                    $results['data'][$countryCode] = (int) $s[$countKey];
                }

                $results['totalWithCountry'] += $s[$countKey];
            }
        }

        return $results;
    }
}
