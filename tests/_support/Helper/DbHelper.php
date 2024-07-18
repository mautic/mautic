<?php

namespace Helper;

use Codeception\Module;

class DbHelper extends Module
{
    public function _beforeSuite($settings = [])
    {
        $this->runCommand('bin/console d:f:l --no-interaction', 'Loading Doctrine fixtures');
        $this->runCommand('bin/console m:s:r', 'Building segments');

        $this->generateSqlDump();

        if (!file_exists('tests/_data/dump.sql')) {
            $this->fail('Failed to generate dump.sql');
        } else {
            $this->debug('DbHelper: dump.sql successfully generated');
        }
    }

    private function runCommand($command, $description)
    {
        $output    = [];
        $returnVar = null;
        exec($command.' 2>&1', $output, $returnVar);
        if (0 !== $returnVar) {
            $this->fail("Command '$command' failed with error: ".implode("\n", $output));
        } else {
            $this->debug("$description completed successfully");
        }
    }

    private function generateSqlDump()
    {
        $this->runCommand('mysqldump -u db -h db db > tests/_data/dump.sql', 'Generating SQL dump');
    }
}
