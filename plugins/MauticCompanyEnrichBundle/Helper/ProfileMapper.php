<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCompanyEnrichBundle\Helper;

final class ProfileMapper
{
    /** @param array<string, mixed> $data
     * @return array<string, string>
     */
    public static function fields(string $kind, array $data): array
    {
        $location = is_array($data['location'] ?? null) ? $data['location'] : [];
        $city = is_array($location['city'] ?? null) ? ($location['city']['name'] ?? null) : null;
        $country = is_array($location['country'] ?? null) ? ($location['country']['name'] ?? null) : null;
        $fields = 'contact' === $kind ? [
            'firstname' => $data['first_name'] ?? null,
            'lastname' => $data['last_name'] ?? null,
            'position' => $data['position'] ?? null,
            'city' => $city,
            'country' => $country,
        ] : [
            'companyname' => $data['name'] ?? null,
            'companydescription' => $data['description'] ?? null,
            'companycity' => $city,
            'companycountry' => $country,
        ];

        // Employees and revenue are ranges; do not invent exact numeric CRM values.
        return array_filter($fields, fn ($value): bool => is_string($value) && '' !== trim($value));
    }
}
