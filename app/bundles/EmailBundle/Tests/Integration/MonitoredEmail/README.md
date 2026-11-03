# Monitored-email integration tests

The same mailbox contract runs against the native IMAP extension and the pure-PHP
fallback. Tests use a disposable GreenMail server and need no database or installed
Mautic instance. Only configuration/path providers are stubbed; SMTP delivery,
IMAP operations and MIME parsing are real.

## Mautic 7 compatibility

When `ext-imap` is installed, `Mailbox` retains its native implementation, including
its public/protected signatures, subclass hooks and `IMAP\Connection` returned by
`getImapStream()`. Without the extension, monitored email uses Horde's socket client
and ZBateson's MIME parser. Existing mailbox settings need no migration.

The extension is optional in Composer and is no longer installed by default in
DDEV. Plugins that call native `imap_*` functions, obtain the native connection or
set native connection arguments still need `ext-imap`. The fallback explicitly
rejects those native-only operations instead of returning a different connection
object or silently ignoring their options. No global IMAP functions or constants
are polyfilled.

The fallback supports `/ssl` (implicit TLS), `/tls` (STARTTLS), `/notls`, and explicit
certificate-validation flags. An empty encryption setting retains opportunistic
TLS. Certificates are verified unless `/novalidate-cert` is explicitly configured.

## DDEV

The `.ddev/docker-compose.imap.yaml` service supplies SMTP on `imap:3025`, IMAP on
`imap:3143`, and IMAPS on `imap:3993` inside the project network. Run:

```sh
ddev restart
ddev exec php bin/phpunit -c app/phpunit.xml.dist app/bundles/EmailBundle/Tests/Integration/MonitoredEmail app/bundles/EmailBundle/Tests/MonitoredEmail
```

A fresh DDEV image tests the fallback. To test legacy interoperability, install the
native extension through a local DDEV override and restart. CI runs both installed
and explicitly removed extension configurations on PHP 8.2, 8.4 and 8.5, and asserts
which configuration is actually running.

## Other test environments

Start `greenmail/standalone:2.1.15` with:

```text
GREENMAIL_OPTS=-Dgreenmail.setup.test.smtp -Dgreenmail.setup.test.imap -Dgreenmail.setup.test.imaps -Dgreenmail.hostname=0.0.0.0 -Dgreenmail.auth.disabled -Dgreenmail.startup.timeout=30000
```

Supply these environment variables to PHPUnit:

| Variable | Value |
| --- | --- |
| `MAUTIC_TEST_IMAP_HOST` | Hostname of the disposable server; enables the tests |
| `MAUTIC_TEST_IMAP_PORT` | IMAP port; defaults to `3143` |
| `MAUTIC_TEST_SMTP_PORT` | SMTP port on the same host; defaults to `3025` |
| `MAUTIC_TEST_IMAPS_PORT` | IMAPS port; enables TLS tests when supplied |
| `MAUTIC_TEST_IMAP_EXTENSION` | Optional runtime assertion: `imap` or `:imap` |

Without the host variable these tests skip. With the host set, an unavailable
server fails the tests. TLS tests exercise GreenMail's self-signed certificate,
including rejection with the default certificate validation enabled. GreenMail
does not implement STARTTLS; the suite checks that requiring STARTTLS fails
instead of silently downgrading to plaintext. A successful STARTTLS handshake
needs a different test server.

Authentication is disabled **only for this test service**. Never point the tests
at a real mailbox server. DDEV publishes no host ports. Every test uses a random
account, delivers fixed MIME fixtures over SMTP, and removes local attachment
files. Accounts are in memory and disappear when the container restarts; there is
no shared-inbox purge or persistent volume. GreenMail does not forward messages
to external mail servers.

## Coverage

The shared contract covers MIME headers, nested multipart bodies, attachment bytes
and disabling attachments, reply metadata, peek/read/unread/important flags,
stable UIDs after expunge, folder moves, raw-message export, empty inboxes,
overviews, sorting and mailbox status. Delivery-status and feedback-loop fixtures
are passed through the existing bounce and complaint parsers. Native connection
interoperability is tested with the extension installed. Fallback-specific tests
cover TLS, Unicode folders, connection failures, unsafe search input and rejection
of native-only connection arguments.

Provider-specific authentication and server behavior still require testing against
those providers; the disposable inbox does not emulate Gmail or Microsoft 365.
