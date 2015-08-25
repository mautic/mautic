<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

/*
 * Common file for preparing an installation package
 */

// Step 4 - Remove stuff that shouldn't be distro'ed
echo "Removing extra files\n";
chdir($baseDir . '/packaging');
system('rm app/phpunit.*');
system('rm app/tests.bootstrap*');
system('rm -rf app/bundles/*/Tests');
system('rm -rf app/bundles/CoreBundle/Test');
system('rm -rf app/cache');
system('rm -rf media/files/*');
system('rm app/config/config_dev.php');
system('rm app/config/config_test.php');
system('rm app/config/local*.php');
system('rm app/config/*_local.php');
system('rm app/config/routing_dev.php');
system('rm app/config/security_test.php');
system('rm app/migrations/Version20141102181850.php');
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
system('rm -rf vendor/doctrine/doctrine-bundle/Tests');
system('rm vendor/doctrine/doctrine-bundle/.gitignore');
system('rm vendor/doctrine/doctrine-bundle/.travis.yml');
system('rm vendor/doctrine/doctrine-bundle/Changelog.md');
system('rm vendor/doctrine/doctrine-bundle/composer.json');
system('rm vendor/doctrine/doctrine-bundle/phpunit.xml.dist');
system('rm vendor/doctrine/doctrine-bundle/README.md');

// doctrine/doctrine-cache-bundle
system('rm -rf vendor/doctrine/doctrine-cache-bundle/Doctrine/Bundle/DoctrineCacheBundle/Tests');
system('rm vendor/doctrine/doctrine-cache-bundle/Doctrine/Bundle/DoctrineCacheBundle/.coveralls.yml');
system('rm vendor/doctrine/doctrine-cache-bundle/Doctrine/Bundle/DoctrineCacheBundle/.gitignore');
system('rm vendor/doctrine/doctrine-cache-bundle/Doctrine/Bundle/DoctrineCacheBundle/.travis.yml');
system('rm vendor/doctrine/doctrine-cache-bundle/Doctrine/Bundle/DoctrineCacheBundle/composer.json');
system('rm vendor/doctrine/doctrine-cache-bundle/Doctrine/Bundle/DoctrineCacheBundle/phpunit.xml.dist');
system('rm vendor/doctrine/doctrine-cache-bundle/Doctrine/Bundle/DoctrineCacheBundle/README.markdown');
system('rm vendor/doctrine/doctrine-cache-bundle/Doctrine/Bundle/DoctrineCacheBundle/ruleset.xml');

// doctrine/doctrine-fixtures-bundle
system('rm vendor/doctrine/doctrine-fixtures-bundle/Doctrine/Bundle/FixturesBundle/.gitignore');
system('rm vendor/doctrine/doctrine-fixtures-bundle/Doctrine/Bundle/FixturesBundle/composer.json');
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

// guzzle/guzzle
system('rm -rf vendor/guzzle/guzzle/docs');
system('rm -rf vendor/guzzle/guzzle/src');
system('rm -rf vendor/guzzle/guzzle/tests');
system('rm vendor/guzzle/guzzle/.gitignore');
system('rm vendor/guzzle/guzzle/.travis.yml');
system('rm vendor/guzzle/guzzle/build.xml');
system('rm vendor/guzzle/guzzle/CHANGELOG.md');
system('rm vendor/guzzle/guzzle/composer.json');
system('rm vendor/guzzle/guzzle/phar-stub.php');
system('rm vendor/guzzle/guzzle/phpunit.xml.dist');
system('rm vendor/guzzle/guzzle/README.md');
system('rm vendor/guzzle/guzzle/UPGRADING.md');

// ircmaxell/password-compat
system('rm vendor/ircmaxell/password-compat/composer.json');
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
system('rm vendor/joomla/uri/phpunit.xml.dist');
system('rm vendor/joomla/uri/README.md');

// knplabs/gaufrette
system('rm -rf vendor/knplabs/gaufrette/bin');
system('rm -rf vendor/knplabs/gaufrette/spec');
system('rm -rf vendor/knplabs/gaufrette/tests');
system('rm vendor/knplabs/gaufrette/.gitignore');
system('rm vendor/knplabs/gaufrette/.travis.yml');
system('rm vendor/knplabs/gaufrette/composer.json');
system('rm vendor/knplabs/gaufrette/composer.lock');
system('rm vendor/knplabs/gaufrette/phpunit.xml.dist');
system('rm vendor/knplabs/gaufrette/README.markdown');

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

// mrclay/minify
system('rm -rf vendor/mrclay/minify/min_unit_tests');
system('rm vendor/mrclay/minify/.gitignore');
system('rm vendor/mrclay/minify/composer.json');
system('rm vendor/mrclay/minify/HISTORY.txt');
system('rm vendor/mrclay/minify/MIN.txt');
system('rm vendor/mrclay/minify/README.txt');
system('rm vendor/mrclay/minify/UPGRADING');

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

