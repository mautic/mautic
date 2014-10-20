<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

/*
 * Common file for preparing an installation package
 */

// Copy files to packaging space
echo "Copying files\n";
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
chdir($baseDir . '/packaging');
system('rm app/phpunit.*');
system('rm app/tests.bootstrap*');
system('rm app/config/config_local.php*');
system('rm app/config/local.php*');
system('rm -rf app/cache');
system('rm -rf app/logs');

// babdev/transifex
system('rm vendor/babdev/transifex/.scrutinizer.yml');
system('rm vendor/babdev/transifex/composer.json');
system('rm vendor/babdev/transifex/README.markdown');

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

// jdorn/sql-formatter
system('rm -rf vendor/jdorn/sql-formatter/examples');
system('rm -rf vendor/jdorn/sql-formatter/tests');
system('rm vendor/jdorn/sql-formatter/.gitignore');
system('rm vendor/jdorn/sql-formatter/.travis.yml');
system('rm vendor/jdorn/sql-formatter/composer.json');
system('rm vendor/jdorn/sql-formatter/composer.lock');
system('rm vendor/jdorn/sql-formatter/phpunit.xml.dist');
system('rm vendor/jdorn/sql-formatter/README.md');

// jms/metadata
system('rm -rf vendor/jms/metadata/tests');
system('rm vendor/jms/metadata/.gitignore');
system('rm vendor/jms/metadata/.travis.yml');
system('rm vendor/jms/metadata/CHANGELOG.md');
system('rm vendor/jms/metadata/composer.json');
system('rm vendor/jms/metadata/composer.lock');
system('rm vendor/jms/metadata/phpunit.xml.dist');
system('rm vendor/jms/metadata/README.rst');

// jms/parser-lib
system('rm -rf vendor/jms/parser-lib/doc');
system('rm -rf vendor/jms/parser-lib/tests');
system('rm vendor/jms/parser-lib/.gitignore');
system('rm vendor/jms/parser-lib/.jms.yml');
system('rm vendor/jms/parser-lib/.travis.yml');
system('rm vendor/jms/parser-lib/composer.json');
system('rm vendor/jms/parser-lib/composer.lock');
system('rm vendor/jms/parser-lib/phpunit.xml.dist');
system('rm vendor/jms/parser-lib/README.md');

// jms/serializer
system('rm -rf vendor/jms/serializer/doc');
system('rm -rf vendor/jms/serializer/tests');
system('rm vendor/jms/serializer/.gitignore');
system('rm vendor/jms/serializer/.travis.yml');
system('rm vendor/jms/serializer/CHANGELOG.md');
system('rm vendor/jms/serializer/composer.json');
system('rm vendor/jms/serializer/composer.lock');
system('rm vendor/jms/serializer/phpunit.xml.dist');
system('rm vendor/jms/serializer/README.md');
system('rm vendor/jms/serializer/UPGRADING.md');

// jms/serializer-bundle
system('rm -rf vendor/jms/serializer-bundle/JMS/SerializerBundle/Tests');
system('rm vendor/jms/serializer-bundle/JMS/SerializerBundle/.gitignore');
system('rm vendor/jms/serializer-bundle/JMS/SerializerBundle/.travis.yml');
system('rm vendor/jms/serializer-bundle/JMS/SerializerBundle/composer.json');
system('rm vendor/jms/serializer-bundle/JMS/SerializerBundle/phpunit.xml.dist');
system('rm vendor/jms/serializer-bundle/JMS/SerializerBundle/README.md');
system('rm vendor/jms/serializer-bundle/JMS/SerializerBundle/UPGRADING.md');

// joomla/http
system('rm -rf vendor/joomla/http/Tests');
system('rm vendor/joomla/http/composer.json');
system('rm vendor/joomla/http/phpunit.travis.xml');
system('rm vendor/joomla/http/phpunit.xml.dist');
system('rm vendor/joomla/http/README.md');

