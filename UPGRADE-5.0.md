# Backwards compatibility breaking changes
*   Platform Requirements
    *   Minimal PHP version was increased from 7.4 to 8.0 and 8.1.
*   Commands
    * The command `bin/console mautic:segments:update` will no longer update the campaign members but only the segment members. Use also command `bin/console mautic:campaigns:update` to update the campaign members if you haven't already. Both commands are recommended from Mautic 1.
    * `mautic:broadcast:send --limit=10 --batch=2` fix process emails with combination of parameters limit/batch. Before it processed all emails with batch 10. Now process 10 emails with batch 2. If you used to use before, probably you need change it.
*   Other
    * The User entity no longer implements Symfony\Component\Security\Core\User\AdvancedUserInterface as it was removed from Symfony 5. These methods required by the interface were also removed:
        * Mautic\UserBundle\Entity\User::isAccountNonExpired()
        * Mautic\UserBundle\Entity\User::isAccountNonLocked()
        * Mautic\UserBundle\Entity\User::isCredentialsNonExpired()
        * Mautic\UserBundle\Entity\User::isEnabled()
