# Backwards compatibility breaking changes
*   Platform Requirements
    *   Minimal PHP version was increased from x to x.
    *   Minimal MySQL version was increased from x to x
*   Symfony 4
    *   Symfony deprecations were removed or refactored [https://github.com/symfony/symfony/blob/4.4/UPGRADE-4.0.md](https://github.com/symfony/symfony/blob/4.4/UPGRADE-4.0.md)
*   Packages removed
    *   debril/rss-atom-bundle removed
    *   egeloen/ordered-form-bundle removed
    *   sensio/distribution-bundle removed
    *   codeception/codeception removed
*   Commands
    * \Mautic\CoreBundle\Command\ModeratedCommand::$lockHandler is now private