// joomla/uri
system('rm -rf vendor/joomla/uri/Tests');
system('rm vendor/joomla/uri/composer.json');
system('rm vendor/joomla/uri/phpunit.travis.xml');
system('rm vendor/joomla/uri/phpunit.xml.dist');
system('rm vendor/joomla/uri/README.md');

// knplabs/knp-menu
system('rm -rf vendor/knplabs/knp-menu/doc');
system('rm -rf vendor/knplabs/knp-menu/tests');
system('rm vendor/knplabs/knp-menu/.gitignore');
system('rm vendor/knplabs/knp-menu/.travis.yml');
system('rm vendor/knplabs/knp-menu/CHANGELOG.md');
system('rm vendor/knplabs/knp-menu/composer.json');
system('rm vendor/knplabs/knp-menu/phpunit.xml.dist');
system('rm vendor/knplabs/knp-menu/README.markdown');

// knplabs/knp-menu-bundle
system('rm -rf vendor/knplabs/knp-menu-bundle/Knp/Bundle/MenuBundle/Tests');
system('rm vendor/knplabs/knp-menu-bundle/Knp/Bundle/MenuBundle/.gitignore');
system('rm vendor/knplabs/knp-menu-bundle/Knp/Bundle/MenuBundle/.travis.yml');
system('rm vendor/knplabs/knp-menu-bundle/Knp/Bundle/MenuBundle/CHANGELOG.md');
system('rm vendor/knplabs/knp-menu-bundle/Knp/Bundle/MenuBundle/composer.json');
system('rm vendor/knplabs/knp-menu-bundle/Knp/Bundle/MenuBundle/phpunit.xml.dist');
system('rm vendor/knplabs/knp-menu-bundle/Knp/Bundle/MenuBundle/README.md');

// liip/functional-test-bundle
system('rm -rf vendor/liip/functional-test-bundle/Liip/FunctionalTestBundle/ExampleTests');
system('rm -rf vendor/liip/functional-test-bundle/Liip/FunctionalTestBundle/Test');
system('rm vendor/liip/functional-test-bundle/Liip/FunctionalTestBundle/composer.json');
system('rm vendor/liip/functional-test-bundle/Liip/FunctionalTestBundle/README.md');

// michelf/php-markdown
system('rm vendor/michelf/php-markdown/composer.json');
system('rm vendor/michelf/php-markdown/Readme.md');
system('rm vendor/michelf/php-markdown/Readme.php');

// monolog/monolog
system('rm -rf vendor/monolog/monolog/doc');
system('rm -rf vendor/monolog/monolog/tests');
system('rm vendor/monolog/monolog/CHANGELOG.mdown');
system('rm vendor/monolog/monolog/composer.json');
system('rm vendor/monolog/monolog/phpunit.xml.dist');
system('rm vendor/monolog/monolog/README.mdown');

// mpdf/mpdf
system('rm -rf vendor/mpdf/mpdf/examples');
system('rm vendor/mpdf/mpdf/ttfonts/*');
system('rm vendor/mpdf/mpdf/ADDED\ INFO\ FONTS.txt');
system('rm vendor/mpdf/mpdf/CHANGELOG.txt');
system('rm vendor/mpdf/mpdf/composer.json');
system('rm vendor/mpdf/mpdf/CREDITS.txt');
system('rm vendor/mpdf/mpdf/FONT\ INFO.txt');
system('rm vendor/mpdf/mpdf/README.txt');

// nelmio/api-doc-bundle
system('rm -rf vendor/nelmio/api-doc-bundle/Nelmio/ApiDocBundle/Tests');
system('rm vendor/nelmio/api-doc-bundle/Nelmio/ApiDocBundle/.gitignore');
system('rm vendor/nelmio/api-doc-bundle/Nelmio/ApiDocBundle/.travis.yml');
system('rm vendor/nelmio/api-doc-bundle/Nelmio/ApiDocBundle/composer.json');
system('rm vendor/nelmio/api-doc-bundle/Nelmio/ApiDocBundle/CONTRIBUTING.md');
system('rm vendor/nelmio/api-doc-bundle/Nelmio/ApiDocBundle/phpunit.xml.dist');
system('rm vendor/nelmio/api-doc-bundle/Nelmio/ApiDocBundle/README.md');

