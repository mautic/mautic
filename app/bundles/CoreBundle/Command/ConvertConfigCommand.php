<?php

namespace Mautic\CoreBundle\Command;

use Mautic\CoreBundle\Helper\PathsHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI Command to convert PHP theme config to JSON.
 */
class ConvertConfigCommand extends Command
{
    public function __construct(
        private PathsHelper $pathsHelper
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('mautic:theme:json-config')
            ->setDefinition([
                new InputOption(
                    'theme', null, InputOption::VALUE_REQUIRED,
                    'The name of the theme whose config you are converting.'
                ),
                new InputOption(
                    'save-php-config', null, InputOption::VALUE_NONE,
                    'When used, the theme\'s PHP config file will be saved.'
                ),
            ])
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command converts a PHP theme config file to JSON.

<info>php %command.full_name%</info>

You must specify the name of the theme via the --theme parameter:

<info>php %command.full_name% --theme=<theme></info>

You may opt to save the PHP config file by using the --save-php-config option.

<info>php %command.full_name% --save-php-config</info>
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $options       = $input->getOptions();
        $theme         = $options['theme'];
        $savePhpConfig = $options['save-php-config'];

        $themePath = realpath($this->pathsHelper->getSystemPath('themes', true).'/'.$theme);

        if (empty($themePath)) {
            $output->writeln("\n\n<error>The specified theme ($theme) does not exist.</error>");

            return \Symfony\Component\Console\Command\Command::FAILURE;
        }

        $jsonConfigPath = $themePath.'/config.json';

        if (file_exists($jsonConfigPath)) {
            $output->writeln("\n\n<error>The specified theme ($theme) already has a JSON config file.");

            return \Symfony\Component\Console\Command\Command::FAILURE;
        }

        $configPath = $themePath.'/config.php';

        if (!file_exists($configPath)) {
            $output->writeln("\n\n<error>The php config file for the specified theme ($theme) could not be found.</error>");

            return \Symfony\Component\Console\Command\Command::FAILURE;
        }

        $config = include $configPath;

        if (!is_array($config) || !array_key_exists('name', $config)) {
            $output->writeln("\n\n<error>The php config file for the specified theme ($theme) is not a valid config file.</error>");

            return \Symfony\Component\Console\Command\Command::FAILURE;
        }

        $jsonConfig = json_encode($config, JSON_PRETTY_PRINT);

        if (!file_put_contents($jsonConfigPath, $jsonConfig)) {
            $output->writeln("\n\n<error>Error writing json config file for the specified theme ($theme).</error>");

            return \Symfony\Component\Console\Command\Command::FAILURE;
        } else {
            $output->writeln("\n\n<info>Successfully wrote json config file for the specified theme ($theme).</info>");
        }

        if (!$savePhpConfig) {
            if (!unlink($configPath)) {
                $output->writeln("\n\n<error>Error deleting php config file for the specified theme ($theme).</error>");
            } else {
                $output->writeln("\n\n<info>PHP config file for theme ($theme) has been deleted.</info>");
            }
        } else {
            $output->writeln("\n\n<info>PHP config file for theme ($theme) was preserved.</info>");
        }

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }

    protected static $defaultDescription = 'Converts theme config to JSON from PHP';
}
