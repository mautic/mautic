# Backwards compatibility breaking changes
*   Platform Requirements
    *   Minimal PHP version was increased from 7.4 to 8.0 and 8.1.
* Packages removed 
    * `swiftmailer/swiftmailer` 
* Packages added 
    * symfony/mailer
    * symfony/messenger
    * symfony/amazon-mailer
    * predis/predis
*   Commands
    * The command `bin/console mautic:segments:update` will no longer update the campaign members but only the segment members. Use also command `bin/console mautic:campaigns:update` to update the campaign members if you haven't already. Both commands are recommended from Mautic 1.
    * The command `bin/console mautic:emails:send` is removed because the file based queue is removed, this command got replaced with `bin/console messenger:consume email_transport` which is part of `symfony/messenger`.
*   Removed configuration variables
    * mailer_transport
    * mailer_host
    * mailer_port
    * mailer_user
    * mailer_password
    * mailer_encryption
    * mailer_auth_mode
    * mailer_amazon_region
    * mailer_amazon_other_region
    * mailer_spool_type
    * mailer_spool_path
    * mailer_spool_msg_limit
    * mailer_spool_time_limit
    * mailer_spool_recover_timeout
    * mailer_spool_clear_timeout

*   Added configuration variables
    * mailer_dsn
    * mailer_messenger_dsn