// phpcollection/phpcollection
system('rm -rf vendor/phpcollection/phpcollection/doc');
system('rm -rf vendor/phpcollection/phpcollection/tests');
system('rm vendor/phpcollection/phpcollection/.gitignore');
system('rm vendor/phpcollection/phpcollection/.travis.yml');
system('rm vendor/phpcollection/phpcollection/composer.json');
system('rm vendor/phpcollection/phpcollection/composer.lock');
system('rm vendor/phpcollection/phpcollection/phpunit.xml.dist');
system('rm vendor/phpcollection/phpcollection/README.md');

// phpoffice/phpexcel
system('rm -rf vendor/phpoffice/phpexcel/Examples');
system('rm -rf vendor/phpoffice/phpexcel/unitTests');
system('rm vendor/phpoffice/phpexcel/.gitattributes');
system('rm vendor/phpoffice/phpexcel/.gitignore');
system('rm vendor/phpoffice/phpexcel/.travis.yml');
system('rm vendor/phpoffice/phpexcel/changelog.txt');
system('rm vendor/phpoffice/phpexcel/composer.json');
system('rm vendor/phpoffice/phpexcel/install.txt');

// phpoption/phpoption
system('rm -rf vendor/phpoption/phpoption/tests');
system('rm vendor/phpoption/phpoption/.gitignore');
system('rm vendor/phpoption/phpoption/.travis.yml');
system('rm vendor/phpoption/phpoption/composer.json');
system('rm vendor/phpoption/phpoption/phpunit.xml.dist');
system('rm vendor/phpoption/phpoption/README.md');

// psr/log
system('rm vendor/psr/log/.gitignore');
system('rm vendor/psr/log/composer.json');
system('rm vendor/psr/log/README.md');

// sensio/distribution-bundle
system('rm -rf vendor/sensio/distribution-bundle/Sensio/Bundle/DistributionBundle/Resources/bin');
system('rm vendor/sensio/distribution-bundle/Sensio/Bundle/DistributionBundle/.gitignore');
system('rm vendor/sensio/distribution-bundle/Sensio/Bundle/DistributionBundle/.travis.yml');
system('rm vendor/sensio/distribution-bundle/Sensio/Bundle/DistributionBundle/composer.json');

// sensio/framework-extra-bundle
system('rm -rf vendor/sensio/framework-extra-bundle/Sensio/Bundle/FrameworkExtraBundle/Resources/doc');
system('rm -rf vendor/sensio/framework-extra-bundle/Sensio/Bundle/FrameworkExtraBundle/Tests');
system('rm vendor/sensio/framework-extra-bundle/Sensio/Bundle/FrameworkExtraBundle/.gitignore');
system('rm vendor/sensio/framework-extra-bundle/Sensio/Bundle/FrameworkExtraBundle/.travis.yml');
system('rm vendor/sensio/framework-extra-bundle/Sensio/Bundle/FrameworkExtraBundle/CHANGELOG.md');
system('rm vendor/sensio/framework-extra-bundle/Sensio/Bundle/FrameworkExtraBundle/composer.json');
system('rm vendor/sensio/framework-extra-bundle/Sensio/Bundle/FrameworkExtraBundle/phpunit.xml.dist');
system('rm vendor/sensio/framework-extra-bundle/Sensio/Bundle/FrameworkExtraBundle/README.md');
system('rm vendor/sensio/framework-extra-bundle/Sensio/Bundle/FrameworkExtraBundle/UPGRADE.md');

