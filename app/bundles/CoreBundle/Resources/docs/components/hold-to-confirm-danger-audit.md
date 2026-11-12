# Danger hold-to-confirm: adoption audit

## Scope and recommendation

Read-only audit of the authenticated local Mautic UI: ten routes returned HTTP 200 and their server-rendered confirmation controls were inspected. Sources below refer to `app/bundles`. No delete, publish, winner or other state-changing action was executed. Component interaction was exercised separately in a temporary preview on Contacts; no production delete handler was replaced.

Start with **bulk contact deletion**, **custom-field deletion**, **form deletion/results** and **campaign deletion**. Use the hold as the final deliberate confirmation inside a stable, explanatory confirmation surface, not as a press-and-hold item hidden inside a dropdown. Keep the item name/count and consequences visible. Retain a normal Cancel action and an accessible alternative for people unable to maintain a long press.

Do not replace every `data-toggle="confirmation"`: exports, cache rebuilds and reversible visibility switches use that same mechanism.

## Strong candidates

1. **Delete selected contacts / Delete this contact** — `/s/contacts`; action targets `/s/contacts/batchDelete` and `/s/contacts/delete/{id}`. Best first integration because the bulk selection increases the cost of an accidental click. Keep selection count visible and re-run `batchActionPrecheck` before the request. Existing POST, ownership and locked-record handling are in `LeadBundle/Controller/LeadController.php::deleteAction/batchDeleteAction`.

2. **Delete custom field(s)** — `/s/contacts/fields`; `/s/contacts/fields/delete/{id}` and `batchDelete`. The rendered warning explicitly says existing field values associated with contacts are also deleted. Stronger fit than an ordinary record delete. State the affected field names and data loss. Sources: `LeadBundle/Controller/FieldController.php::deleteAction/batchDeleteAction`, `LeadBundle/Translations/en_US/messages.ini`.

3. **Delete form(s)** — `/s/forms`; `/s/forms/delete/{id}` and `batchDelete`. The live warning says ALL associated results are deleted too. Do not reduce that explanation to just “Hold to delete”. Keep it before the hold. Source: `FormBundle/Controller/FormController.php::deleteAction/batchDeleteAction`.

4. **Delete form submission(s)** — results screen, especially bulk removal. Confirmed by code rather than a populated results UI in this audit: `FormBundle/Resources/views/Result/list.html.twig`, `Result/_list.html.twig`, and `FormBundle/Controller/ResultController.php::deleteAction/batchDeleteAction`. These already provide per-row and bulk delete through `mautic_form_results_action`. Retain the results-table target and `formId` context.

5. **Delete campaign(s)** — `/s/campaigns`; `/s/campaigns/delete/{id}` and `batchDelete`. The live warning identifies permanent deletion of events, scheduled actions and execution history. A deliberate final hold is useful, especially for published campaigns. Keep publish/unpublish separate: it is not the same destructive operation. Source: `CampaignBundle/Controller/CampaignController.php`.

6. **Delete email(s), including a parent with A/B tests/translations** — `/s/emails`; `/s/emails/delete/{id}` and `batchDelete`. The rendered warning explicitly includes associated A/B tests and translations. Variant-row delete additionally passes `parentId`; keep it. Sources: `EmailBundle/Controller/EmailController.php`, `EmailBundle/Resources/views/Variant/row.html.twig`.

7. **Delete uploaded asset(s)** — `/s/assets`; `/s/assets/delete/{id}` and `batchDelete`. Live copy warns that files stored in Mautic are permanently deleted. Explain the difference between removing a locally stored file and a remote asset reference. Source: `AssetBundle/Controller/AssetController.php`.

8. **Delete user account(s)** — `/s/users`; `/s/users/delete/{id}` and `batchDelete`. The live warning identifies unassigned contacts/companies plus removed API access tokens and scheduled exports. A hold fits the final confirmation, but cannot replace server-side self/account/authorization safeguards. Source: `UserBundle/Controller/UserController.php::deleteAction/batchDeleteAction`.

## Secondary candidates

- **Delete segment(s)** — `/s/segments`; both single and bulk deletion exist. The live warning explicitly says contacts are NOT deleted, only removed from these segments. Preserve this distinction and the server's dependency checks; do not label it as deleting contacts. Source: `LeadBundle/Controller/ListController.php::deleteAction/batchDeleteAction`.
- **Delete role(s)** — `/s/roles`; individual and bulk actions exist. A secondary administrative use; preserve the existing rules around roles in use. Source: `UserBundle/Controller/RoleController.php`.
- **Delete an installed custom theme** — `/s/themes`; individual `/s/themes/delete/{theme}` and bulk delete exist. The same screen also has Hide actions for bundled themes: only deletion is a danger candidate. Source: `CoreBundle/Resources/views/Theme/_list_results.html.twig`.

## Not a blanket danger replacement

- **Make A/B winner** is a consequential action, not a delete button. The shared warning explains unpublishing other variants, ending the test and resetting statistics. Keep that explanation and the existing winner semantics; assess a separate warning/neutral hold rather than silently treating it as deletion. Code evidence: `CoreBundle/Resources/views/Variant/row.html.twig`, `EmailBundle/Resources/views/Variant/row.html.twig`, and `mautic.core.ab_test.confirm_make_winner`.
- **Export selected contacts/campaigns**, **Rebuild Cache**, **Hide theme**, **Publish/unpublish campaign**: all appeared among confirmation controls during the audit; they should not inherit danger merely because they share a selector.
- **Remove an unsaved builder item, filter, tab, attachment selection or ordinary note**: avoid adding a mandatory delay to frequent low-cost editing. Distinguish reversible editing from persisted destructive data loss.

## Shared integration seams

- `CoreBundle/Resources/views/Helper/toolbar_bulk_actions.html.twig` and `Helper/tableheader.html.twig` construct batch-delete confirmations.
- `CoreBundle/Resources/views/Helper/confirm.html.twig` carries action, callback, precheck, target, message and cancellation metadata.
- `CoreBundle/Assets/js/10.entity.js::executeBatchAction/batchActionPrecheck` owns selection checks and the existing batch request path.
- Keep the row/menu action as the entry point and render the hold only at the final confirmation. Preserve extension/plugin hooks rather than swapping all helpers globally.
- After a completed hold use either the component callback or its event, not both. Keep POST, CSRF and permission checks, pending/disabled state, errors and focus return. Show “Confirmed” for the gesture; show “Deleted” only after server success. Do not require both a hold and the old second confirmation.

## Evidence boundaries

The ten live routes were Contacts, Segments, Campaigns, Forms, Users, Roles, Custom Fields, Themes, Emails and Assets. Form-results deletion and A/B winner consequences were traced in code; they were not executed. Other installed plugins and every permission role were not exhaustively audited. This is a prioritized adoption map, not a claim that every delete endpoint in Mautic was tested.
