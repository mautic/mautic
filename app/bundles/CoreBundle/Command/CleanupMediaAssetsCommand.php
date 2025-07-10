<?php

namespace Mautic\CoreBundle\Command;

use Mautic\CoreBundle\Helper\PathsHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Finder\Finder;

/**
 * CLI Command to clean up obsolete files in the media folder.
 */
class CleanupMediaAssetsCommand extends Command
{
    public function __construct(
        private PathsHelper $pathsHelper,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('mautic:assets:cleanup')
          ->setHelp(
              <<<'EOT'
                The <info>%command.name%</info> command is used to clean up obsolete files in the media folder that are present in the app/assets folder.

<info>php %command.full_name%</info>
EOT
          );
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $assetsPath = $this->pathsHelper->getAssetsPath();
        $mediaPath  = $this->pathsHelper->getMediaPath();

        $finder = new Finder();
        $finder->files()->in($assetsPath)->path(['images', 'dashboards'])->notName('.htaccess');

        $files_to_delete = [];

        foreach ($finder as $file) {
            $absoluteFilePath = $file->getRealPath();
            $relativeFilePath = $file->getRelativePathname();
            $md5_source       = md5_file($absoluteFilePath);

            $mediaOverride = $mediaPath.'/'.$relativeFilePath;
            if (file_exists($mediaOverride)) {
                $md5_override = md5_file($mediaOverride);

                if ($md5_source == $md5_override) {
                    $files_to_delete[] = $mediaOverride;
                }
            }
        }
        $output->writeln('<info>'.count($files_to_delete).' obsolete files found</info>');

        if (count($files_to_delete)) {
            foreach ($files_to_delete as $file) {
                $output->writeln('<comment> - '.$file.'</comment>');
            }
            $output->writeln('');

            /** @var \Symfony\Component\Console\Helper\SymfonyQuestionHelper $helper */
            $helper   = $this->getHelperSet()->get('question');
            $question = new ConfirmationQuestion(
                '<question>delete files?</question> ', false
            );

            if ($helper->ask($input, $output, $question)) {
                foreach ($files_to_delete as $file) {
                    unlink($file);
                }
            }
        }

        return Command::SUCCESS;
    }

    protected static $defaultDescription = 'Cleans up obsolete files in the media folder that are present in the app/assets folder';
}
