<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

/*
 * Build a "production" package from the current development HEAD, this should be run after a 'composer install'
 */

// Step 1 - Remove previous packages
echo "Preparing environment\n";
umask(022);
chdir(__DIR__);
system('rm -rf packaging');
@unlink(__DIR__ . '/packages/mautic-head.zip');

// Step 2 - Provision packaging space
mkdir(__DIR__ . '/packaging');

// Step 3 - Copy files to packaging space
echo "Copying files\n";
system('cp -r ../addons packaging/');
system('cp -r ../app packaging/');
system('cp -r ../assets packaging/');
system('cp -r ../bin packaging/');
system('cp -r ../themes packaging/');
system('cp -r ../vendor packaging/');
system('cp ../.htaccess packaging/');
system('cp ../index.php packaging/');
system('cp ../LICENSE.txt packaging/');
system('cp ../robots.txt packaging/');

// Step 4 - Remove stuff that shouldn't be distro'ed
echo "Removing extra files\n";
chdir(__DIR__ . '/packaging');
system('rm app/bootstrap*');
system('rm app/phpunit.*');
system('rm app/tests.bootstrap*');
system('rm app/config/config_local.php*');
system('rm app/config/local.php*');
system('rm -rf packaging/app/cache');
system('rm -rf packaging/app/logs');

// doctrine/annotations
system('rm -rf vendor/doctrine/annotations/tests');
system('rm vendor/doctrine/annotations/.gitignore');
system('rm vendor/doctrine/annotations/.travis.yml');
system('rm vendor/doctrine/annotations/composer.json');
system('rm vendor/doctrine/annotations/phpunit.xml.dist');
system('rm vendor/doctrine/annotations/README.md');

// doctrine/cache
system('rm -rf vendor/doctrine/cache/tests');
system('rm vendor/doctrine/cache/.coveralls.yml');
system('rm vendor/doctrine/cache/.gitignore');
system('rm vendor/doctrine/cache/.travis.yml');
system('rm vendor/doctrine/cache/build.properties');
system('rm vendor/doctrine/cache/build.xml');
system('rm vendor/doctrine/cache/composer.json');
system('rm vendor/doctrine/cache/phpunit.xml.dist');
system('rm vendor/doctrine/cache/README.md');

// doctrine/collections
system('rm -rf vendor/doctrine/collections/tests');
system('rm vendor/doctrine/collections/.gitignore');
system('rm vendor/doctrine/collections/.travis.yml');
system('rm vendor/doctrine/collections/composer.json');
system('rm vendor/doctrine/collections/phpunit.xml.dist');
system('rm vendor/doctrine/collections/README.md');

// doctrine/common
system('rm -rf vendor/doctrine/common/tests');
system('rm vendor/doctrine/common/.gitignore');
system('rm vendor/doctrine/common/.gitmodules');
system('rm vendor/doctrine/common/.travis.yml');
system('rm vendor/doctrine/common/build.properties');
system('rm vendor/doctrine/common/build.xml');
system('rm vendor/doctrine/common/composer.json');
system('rm vendor/doctrine/common/phpunit.xml.dist');
system('rm vendor/doctrine/common/README.md');
system('rm vendor/doctrine/common/UPGRADE_TO_2_1');
system('rm vendor/doctrine/common/UPGRADE_TO_2_2');

// doctrine/data-fixtures
system('rm -rf vendor/doctrine/data-fixtures/tests');
system('rm vendor/doctrine/data-fixtures/.gitignore');
system('rm vendor/doctrine/data-fixtures/composer.json');
system('rm vendor/doctrine/data-fixtures/phpunit.xml.dist');
system('rm vendor/doctrine/data-fixtures/README.md');
system('rm vendor/doctrine/data-fixtures/UPGRADE');

// doctrine/dbal
system('rm vendor/doctrine/dbal/composer.json');
system('rm vendor/doctrine/dbal/README.md');
system('rm vendor/doctrine/dbal/UPGRADE');

// doctrine/doctrine-bundle
system('rm -rf vendor/doctrine/doctrine-bundle/Doctrine/Bundle/DoctrineBundle/Tests');
system('rm vendor/doctrine/doctrine-bundle/Doctrine/Bundle/DoctrineBundle/.gitignore');
system('rm vendor/doctrine/doctrine-bundle/Doctrine/Bundle/DoctrineBundle/.travis.yml');
system('rm vendor/doctrine/doctrine-bundle/Doctrine/Bundle/DoctrineBundle/Changelog.md');
system('rm vendor/doctrine/doctrine-bundle/Doctrine/Bundle/DoctrineBundle/composer.json');
system('rm vendor/doctrine/doctrine-bundle/Doctrine/Bundle/DoctrineBundle/phpunit.xml.dist');
system('rm vendor/doctrine/doctrine-bundle/Doctrine/Bundle/DoctrineBundle/README.md');

// doctrine/doctrine-fixtures-bundle
system('rm vendor/doctrine/doctrine-fixtures-bundle/Doctrine/Bundle/FixturesBundle/.gitignore');
system('rm vendor/doctrine/doctrine-fixtures-bundle/Doctrine/Bundle/FixturesBundle/composer.json');
system('rm vendor/doctrine/doctrine-fixtures-bundle/Doctrine/Bundle/FixturesBundle/composer.lock');
system('rm vendor/doctrine/doctrine-fixtures-bundle/Doctrine/Bundle/FixturesBundle/phpunit.xml.dist');
system('rm vendor/doctrine/doctrine-fixtures-bundle/Doctrine/Bundle/FixturesBundle/README.markdown');

