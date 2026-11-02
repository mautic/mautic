# CompanyEnrich for Mautic

[CompanyEnrich](https://companyenrich.com/) provides B2B company and professional-profile data through APIs. Its company enrichment, search, lookalike, people lookup/search and workforce operations help teams research accounts and complete existing CRM profiles. [API documentation](https://docs.companyenrich.com/reference).

## Connect

1. Install the plugin and run `php bin/console mautic:plugins:reload` (or the equivalent DDEV command).
2. Open **Settings → Plugins → CompanyEnrich**, enter your own API key, and publish the integration. Mautic encrypts the authentication fields using its integration credential store.
3. Users with **Plugins → Manage** permission can open **CompanyEnrich research**. Record enrichment also requires view and edit access to that individual contact or company.

## Research

Choose an operation, enter its query parameters and JSON filters, then click **Run research**. Loading a page makes no CompanyEnrich request. Results retain all fields and response metadata; reported credit usage is shown when available. There is no automatic pagination, retry, background refresh or record import.

| Operation | Endpoint |
| --- | --- |
| Company enrichment by domain | GET `/companies/enrich` |
| Company enrichment by name/social identifier | POST `/companies/enrich` |
| Batch company enrichment (1–50 domains) | POST `/companies/enrich/batch` |
| Company by ID | GET `/companies` |
| Company search/count | POST `/companies/search`, `/companies/search/count` |
| Similar companies/count | POST `/companies/similar`, `/companies/similar/count` |
| Workforce | GET `/companies/workforce` |
| Person lookup by existing email | POST `/people/lookup` |
| Person by ID | GET `/people` |
| People search | POST `/people/search` |
| Person education | GET `/people/education` |
| Company autocomplete | GET `/companies/autocomplete` |
| Company and lookalike previews (plan access required) | POST `/companies/search/preview`, `/companies/similar/preview` |

The request schemas are derived from the public CompanyEnrich OpenAPI document checked on 2026-11-02. The API documentation lists valid filters and plan-specific availability. Search page size is capped at 100 by the provider schema. Advanced optional `expand` fields are available explicitly. Domain enrichment and lookalike requests default to `waitForEnrichment=false`: an unstored company can return a free miss and may be available on a later user-requested run.

## Complete existing records

Use **Fill missing profile fields** on a contact or company. The confirmation names the existing identifier and notes that credits may be consumed. Submitting performs one lookup, rechecks access and record freshness, then fills only empty fields. Contacts use their existing email for reverse lookup; companies use their website domain.

Contact fields: first name, last name, position, city and country. Company fields: name, description, city and country. Existing data, email addresses, ownership, segments, campaign membership and marketing preferences are not directly changed. Saves use Mautic's normal record model, so administrators should account for their own configured record-change automations.

Employee and revenue ranges remain in research results rather than being converted into invented exact CRM values. People search returns professional profiles, not email addresses. Async exports, saved-list administration and beta email discovery are outside this interactive integration's scope.

## Validation

Use the repository's DDEV commands to run the plugin unit and functional tests, static analysis and coding standards. Tests use fictional fixtures and mocked provider responses; they never use a live key or spend CompanyEnrich credits.
