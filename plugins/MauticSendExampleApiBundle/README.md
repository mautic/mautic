# Send Example API plugin for Mautic 7.x

Exposes the UI **"Send example"** email action as a REST API endpoint. Internally it calls the
exact same core service the UI button uses (`EmailModel::sendSampleEmailToUser()`), so behavior
matches the UI:

- No email stats are created (no opens/clicks tracked, tracking pixel stripped).
- Works on **unpublished** (draft) emails.
- Tokens are filled with **fake contact data** by default, or with a real contact's data when
  `contactId` is supplied.
- The subject is prefixed with `[TEST]` (can be disabled).
- Do Not Contact status is irrelevant — recipients are arbitrary addresses, not contacts.

## Requirements

- Mautic 7.x (developed against the `7.x` branch; the core APIs used — `sendSampleEmailToUser`,
  `FakeContactHelper`, `enrichedContactWithCompanies` — exist as of Mautic 5.1+, so it will
  likely work on 5.x/6.x too, but only 7.x has been checked).
- API enabled in Mautic (Configuration → API Settings), with OAuth2 or Basic Auth credentials.

## Installation

1. Copy the `MauticSendExampleApiBundle` folder into your Mautic `plugins/` directory:

   ```
   plugins/MauticSendExampleApiBundle/
   ├── MauticSendExampleApiBundle.php
   ├── Config/config.php
   ├── Controller/Api/ExampleSendApiController.php
   └── Tests/Controller/Api/ExampleSendApiControllerFunctionalTest.php
   ```

2. Clear the cache and reload plugins:

   ```bash
   php bin/console cache:clear
   php bin/console mautic:plugins:reload
   ```

   (With DDEV: `ddev exec php bin/console cache:clear` etc.)

3. The plugin appears as **"Send Example API"** under Settings → Plugins.

## Usage

```
POST /api/emails/{id}/example/send
Content-Type: application/json
```

### Body parameters

| Field             | Type     | Required | Description                                                             |
|-------------------|----------|----------|-------------------------------------------------------------------------|
| `recipients`      | string[] | yes      | Email addresses to send the example to                                  |
| `contactId`       | int      | no       | Fill tokens ({contactfield=...}) from this contact instead of fake data |
| `tokens`          | object   | no       | Custom token overrides, e.g. `{"{custom}": "value"}` (braces optional)  |
| `noSubjectPrefix` | bool     | no       | Set `true` to skip the `[TEST]` subject prefix                          |

### Examples

Send with fake contact data (same as UI default):

```bash
curl -X POST "https://mautic.example.com/api/emails/42/example/send" \
  -u "user:password" \
  -H "Content-Type: application/json" \
  -d '{"recipients": ["me@example.com", "qa@example.com"]}'
```

Preview with a real contact's data plus a custom token:

```bash
curl -X POST "https://mautic.example.com/api/emails/42/example/send" \
  -u "user:password" \
  -H "Content-Type: application/json" \
  -d '{
        "recipients": ["me@example.com"],
        "contactId": 123,
        "tokens": {"myToken": "hello"},
        "noSubjectPrefix": true
      }'
```

### Response

```json
{
  "success": true,
  "sent": ["me@example.com"],
  "errors": []
}
```

Per-recipient failures (e.g. invalid address, transport errors) are reported in `errors`;
`success` is `true` only when every recipient was accepted.

| Status | Meaning                                                                  |
|--------|--------------------------------------------------------------------------|
| 200    | Processed (check `success`/`errors` for per-recipient results)           |
| 400    | Missing/empty `recipients`                                               |
| 403    | API user lacks `email:emails:viewown/viewother` (or lead view permission when `contactId` is used) |
| 404    | Email (or contact) not found                                             |

## Permissions

Mirrors the UI action: requires *view* access to the email
(`email:emails:viewown` / `viewother`). When `contactId` is passed, *view* access to that
contact (`lead:leads:viewown` / `viewother`) is also required.
