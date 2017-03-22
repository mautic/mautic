<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Mautic\CoreBundle\Command;

use Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for generating new blank migration classes.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 *
 * @author  Jonathan Wage <jonwage@gmail.com>
 */
class GenerateMigrationsCommand extends AbstractCommand
{
    private static $_template =
            '<?php

/*
 * @package     Mautic
 * @copyright   <year> Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Migrations\SkipMigrationException;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version<version> extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        $shouldRunMigration = false; // Please modify to your needs
        
        if (!$shouldRunMigration) {

            throw new SkipMigrationException(\'Schema includes this migration\');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // Please modify to your needs
    }
}
';

    protected function configure()
    {
        $this
                ->setName('mautic:migrations:generate')
                ->setDescription('Generate a blank migration class.')
                ->addOption('editor-cmd', null, InputOption::VALUE_OPTIONAL, 'Open file with this command upon creation.')
                ->setHelp(<<<'EOT'
The <info>%command.name%</info> command generates a blank migration class:

    <info>%command.full_name%</info>

You can optionally specify a <comment>--editor-cmd</comment> option to open the generated file in your favorite editor:

    <info>%command.full_name% --editor-cmd=mate</info>
EOT
        );

        parent::configure();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $version = date('YmdHis');
        $path    = $this->generateMigration($input, $version);

        $output->writeln(sprintf('Generated new migration class to "<info>%s</info>"', $path));
    }

    protected function generateMigration(InputInterface $input, $version, $up = null, $down = null)
    {
        $code = str_replace(['<version>', '<year>'], [$version, date('Y')], self::$_template);
        $code = preg_replace('/^ +$/m', '', $code);
        $dir  = MAUTIC_ROOT_DIR.'/app/migrations';
        $path = $dir.'/Version'.$version.'.php';

        file_put_contents($path, $code);

        if ($editorCmd = $input->getOption('editor-cmd')) {
            proc_open($editorCmd.' '.escapeshellarg($path), [], $pipes);
        }

        return $path;
    }
}