// sensio/generator-bundle
system('rm -rf vendor/sensio/generator-bundle/Sensio/Bundle/GeneratorBundle/Resources/doc');
system('rm -rf vendor/sensio/generator-bundle/Sensio/Bundle/GeneratorBundle/Tests');
system('rm vendor/sensio/generator-bundle/Sensio/Bundle/GeneratorBundle/.gitignore');
system('rm vendor/sensio/generator-bundle/Sensio/Bundle/GeneratorBundle/.travis.yml');
system('rm vendor/sensio/generator-bundle/Sensio/Bundle/GeneratorBundle/composer.json');
system('rm vendor/sensio/generator-bundle/Sensio/Bundle/GeneratorBundle/phpunit.xml.dist');
system('rm vendor/sensio/generator-bundle/Sensio/Bundle/GeneratorBundle/README.md');

// swiftmailer/swiftmailer
system('rm -rf vendor/swiftmailer/swiftmailer/doc');
system('rm -rf vendor/swiftmailer/swiftmailer/notes');
system('rm -rf vendor/swiftmailer/swiftmailer/tests');
system('rm vendor/swiftmailer/swiftmailer/.gitattributes');
system('rm vendor/swiftmailer/swiftmailer/.gitignore');
system('rm vendor/swiftmailer/swiftmailer/.travis.yml');
system('rm vendor/swiftmailer/swiftmailer/CHANGES');
system('rm vendor/swiftmailer/swiftmailer/composer.json');
system('rm vendor/swiftmailer/swiftmailer/phpunit.xml.dist');
system('rm vendor/swiftmailer/swiftmailer/README');

// symfony/finder
system('rm -rf vendor/symfony/finder/Symfony/Component/Finder/Tests');
system('rm vendor/symfony/finder/Symfony/Component/Finder/.gitignore');
system('rm vendor/symfony/finder/Symfony/Component/Finder/CHANGELOG.md');
system('rm vendor/symfony/finder/Symfony/Component/Finder/composer.json');
system('rm vendor/symfony/finder/Symfony/Component/Finder/phpunit.xml.dist');
system('rm vendor/symfony/finder/Symfony/Component/Finder/README.md');

// symfony/icu
system('rm -rf vendor/symfony/icu/Symfony/Component/Icu/Tests');
system('rm vendor/symfony/icu/Symfony/Component/Icu/.gitignore');
system('rm vendor/symfony/icu/Symfony/Component/Icu/composer.json');
system('rm vendor/symfony/icu/Symfony/Component/Icu/phpunit.xml.dist');
system('rm vendor/symfony/icu/Symfony/Component/Icu/README.md');

// symfony/monolog-bundle
system('rm -rf vendor/symfony/monolog-bundle/Tests');
system('rm vendor/symfony/monolog-bundle/.gitignore');
system('rm vendor/symfony/monolog-bundle/.travis.yml');
system('rm vendor/symfony/monolog-bundle/composer.json');
system('rm vendor/symfony/monolog-bundle/phpunit.xml.dist');
system('rm vendor/symfony/monolog-bundle/README.md');

// symfony/swiftmailer-bundle
system('rm -rf vendor/symfony/swiftmailer-bundle/Symfony/Bundle/SwiftmailerBundle/Tests');
system('rm vendor/symfony/swiftmailer-bundle/Symfony/Bundle/SwiftmailerBundle/.gitignore');
system('rm vendor/symfony/swiftmailer-bundle/Symfony/Bundle/SwiftmailerBundle/.travis.yml');
system('rm vendor/symfony/swiftmailer-bundle/Symfony/Bundle/SwiftmailerBundle/composer.json');
system('rm vendor/symfony/swiftmailer-bundle/Symfony/Bundle/SwiftmailerBundle/phpunit.xml.dist');