// rackspace/php-opencloud
system('rm -rf vendor/rackspace/php-opencloud/docs');
system('rm -rf vendor/rackspace/php-opencloud/samples');
system('rm -rf vendor/rackspace/php-opencloud/tests');
system('rm vendor/rackspace/php-opencloud/.coveralls.yml');
system('rm vendor/rackspace/php-opencloud/.gitignore');
system('rm vendor/rackspace/php-opencloud/.travis.yml');
system('rm vendor/rackspace/php-opencloud/composer.json');
system('rm vendor/rackspace/php-opencloud/CONTRIBUTING.md');
system('rm vendor/rackspace/php-opencloud/phpunit.xml.dist');
system('rm vendor/rackspace/php-opencloud/README.md');

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

// symfony
system('rm -rf vendor/symfony/*/Symfony/*/*/Tests');
system('rm vendor/symfony/*/Symfony/*/*/.gitignore');
system('rm vendor/symfony/*/Symfony/*/*/.travis.yml');
system('rm vendor/symfony/*/Symfony/*/*/CHANGELOG.md');
system('rm vendor/symfony/*/Symfony/*/*/composer.json');
system('rm vendor/symfony/*/Symfony/*/*/phpunit.xml.dist');
system('rm vendor/symfony/*/Symfony/*/*/README.md');
system('rm -rf vendor/symfony/console/Symfony/Component/Console/Resources');
system('rm -rf vendor/symfony/debug/Symfony/Component/Debug/Resources');
system('rm -rf vendor/symfony/doctrine-bridge/Symfony/Bridge/Doctrine/Test');
system('rm -rf vendor/symfony/form/Symfony/Component/Form/Test');
system('rm -rf vendor/symfony/framework-bundle/Symfony/Bundle/FrameworkBundle/Test');
system('rm -rf vendor/symfony/security/Symfony/Component/Security/Acl/Tests');
system('rm vendor/symfony/security/Symfony/Component/Security/Acl/.gitignore');
system('rm vendor/symfony/security/Symfony/Component/Security/Acl/composer.json');
system('rm vendor/symfony/security/Symfony/Component/Security/Acl/phpunit.xml.dist');
system('rm vendor/symfony/security/Symfony/Component/Security/Acl/README.md');
system('rm -rf vendor/symfony/security/Symfony/Component/Security/Core/Tests');
system('rm vendor/symfony/security/Symfony/Component/Security/Core/.gitignore');
system('rm vendor/symfony/security/Symfony/Component/Security/Core/composer.json');
system('rm vendor/symfony/security/Symfony/Component/Security/Core/phpunit.xml.dist');
system('rm vendor/symfony/security/Symfony/Component/Security/Core/README.md');
system('rm -rf vendor/symfony/security/Symfony/Component/Security/Csrf/Tests');
system('rm vendor/symfony/security/Symfony/Component/Security/Csrf/.gitignore');
system('rm vendor/symfony/security/Symfony/Component/Security/Csrf/composer.json');
system('rm vendor/symfony/security/Symfony/Component/Security/Csrf/phpunit.xml.dist');
system('rm vendor/symfony/security/Symfony/Component/Security/Csrf/README.md');
system('rm -rf vendor/symfony/security/Symfony/Component/Security/Http/Tests');
system('rm vendor/symfony/security/Symfony/Component/Security/Http/.gitignore');
system('rm vendor/symfony/security/Symfony/Component/Security/Http/composer.json');
system('rm vendor/symfony/security/Symfony/Component/Security/Http/phpunit.xml.dist');
system('rm vendor/symfony/security/Symfony/Component/Security/Http/README.md');

// syfmony/monolog-bundle
system('rm -rf vendor/symfony/monolog-bundle/Tests');
system('rm vendor/symfony/monolog-bundle/.gitignore');
system('rm vendor/symfony/monolog-bundle/.travis.yml');
system('rm vendor/symfony/monolog-bundle/composer.json');
system('rm vendor/symfony/monolog-bundle/phpunit.xml.dist');
system('rm vendor/symfony/monolog-bundle/README.md');

// twig/twig
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

// webfactory/exceptions-bundle
system('rm -rf vendor/webfactory/exceptions-bundle/Resources/doc');
system('rm -rf vendor/webfactory/exceptions-bundle/Tests');
system('rm vendor/webfactory/exceptions-bundle/.gitignore');
system('rm vendor/webfactory/exceptions-bundle/.hgtags');
system('rm vendor/webfactory/exceptions-bundle/.travis.yml');
system('rm vendor/webfactory/exceptions-bundle/composer.json');
system('rm vendor/webfactory/exceptions-bundle/phpunit.xml.dist');
system('rm vendor/webfactory/exceptions-bundle/README.md');
system('rm vendor/webfactory/exceptions-bundle/UPGRADING.md');

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

// webfactory/exceptions-bundle
system('rm -rf vendor/webfactory/exceptions-bundle/Tests');
system('rm vendor/webfactory/exceptions-bundle/.gitignore');
system('rm vendor/webfactory/exceptions-bundle/.hgtags');
system('rm vendor/webfactory/exceptions-bundle/.travis.yml');
system('rm vendor/webfactory/exceptions-bundle/composer.json');
system('rm vendor/webfactory/exceptions-bundle/phpunit.xml.dist');
system('rm vendor/webfactory/exceptions-bundle/README.md');
system('rm vendor/webfactory/exceptions-bundle/UPGRADING.md');

// Find any .git directories and nuke them
system('find . -type d -name .git -exec rm -rf {} \\;');

// Find any .DS_Store files and nuke them
system('find . -name .DS_Store -exec rm -rf {} \\;');
