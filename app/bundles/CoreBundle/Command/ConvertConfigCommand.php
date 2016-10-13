<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI Command to convert PHP theme config to JSON.
 */
class ConvertConfigCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('mautic:theme:json-config')
            ->setDescription('Converts theme config to JSON from PHP')
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

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $options       = $input->getOptions();
        $theme         = $options['theme'];
        $savePhpConfig = $options['save-php-config'];

        $themePath = realpath($this->getContainer()->get('mautic.factory')->getSystemPath('themes').'/'.$theme);

        if (empty($themePath)) {
            $output->writeln("\n\n<error>The specified theme ($theme) does not exist.</error>");

            return 1;
        }

        $jsonConfigPath = $themePath.'/config.json';

        if (file_exists($jsonConfigPath)) {
            $output->writeln("\n\n<error>The specified theme ($theme) already has a JSON config file.");

            return 1;
        }

        $configPath = $themePath.'/config.php';

        if (!file_exists($configPath)) {
            $output->writeln("\n\n<error>The php config file for the specified theme ($theme) could not be found.</error>");

            return 1;
        }

        $config = include $configPath;

        if (!is_array($config) || !array_key_exists('name', $config)) {
            $output->writeln("\n\n<error>The php config file for the specified theme ($theme) is not a valid config file.</error>");

            return 1;
        }

        $jsonConfig = json_encode($config, JSON_PRETTY_PRINT);

        if (!file_put_contents($jsonConfigPath, $jsonConfig)) {
            $output->writeln("\n\n<error>Error writing json config file for the specified theme ($theme).</error>");

            return 1;
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

        return 0;
    }
}
