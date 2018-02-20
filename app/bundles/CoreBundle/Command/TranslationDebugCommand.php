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
 * Modified from Symfony's command
 *
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mautic\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Bundle\FrameworkBundle\Translation\PhpExtractor;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Translation\Catalogue\MergeOperation;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * Helps finding unused or missing translation messages in a given locale
 * and comparing them with the fallback ones.
 *
 * @author Florian Voutzinos <florian@voutzinos.com>
 */
class TranslationDebugCommand extends ContainerAwareCommand
{
    const MESSAGE_MISSING         = 0;
    const MESSAGE_UNUSED          = 1;
    const MESSAGE_EQUALS_FALLBACK = 2;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('mautic:translation:debug')
            ->setDefinition([
                new InputOption('locale', 'l', InputOption::VALUE_OPTIONAL, 'The locale'),
                new InputOption('bundle', 'b', InputOption::VALUE_OPTIONAL, 'The bundle name'),
                new InputOption('logfile', 'log', InputOption::VALUE_OPTIONAL, 'Optional log file'),
                new InputOption('only-views', null, InputOption::VALUE_NONE, 'Extract from Views only.  Otherise it will check Controller, Model, and EventListener as well'),
                new InputOption('only-dups', null, InputOption::VALUE_NONE, 'Show only duplicates'),
                new InputOption('domain', null, InputOption::VALUE_OPTIONAL, 'The messages domain'),
                new InputOption('only-missing', null, InputOption::VALUE_NONE, 'Displays only missing messages'),
                new InputOption('only-unused', null, InputOption::VALUE_NONE, 'Displays only unused messages'),
            ])
            ->setDescription('Displays translation messages informations')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command helps finding unused or missing translation
messages and comparing them with the fallback ones by inspecting the
templates and translation files of a given bundle.

You can display information about bundle translations in a specific locale:

<info>php %command.full_name% en AcmeDemoBundle</info>

You can also specify a translation domain for the search:

<info>php %command.full_name% --domain=messages en AcmeDemoBundle</info>

You can only display missing messages:

<info>php %command.full_name% --only-missing en AcmeDemoBundle</info>

You can only display unused messages:

<info>php %command.full_name% --only-unused en AcmeDemoBundle</info>

EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $locale = $input->getOption('locale');
        if (empty($locale)) {
            $locale = 'en_US';
        }
        $domain    = $input->getOption('domain');
        $bundle    = $input->getOption('bundle');
        $log       = $input->getOption('logfile');
        $loader    = $this->getContainer()->get('mautic.translation.loader');
        $factory   = $this->getContainer()->get('mautic.factory');
        $viewsOnly = $input->getOption('only-views');
        $dupsOnly  = $input->getOption('only-dups');
        $bundles   = $factory->getMauticBundles(true);

        if (!empty($log)) {
            $logfile = fopen($log, 'w+');
            $output  = new StreamOutput($logfile, null, false, new OutputFormatter(false));
        }

        if (!empty($bundle)) {
            if (!isset($bundles[$bundle])) {
                $output->writeln('Bundle not found');

                return;
            }

            $bundles = [$bundles[$bundle]];
        }

        // Load defined messages
        $currentCatalogue = new MessageCatalogue($locale);

        // Extract used messages from Views
        $extractedCatalogue = new MessageCatalogue($locale);

        $phpFormExtractor = new PhpFormTranslationExtractor();

        foreach ($bundles as $bundle) {
            if (!$dupsOnly) {
                if (file_exists($bundle['directory'].'/Views')) {
                    $phpFormExtractor->extract($bundle['directory'].'/Views', $extractedCatalogue);
                    $this->getContainer()->get('translation.extractor')->extract($bundle['directory'].'/Views', $extractedCatalogue);
                }

                if (!$viewsOnly) {
                    $directories = [
                        '/Form/Type',
                        '/EventListener',
                        '/Model',
                        '/EventListener',
                        '/Controller',
                    ];
                    foreach ($directories as $d) {
                        if (file_exists($bundle['directory'].$d)) {
                            $phpFormExtractor->extract($bundle['directory'].$d, $extractedCatalogue);
                            $this->getContainer()->get('translation.extractor')->extract($bundle['directory'].$d, $extractedCatalogue);
                        }
                    }
                }
            }

            if (is_dir($bundle['directory'].'/Translations')) {
                $currentCatalogue = $loader->load(null, $locale, $domain);
            }
        }

        // Merge defined and extracted messages to get all message ids
        $mergeOperation = new MergeOperation($extractedCatalogue, $currentCatalogue);
        $allMessages    = $mergeOperation->getResult()->all($domain);
        if (null !== $domain) {
            $allMessages = [$domain => $allMessages];
        }

