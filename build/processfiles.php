<?php
/**
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @see        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

/*
 * Common file for preparing an installation package
 */

// Step 4 - Remove stuff that shouldn't be distro'ed
echo "Removing extra files\n";
chdir($baseDir.'/packaging');

system('rm -f app/phpunit.*');
system('rm -f app/tests.bootstrap*');
system('find app/bundles/*/Tests/* ! -path "*/Tests/DataFixtures*" -prune -exec rm -rf {} \\;');
system('rm -rf app/bundles/CoreBundle/Test');
system('rm -rf app/cache/*');
system('rm -rf app/logs/*');
system('rm -rf var/cache/*');
system('rm -rf var/logs/*');
system('rm -rf var/spool/*');
system('rm -rf var/tmp/*');
system('rm -rf media/files/*');
// Delete ElFinder's (filemanager) assets
system('rm -rf media/assets/');
system('rm -f app/config/config_dev.php');
system('rm -f app/config/config_test.php');
system('rm -f app/config/local*.php');
system('rm -f app/config/routing_dev.php');
system('rm -f app/config/security_test.php');

// babdev/transifex
system('rm -f vendor/babdev/transifex/.scrutinizer.yml');

// doctrine/common
system('rm -f vendor/doctrine/common/UPGRADE_TO*');

// doctrine/migrations
system('rm -f vendor/doctrine/migrations/build.properties.dev');

// doctrine/orm
system('rm -rf vendor/doctrine/orm/docs');

// friendsofsymfony/oauth2-php
system('rm -f vendor/friendsofsymfony/oauth2-php/CHANGELOG.txt');
system('rm -f vendor/friendsofsymfony/oauth2-php/config.doxy');

// guzzle/guzzle
system('rm -rf vendor/guzzle/guzzle/docs');
system('rm -f vendor/guzzle/guzzle/phar-stub.php');

// ircmaxell/password-compat
system('rm -f vendor/ircmaxell/password-compat/version-test.php');

// jdorn/sql-formatter
system('rm -rf vendor/jdorn/sql-formatter/examples');

// jms/metadata
system('rm -f vendor/jms/metadata/README.rst');

// jms/parser-lib
system('rm -rf vendor/jms/parser-lib/doc');

// jms/serializer
system('rm -rf vendor/jms/serializer/doc');

// knplabs/gaufrette
system('rm -rf vendor/knplabs/gaufrette/bin');
system('rm -rf vendor/knplabs/gaufrette/spec');

// knplabs/knp-menu
system('rm -rf vendor/knplabs/knp-menu/doc');

// liip/functional-test-bundle
system('rm -rf vendor/liip/functional-test-bundle/Liip/FunctionalTestBundle/ExampleTests');

// michelf/php-markdown
system('rm -f vendor/michelf/php-markdown/Readme.php');

// monolog/monolog
system('rm -rf vendor/monolog/monolog/doc');

// mrclay/minify
system('rm -rf vendor/mrclay/minify/min_unit_tests');
system('rm -f vendor/mrclay/minify/HISTORY.txt');
system('rm -f vendor/mrclay/minify/MIN.txt');
system('rm -f vendor/mrclay/minify/README.txt');
system('rm -f vendor/mrclay/minify/UPGRADING');
// phpcollection/phpcollection
system('rm -rf vendor/phpcollection/phpcollection/doc');

// phpoffice/phpexcel
system('rm -rf vendor/phpoffice/phpexcel/Examples');
system('rm -rf vendor/phpoffice/phpexcel/unitTests');
system('rm -f vendor/phpoffice/phpexcel/changelog.txt');
system('rm -f vendor/phpoffice/phpexcel/install.txt');

// rackspace/php-opencloud
system('rm -rf vendor/rackspace/php-opencloud/docs');
system('rm -rf vendor/rackspace/php-opencloud/samples');

// sensio/distribution-bundle
system('rm -rf vendor/sensio/distribution-bundle/Sensio/Bundle/DistributionBundle/Resources/bin');

// sensio/framework-extra-bundle
system('rm -rf vendor/sensio/framework-extra-bundle/Sensio/Bundle/FrameworkExtraBundle/Resources/doc');
// sensio/generator-bundle
system('rm -rf vendor/sensio/generator-bundle/Sensio/Bundle/GeneratorBundle/Resources/doc');
// swiftmailer/swiftmailer
system('rm -rf vendor/swiftmailer/swiftmailer/doc');
system('rm -rf vendor/swiftmailer/swiftmailer/notes');
system('rm -f vendor/swiftmailer/swiftmailer/CHANGES');

// symfony
system('rm -rf vendor/symfony/console/Symfony/Console/Resources');

// twig/twig
system('rm -rf vendor/twig/twig/doc');
system('rm -rf vendor/twig/twig/ext');
system('rm -f vendor/twig/twig/.editorconfig');
system('rm -f vendor/twig/twig/CHANGELOG');
system('rm -f vendor/twig/twig/README.rst');

// webfactory/exceptions-bundle
system('rm -rf vendor/webfactory/exceptions-bundle/Resources/doc');

// willdurand/oauth-server-bundle
system('rm -rf vendor/willdurand/oauth-server-bundle/Resources/doc');

// Delete random files
system('find . -type f -name phpunit.xml -exec rm -f {} \\;');
system('find . -type f -name phpunit.xml.dist -exec rm -f {} \\;');
system('find . -type f -name .travis.yml -exec rm -f {} \\;');
system('find . -type f -name .hgtags -exec rm -f {} \\;');
system('find . -type f -name .coveralls.yml -exec rm -f {} \\;');
system('find . -type f -name build.properties -exec rm -f {} \\;');
system('find . -type f -name build.xml -exec rm -f {} \\;');
system('find . -type f -name Gruntfile.js -exec rm -f {} \\;');

// Delete composer files
system('find . -type f -name composer.json -exec rm -f {} \\;');
system('find . -type f -name composer.lock -exec rm -f {} \\;');
system('find . -type f -name package.json -exec rm -f {} \\;');

// Delete MD files
system('find vendor/ -type f -name "*.md" -exec rm -f {} \\;');
system('find vendor/ -type f -name "*.mdown" -exec rm -f {} \\;');
system('find vendor/ -type f -name "*.markdown" -exec rm -f {} \\;');

// Find git special files
system('find . -name ".git*" -prune -exec rm -rf {} \\;');

// Find any .DS_Store files and nuke them
system('find . -name .DS_Store -exec rm -rf {} \\;');

// Delete test directories
system('find . -type d -name Test ! -path "./vendor/twig/twig/lib/Twig/Node/Expression/Test" ! -path "./vendor/twig/twig/lib/Twig/Test" ! -path "./vendor/twig/twig/src/Node/Expression/Test" ! -path "./vendor/twig/twig/src/Test" -prune -exec rm -rf {} \\;');
system('find . -type d -name test ! -path "./vendor/twig/twig/lib/Twig/Node/Expression/Test" ! -path "./vendor/twig/twig/lib/Twig/Test" ! -path "./vendor/twig/twig/src/Node/Expression/Test" ! -path "./vendor/twig/twig/src/Test" -prune -exec rm -rf {} \\;');
system('find . -path "*/Tests/*" ! -path "./app/bundles/*/Tests*" ! -path "./plugins/*/Tests/DataFixtures*" -prune -exec rm -rf {} \\;');
system('find . -type d -name tests -prune -exec rm -rf {} \\;');