// doctrine/doctrine-migrations-bundle
system('rm vendor/doctrine/doctrine-migrations-bundle/Doctrine/Bundle/MigrationsBundle/.gitignore');
system('rm vendor/doctrine/doctrine-migrations-bundle/Doctrine/Bundle/MigrationsBundle/composer.json');
system('rm vendor/doctrine/doctrine-migrations-bundle/Doctrine/Bundle/MigrationsBundle/README.markdown');

// doctrine/inflector
system('rm -rf vendor/doctrine/inflector/tests');
system('rm vendor/doctrine/inflector/composer.json');
system('rm vendor/doctrine/inflector/phpunit.xml.dist');
system('rm vendor/doctrine/inflector/README.md');

// doctrine/lexer
system('rm vendor/doctrine/lexer/composer.json');
system('rm vendor/doctrine/lexer/README.md');

// doctrine/migrations
system('rm -rf vendor/doctrine/migrations/tests');
system('rm vendor/doctrine/migrations/.gitignore');
system('rm vendor/doctrine/migrations/.travis.yml');
system('rm vendor/doctrine/migrations/build.properties.dev');
system('rm vendor/doctrine/migrations/build.xml');
system('rm vendor/doctrine/migrations/composer.json');
system('rm vendor/doctrine/migrations/phpunit.xml.dist');
system('rm vendor/doctrine/migrations/README.markdown');

// doctrine/orm
system('rm -rf vendor/doctrine/orm/docs');
system('rm vendor/doctrine/orm/.coveralls.yml');
system('rm vendor/doctrine/orm/composer.json');
system('rm vendor/doctrine/orm/README.markdown');
system('rm vendor/doctrine/orm/UPGRADE.md');

// friendsofsymfony/oauth2-php
system('rm -rf vendor/friendsofsymfony/oauth2-php/tests');
system('rm vendor/friendsofsymfony/oauth2-php/.gitignore');
system('rm vendor/friendsofsymfony/oauth2-php/.gitmodules');
system('rm vendor/friendsofsymfony/oauth2-php/.travis.yml');
system('rm vendor/friendsofsymfony/oauth2-php/CHANGELOG.txt');
system('rm vendor/friendsofsymfony/oauth2-php/composer.json');
system('rm vendor/friendsofsymfony/oauth2-php/config.doxy');
system('rm vendor/friendsofsymfony/oauth2-php/phpunit.xml.dist');
system('rm vendor/friendsofsymfony/oauth2-php/README.md');

// friendsofsymfony/oauth-server-bundle
system('rm -rf vendor/friendsofsymfony/oauth-server-bundle/FOS/OAuthServerBundle/Tests');
system('rm vendor/friendsofsymfony/oauth-server-bundle/FOS/OAuthServerBundle/.gitignore');
system('rm vendor/friendsofsymfony/oauth-server-bundle/FOS/OAuthServerBundle/.travis.yml');
system('rm vendor/friendsofsymfony/oauth-server-bundle/FOS/OAuthServerBundle/composer.json');
system('rm vendor/friendsofsymfony/oauth-server-bundle/FOS/OAuthServerBundle/phpunit.xml.dist');
system('rm vendor/friendsofsymfony/oauth-server-bundle/FOS/OAuthServerBundle/README.md');

// friendsofsymfony/rest-bundle
system('rm -rf vendor/friendsofsymfony/rest-bundle/FOS/RestBundle/Tests');
system('rm vendor/friendsofsymfony/rest-bundle/FOS/RestBundle/.gitignore');
system('rm vendor/friendsofsymfony/rest-bundle/FOS/RestBundle/.travis.yml');
system('rm vendor/friendsofsymfony/rest-bundle/FOS/RestBundle/composer.json');
system('rm vendor/friendsofsymfony/rest-bundle/FOS/RestBundle/phpunit.xml.dist');
system('rm vendor/friendsofsymfony/rest-bundle/FOS/RestBundle/README.md');
system('rm vendor/friendsofsymfony/rest-bundle/FOS/RestBundle/UPGRADING.md');

// ircmaxell/password-compat
system('rm -rf vendor/ircmaxell/password-compat/test');
system('rm vendor/ircmaxell/password-compat/.travis.yml');
system('rm vendor/ircmaxell/password-compat/composer.json');
system('rm vendor/ircmaxell/password-compat/phpunit.xml.dist');
system('rm vendor/ircmaxell/password-compat/README.md');
system('rm vendor/ircmaxell/password-compat/version-test.php');

// jdorn/sqlformatter
system('rm -rf vendor/jdorn/sqlformatter/examples');
system('rm -rf vendor/jdorn/sqlformatter/tests');
system('rm vendor/jdorn/sqlformatter/.gitignore');
system('rm vendor/jdorn/sqlformatter/.travis.yml');
system('rm vendor/jdorn/sqlformatter/composer.json');
system('rm vendor/jdorn/sqlformatter/composer.lock');
system('rm vendor/jdorn/sqlformatter/phpunit.xml.dist');
system('rm vendor/jdorn/sqlformatter/README.md');

// Step 5 - ZIP it up
echo "Packaging Mautic\n";
system('find . -type d -name .git -exec rm -rf {} \\; > /dev/null');
system('zip -r ../packages/mautic-head.zip addons/ app/ assets/ bin/ themes/ vendor/ .htaccess index.php LICENSE.txt robots.txt > /dev/null');