        // No defined or extracted messages
        if (empty($allMessages) || null !== $domain && empty($allMessages[$domain])) {
            $outputMessage = sprintf('<info>No defined or extracted messages for locale "%s"</info>', $locale);

            if (null !== $domain) {
                $outputMessage .= sprintf(' <info>and domain "%s"</info>', $domain);
            }

            $output->writeln($outputMessage);

            return;
        }

        /** @var \Symfony\Component\Console\Helper\Table $table */
        $table = new Table($output);

        // Display header line
        $headers = ['State(s)', 'Id', sprintf('Message Preview (%s)', $locale)];
        $table->setHeaders($headers);

        $duplicateCheck = [];

        // Iterate all message ids and determine their state
        foreach ($allMessages as $domain => $messages) {
            foreach (array_keys($messages) as $messageId) {
                $value = $currentCatalogue->get($messageId, $domain);

                $duplicateKey = strtolower($value);
                if (!isset($duplicateCheck[$duplicateKey])) {
                    $duplicateCheck[$duplicateKey] = [];
                }
                $duplicateCheck[$duplicateKey][] = [
                    'id'     => $messageId,
                    'domain' => $domain,
                ];

                $states = [];

                if ($extractedCatalogue->defines($messageId, $domain)) {
                    if (!$currentCatalogue->defines($messageId, $domain)) {
                        $states[] = self::MESSAGE_MISSING;
                    }
                } elseif ($currentCatalogue->defines($messageId, $domain)) {
                    $states[] = self::MESSAGE_UNUSED;
                }

                if (!in_array(self::MESSAGE_UNUSED, $states) && true === $input->getOption('only-unused')
                    || !in_array(self::MESSAGE_MISSING, $states) && true === $input->getOption('only-missing')
                ) {
                    continue;
                }

                $row = [$this->formatStates($states), $this->formatId($messageId), $this->sanitizeString($value)];

                $table->addRow($row);
            }
        }

        if (!$dupsOnly) {
            $table->render();

            $output->writeln('');
            $output->writeln('<info>Legend:</info>');
            $output->writeln(sprintf(' %s Missing message', $this->formatState(self::MESSAGE_MISSING)));
            $output->writeln(sprintf(' %s Unused message', $this->formatState(self::MESSAGE_UNUSED)));
            $output->writeln(sprintf(' %s Same as the fallback message', $this->formatState(self::MESSAGE_EQUALS_FALLBACK)));
        }

        $output->writeln('');
        $output->writeln('<info>Duplicates:</info>');

        /** @var \Symfony\Component\Console\Helper\Table $table */
        $table = new Table($output);

        // Display header line
        $headers = ['Value', 'Domain', 'Message ID'];
        $table->setHeaders($headers);

        //Check for duplicates
        $totalDuplicateCount = 0;
        foreach ($duplicateCheck as $value => $dups) {
            $count = count($dups);
            if ($count > 1) {
                ++$totalDuplicateCount;
                $table->addRow(['', '', '']);
                $table->addRow([$this->sanitizeString($value), $count, '']);
                foreach ($dups as $dup) {
                    $table->addRow(['', $dup['domain'], $dup['id']]);
                }
            }
        }

        $table->render();

        $output->writeln('');
        $output->writeln('<info>Total number of duplicates: '.$totalDuplicateCount.'</info>');
    }

    private function formatState($state)
    {
        if (self::MESSAGE_MISSING === $state) {
            return '<fg=red>x</>';
        }

        if (self::MESSAGE_UNUSED === $state) {
            return '<fg=yellow>o</>';
        }

        if (self::MESSAGE_EQUALS_FALLBACK === $state) {
            return '<fg=green>=</>';
        }

        return $state;
    }

    private function formatStates(array $states)
    {
        $result = [];
        foreach ($states as $state) {
            $result[] = $this->formatState($state);
        }

        return implode(' ', $result);
    }

    private function formatId($id)
    {
        return sprintf('<fg=cyan;options=bold>%s</fg=cyan;options=bold>', $id);
    }

    private function sanitizeString($string, $length = 40)
    {
        $string = trim(preg_replace('/\s+/', ' ', $string));

        if (function_exists('mb_strlen') && false !== $encoding = mb_detect_encoding($string)) {
            if (mb_strlen($string, $encoding) > $length) {
                return mb_substr($string, 0, $length - 3, $encoding).'...';
            }
        } elseif (strlen($string) > $length) {
            return substr($string, 0, $length - 3).'...';
        }

        return $string;
    }
}

class PhpFormTranslationExtractor extends PhpExtractor
{
    /**
     * The sequence that captures translation messages.
     *
     * @var array
     */
    protected $sequences = [
        [
            "'label'",
            '=>',
            self::MESSAGE_TOKEN,
        ],
        [
            "placeholder'",
            '=>',
            self::MESSAGE_TOKEN,
        ],
        [
            "'tooltip'",
            '=>',
            self::MESSAGE_TOKEN,
        ],
    ];
}
