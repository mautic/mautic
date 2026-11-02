# Monitored-email integration tests

These tests establish the existing Mautic 7 mailbox contract before introducing
an alternative IMAP implementation. They use a real, disposable GreenMail server
and do not need a database or installed Mautic instance. PHPUnit's `TestCase` is
intentional: only configuration/path providers are stubbed, not mail operations.

## DDEV

The `.ddev/docker-compose.imap.yaml` service supplies SMTP on `imap:3025` and IMAP
on `imap:3143` inside the project network. Run:

```sh
ddev restart
ddev exec php bin/phpunit -c app/phpunit.xml.dist app/bundles/EmailBundle/Tests/Integration/MonitoredEmail
```

The current implementation still requires the native IMAP extension. DDEV already
installs it. These tests do not yet remove that dependency.

## Other test environments

Start `greenmail/standalone:2.1.15` with:

```text
GREENMAIL_OPTS=-Dgreenmail.setup.test.smtp -Dgreenmail.setup.test.imap -Dgreenmail.hostname=0.0.0.0 -Dgreenmail.auth.disabled -Dgreenmail.startup.timeout=30000
```

Supply these environment variables to PHPUnit:

| Variable | Value |
| --- | --- |
| `MAUTIC_TEST_IMAP_HOST` | Hostname of the disposable server; enables the tests |
| `MAUTIC_TEST_IMAP_PORT` | IMAP port; defaults to `3143` |
| `MAUTIC_TEST_SMTP_PORT` | SMTP port on the same host; defaults to `3025` |

Without the host variable, these tests skip so existing test environments remain
usable. With the host set, a missing extension or unavailable server fails the
tests. The dedicated IMAP workflow runs them on PHP 8.2, 8.4 and 8.5.

GreenMail has authentication disabled **only for this test service**. Never point
these tests at a real mailbox server. DDEV publishes no host ports for it. Each
test uses a random account, delivers a fixed MIME fixture over SMTP, and removes
its local attachment files. Server-side accounts are held in memory and disappear
when the container restarts; there is no shared-inbox purge or persistent volume.
GreenMail does not forward messages to external mail servers.

## Compatibility baseline

Covered behavior includes MIME headers, nested multipart bodies, attachment bytes,
reply metadata, peek/read/unread/important flags, searches returning UIDs after
expunge, empty search results, moving between folders, raw-message export, and
native `IMAP\Connection` interoperability.

The fixture is delivered independently of Mautic's IMAP implementation. Native
calls are confined to the legacy API test and destination-folder provisioning.
When adding another implementation, reuse the behavioral tests and retain a
separate native-API compatibility test for the legacy adapter.

This is the first baseline, not complete migration coverage. Follow-up tests must
cover bounce/reply processors, DSN and feedback-loop messages, TLS/authentication
failures, reconnects, non-ASCII folders, and provider-specific behavior before a
new implementation becomes the default.
