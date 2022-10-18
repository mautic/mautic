<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Command;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\PluginBundle\Facade\ReloadFacade;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use ZipArchive;

class CreateCommand extends Command
{
    private CoreParametersHelper $coreParametersHelper;
    private KernelInterface $kernel;
    private ClientInterface $client;
    private Filesystem $filesystem;
    private ReloadFacade $reloadFacade;

    public function __construct(CoreParametersHelper $coreParametersHelper, KernelInterface $kernel, ClientInterface $client, ReloadFacade $reloadFacade)
    {
        $this->coreParametersHelper = $coreParametersHelper;
        $this->kernel = $kernel;
        $this->client = $client;
        $this->reloadFacade = $reloadFacade;
        $this->filesystem = new Filesystem();

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('mautic:plugins:create')
            ->setAliases(['mautic:plugins:new'])
            ->setDescription('Creates a new plugin based on a plugin template.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var QuestionHelper */
        $helper = $this->getHelper('question');

        $bundleNameQ = new Question('What will be the name of your new bundle? (Example: HelloWorldBundle) ', null);
        $bundleNameQ->setValidator(function ($answer) {
            if (!is_string($answer) || 'Bundle' !== substr($answer, -6)) {
                throw new \RuntimeException(
                    'The name of the bundle should be suffixed with \'Bundle\''
                );
            }
    
            return $answer;
        });
        $bundleNameQ->setMaxAttempts(3);

        $bundleName = $helper->ask($input, $output, $bundleNameQ);
        $mauticRoot = $this->kernel->getProjectDir();
        $bundleRoot = $mauticRoot . '/plugins/' . $bundleName;

        if ($this->filesystem->exists($bundleRoot)) {
            $removeFolderQ = new ConfirmationQuestion('The plugins/' . $bundleName . ' folder already exists. OK to delete it? ', false);

            if (!$helper->ask($input, $output, $removeFolderQ)) {
                $output->writeln('OK, we won\'t remove the folder and abort the plugin creation.');
                return 1;
            }

            $this->filesystem->remove($bundleRoot);
        }

        $composerNameQ = new Question('What will be the name of your bundle in Git and/or Packagist? (Example: mautic/hello-world-bundle) ', null);
        $composerNameQ->setValidator(function ($answer) {
            if (
                !is_string($answer) ||
                !preg_match('/^[a-z0-9]([_.-]?[a-z0-9]+)*\/[a-z0-9](([_.]?|-{0,2})[a-z0-9]+)*$/', $answer)
            ) {
                throw new \RuntimeException(
                    'The name must be in the format vendor/bundle, be lowercased and consist of words separated by -, . or _.'
                );
            }
    
            return $answer;
        });
        $composerNameQ->setMaxAttempts(3);

        $composerName = $helper->ask($input, $output, $composerNameQ);

        $composerDescriptionQ = new Question('Please provide a short decription of what your plugin does (or leave it empty for now): ', null);
        $composerDescription = $helper->ask($input, $output, $composerDescriptionQ);

        $output->writeln('Downloading plugin template...');

        // Download the plugin template from GitHub
        // TODO make this configurable
        $url = 'https://github.com/mautic/plugin-template/archive/refs/heads/main.zip';
        $templateZipPath = realpath($this->coreParametersHelper->get('tmp_path')) . '/mautic-plugin-template.zip';
        $templateFolderPath = realpath($this->coreParametersHelper->get('tmp_path')) . '/mautic-plugin-template';
        // TODO make configurable
        $subfolderName = 'plugin-template-main';
        $pluginTemplatePath = $templateFolderPath . '/' . $subfolderName;

        $this->downloadAndExtractPluginTemplate($url, $templateZipPath, $templateFolderPath);

        $output->writeln('Preparing your plugin...');

        $this->preparePluginFiles(
            $bundleName,
            $bundleRoot,
            $pluginTemplatePath,
            $composerName,
            $composerDescription
        );

        $output->writeln('Clearing cache and registering your plugin with Mautic... This might take a while!');

        $this->reloadPlugins();
        
        $output->writeln('Were done! Your plugin is now available at ' . $bundleRoot . '. For next steps, please read LINK_HERE');

        return 0;
    }

    private function downloadAndExtractPluginTemplate(string $url, string $templateZipPath, string $templateFolderPath): void {
        $request  = new Request('GET', $url, ['User-Agent' => 'Mautic Plugin generator']);
        $response = $this->client->send($request);
        $stream   = $response->getBody();

        if ($response->getStatusCode() >= 300) {
            throw new \Exception('Couldnt get ZIP template');
        }

        if (!file_put_contents($templateZipPath, $stream)) {
            throw new \RuntimeException('Couldnt move the ZIP file with our plugin template into the temporary folder: ' . $templateZipPath . '. Your folder permissions are likely incorrect or the file has been corrupted.');
        }

        $zip = new ZipArchive();

        if (!$zip->open($templateZipPath)) {
            throw new \RuntimeException('Couldnt open the ZIP file with our plugin template at ' . $templateZipPath . '. The file might be corrupted.');
        }

        if (!$zip->extractTo($templateFolderPath)) {
            throw new \RuntimeException('Couldnt extract the ZIP file with our plugin template into ' . $templateFolderPath);
        }

        $zip->close();
    }

    private function preparePluginFiles(
        string $bundleName,
        string $bundleRoot,
        string $pluginTemplatePath,
        string $composerName,
        string $composerDescription
    ): void {
        try {
            $this->filesystem->rename($pluginTemplatePath, $bundleRoot, true);
        } catch (IOException $e) {
            throw new \RuntimeException('We couldnt copy our plugin template from ' . $pluginTemplatePath . ' to ' . $bundleRoot . '. Your folder permissions are likely incorrect.');
        }

        $composerJson = file_get_contents($bundleRoot . '/composer.json');

        if (empty($composerJson)) {
            throw new \RuntimeException('The plugin template doesn\'t have a composer.json file in it. It might be missing or corrupted. Please try again.');
        }

        $composerJsonParsed = json_decode($composerJson, true);

        $composerJsonParsed['name'] = $composerName;
        $composerJsonParsed['description'] = $composerDescription;
        $composerJsonParsed['extra']['install-directory-name'] = $bundleName;

        if (!file_put_contents($bundleRoot . '/composer.json', json_encode($composerJsonParsed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
            throw new \RuntimeException('Couldnt update the composer.json file in the plugin template (' . $bundleRoot . '/composer.json). You might have insufficient file permissions.');
        }

        $bundleFile = file_get_contents($bundleRoot . '/_BundleTemplate.php');
        $updatedBundleFile = str_replace('REPLACE_BUNDLE_NAME', $bundleName, $bundleFile);
        file_put_contents($bundleRoot . '/_BundleTemplate.php', $updatedBundleFile);

        $this->filesystem->rename($bundleRoot . '/_BundleTemplate.php', $bundleRoot . '/' . $bundleName . '.php');
    }

    private function reloadPlugins(): void {
        // Clear cache
        $env = $this->kernel->getEnvironment();

        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'cache:clear',
            '--env'   => $env,
        ]);

        $output = new BufferedOutput();

        // TODO error handling
        $application->run($input, $output);

        $this->reloadFacade->reloadPlugins();
    }
}
