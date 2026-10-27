<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\CoreBundle\Loader\ParameterLoader;

final class Version20260709103000 extends AbstractMauticMigration
{
    public function up(Schema $schema): void
    {
        if (!$schema->hasTable("{$this->prefix}form_fields")) {
            return;
        }

        $formFieldsTable = $schema->getTable("{$this->prefix}form_fields");
        if (!$formFieldsTable->hasColumn('is_auto_fill')) {
            return;
        }

        $formUsingAutoFill = $this->connection->fetchOne(
            "SELECT 1 FROM {$this->prefix}form_fields WHERE is_auto_fill = 1 LIMIT 1"
        );

        if (false === $formUsingAutoFill) {
            return;
        }

        $configFile = ParameterLoader::getLocalConfigFile(__DIR__.'/../');
        if (!file_exists($configFile)) {
            return;
        }

        require $configFile;

        if (!isset($parameters) || !is_array($parameters)) {
            return;
        }

        $parameters['form_field_autofill'] = true;

        $result = file_put_contents($configFile, "<?php\n".'$parameters = '.var_export($parameters, true).';');
        if (false === $result) {
            throw new \RuntimeException('Could not update the local config file with form_field_autofill parameter.');
        }
    }
}
