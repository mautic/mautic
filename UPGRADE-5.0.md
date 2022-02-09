# Backwards compatibility breaking changes
*   Platform Requirements
    *   Minimal PHP version was increased from 7.4 to 8.0 and 8.1.
*   Commands
    * The command `bin/console mautic:segments:update` will no longer update the campaign members but only the segment members. Use also command `bin/console mautic:campaigns:update` to update the campaign members if you haven't already. Both commands are recommended from Mautic 1.
