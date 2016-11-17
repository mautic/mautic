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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * CLI Command to create language configuration files.
 */
class LanguageConfigCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('mautic:translation:createconfig')
            ->setDescription('Create config.php files for translations')
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command is used to create config.php files for translations

<info>php %command.full_name%</info>
EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \Symfony\Bundle\FrameworkBundle\Translation\Translator $translator */
        $translator = $this->getContainer()->get('translator');
        $translator->setLocale($this->getContainer()->get('mautic.factory')->getParameter('locale'));
        $username = $this->getContainer()->get('mautic.factory')->getParameter('transifex_username');
        $password = $this->getContainer()->get('mautic.factory')->getParameter('transifex_password');

        if (empty($username) || empty($password)) {
            $output->writeln($translator->trans('mautic.core.command.transifex_no_credentials'));

            return 0;
        }

        $translationDir = dirname($this->getContainer()->getParameter('kernel.root_dir')).'/translations/';

        $installedLocales = new Finder();
        $installedLocales->directories()->in($translationDir)->ignoreDotFiles(true)->depth('== 0');

        /** @var \BabDev\Transifex\Transifex $transifex */
        $transifex = $this->getContainer()->get('transifex');

        /** @var \Symfony\Component\Finder\SplFileInfo $dir */
        foreach ($installedLocales as $dir) {
            // If a config.php file exists, we don't need to do anything
            $configFile = $dir->getRealPath().'/config.php';

            if (file_exists($configFile)) {
                continue;
            }

            $lang = $dir->getBasename();

            // Fetch the language data from Transifex
            $langInfo = $transifex->languageinfo->getLanguage($lang);

            // TODO - Don't hardcode the author data
            $configData = $this->render(['name' => $langInfo->name, 'locale' => $langInfo->code, 'author' => 'Mautic Translators']);

            if (!@file_put_contents($configFile, $configData)) {
                $output->writeln($translator->trans('mautic.core.command.language_config.could_not_create', ['%file%' => $configFile]));
            } else {
                $output->writeln($translator->trans('mautic.core.command.language_config.config_written', ['%lang%' => $lang]));
            }
        }

        return 0;
    }

    /**
     * Renders parameters as a string.
     *
     * @param array $data Data array to render
     *
     * @return string
     */
    private function render(array $data)
    {
        $string = "<?php\n";
        $string .= "\$config = array(\n";

        foreach ($data as $key => $value) {
            if ($value !== '') {
                if (is_string($value)) {
                    $value = "'$value'";
                } elseif (is_bool($value)) {
                    $value = ($value) ? 'true' : 'false';
                } elseif (is_null($value)) {
                    $value = 'null';
                } elseif (is_array($value)) {
                    $value = $this->renderArray($value);
                }

                $string .= "\t'$key' => $value,\n";
            }
        }

        $string .= ");\n\nreturn \$config;";

        return $string;
    }

    /**
     * @param     $array
     * @param int $level
     *
     * @return string
     */
    protected function renderArray($array, $level = 1)
    {
        $string = "array(\n";

        $count = $counter = count($array);
        foreach ($array as $key => $value) {
            if (is_string($key)) {
                if ($counter === $count) {
                    $string .= str_repeat("\t", $level + 1);
                }
                $string .= '"'.$key.'" => ';
            }

            if (is_array($value)) {
                $string .= $this->renderArray($value, $level + 1);
            } else {
                $string .= '"'.addcslashes($value, '\\"').'"';
            }

            --$counter;
            if ($counter > 0) {
                $string .= ", \n".str_repeat("\t", $level + 1);
            }
        }
        $string .= "\n".str_repeat("\t", $level).')';

        return $string;
    }
}
