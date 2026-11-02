<?php

declare(strict_types=1);

return [
    'name' => 'CompanyEnrich',
    'description' => 'Company and people research, with explicit profile enrichment using your CompanyEnrich API key.',
    'version' => '1.0.0',
    'author' => 'CompanyEnrich contributors',
    'routes' => ['main' => [
        'mautic_companyenrich_research' => [
            'path' => '/companyenrich',
            'controller' => 'MauticPlugin\\MauticCompanyEnrichBundle\\Controller\\CompanyEnrichController::researchAction',
        ],
        'mautic_companyenrich_enrich' => [
            'path' => '/companyenrich/enrich/{kind}/{id}',
            'controller' => 'MauticPlugin\\MauticCompanyEnrichBundle\\Controller\\CompanyEnrichController::enrichAction',
            'requirements' => ['kind' => 'contact|company', 'id' => '\\d+'],
        ],
    ]],
    'menu' => ['main' => ['mautic.companyenrich.title' => [
        'route' => 'mautic_companyenrich_research',
        'iconClass' => 'ri-search-line',
        'access' => 'plugin:plugins:manage',
    ]]],
];