// symfony/symfony
system('rm vendor/symfony/symfony/*.md');
system('rm vendor/symfony/symfony/.editorconfig');
system('rm vendor/symfony/symfony/.gitignore');
system('rm vendor/symfony/symfony/.travis.yml');
system('rm vendor/symfony/symfony/autoload.php.dist');
system('rm vendor/symfony/symfony/composer.json');
system('rm vendor/symfony/symfony/phpunit.xml.dist');

// symfony/symfony/src/Symfony/Bridge
chdir($baseDir . '/packaging/vendor/symfony/symfony/src/Symfony/Bridge');
system('rm -rf Doctrine/Test');
system('rm -rf */Tests');
system('rm */.gitignore');
system('rm */CHANGELOG.md');
system('rm */composer.json');
system('rm */phpunit.xml.dist');
system('rm */README.md');

// symfony/symfony/src/Symfony/Bundle
chdir($baseDir . '/packaging/vendor/symfony/symfony/src/Symfony/Bundle');
system('rm -rf FrameworkBundle/Test');
system('rm -rf */Tests');
system('rm */.gitignore');
system('rm */CHANGELOG.md');
system('rm */composer.json');
system('rm */phpunit.xml.dist');

// symfony/symfony/src/Symfony/Component
chdir($baseDir . '/packaging/vendor/symfony/symfony/src/Symfony/Component');
system('rm -rf */Tests');
system('rm */.gitignore');
system('rm */CHANGELOG.md');
system('rm */composer.json');
system('rm */phpunit.xml.dist');
system('rm */README.md');
system('rm -rf Console/Resources');

// twig/twig
chdir($baseDir . '/packaging');
system('rm -rf vendor/twig/twig/doc');
system('rm -rf vendor/twig/twig/ext');
system('rm -rf vendor/twig/twig/test');
system('rm vendor/twig/twig/.editorconfig');
system('rm vendor/twig/twig/.gitignore');
system('rm vendor/twig/twig/.travis.yml');
system('rm vendor/twig/twig/CHANGELOG');
system('rm vendor/twig/twig/composer.json');
system('rm vendor/twig/twig/phpunit.xml.dist');
system('rm vendor/twig/twig/README.rst');

// willdurand/jsonp-callback-validator
system('rm -rf vendor/willdurand/jsonp-callback-validator/tests');
system('rm vendor/willdurand/jsonp-callback-validator/.gitignore');
system('rm vendor/willdurand/jsonp-callback-validator/.travis.yml');
system('rm vendor/willdurand/jsonp-callback-validator/composer.json');
system('rm vendor/willdurand/jsonp-callback-validator/CONTRIBUTING.md');
system('rm vendor/willdurand/jsonp-callback-validator/phpunit.xml.dist');
system('rm vendor/willdurand/jsonp-callback-validator/README.md');

// willdurand/negotiation
system('rm -rf vendor/willdurand/negotiation/tests');
system('rm vendor/willdurand/negotiation/.gitignore');
system('rm vendor/willdurand/negotiation/.travis.yml');
system('rm vendor/willdurand/negotiation/composer.json');
system('rm vendor/willdurand/negotiation/CONTRIBUTING.md');
system('rm vendor/willdurand/negotiation/phpunit.xml.dist');
system('rm vendor/willdurand/negotiation/README.md');

// willdurand/oauth-server-bundle
system('rm -rf vendor/willdurand/oauth-server-bundle/Resources/doc');
system('rm -rf vendor/willdurand/oauth-server-bundle/Tests');
system('rm vendor/willdurand/oauth-server-bundle/.gitignore');
system('rm vendor/willdurand/oauth-server-bundle/.travis.yml');
system('rm vendor/willdurand/oauth-server-bundle/composer.json');
system('rm vendor/willdurand/oauth-server-bundle/composer.lock');
system('rm vendor/willdurand/oauth-server-bundle/phpunit.xml.dist');
system('rm vendor/willdurand/oauth-server-bundle/README.markdown');

// Find any .git directories and nuke them
system('find . -type d -name .git -exec rm -rf {} \\;');
