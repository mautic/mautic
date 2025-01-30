<?php

namespace Mautic\CampaignBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Command\ModeratedCommand;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FetchFormCommand extends ModeratedCommand
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        PathsHelper $pathsHelper,
        CoreParametersHelper $coreParametersHelper,
    ) {
        parent::__construct($pathsHelper, $coreParametersHelper);
    }

    protected function configure()
    {
        $this
            ->setName('mautic:campaign:fetch-forms')
            ->setDescription('Fetch campaign forms as JSON.')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'The ID of the campaign to fetch forms for.')
            ->addOption('json-only', null, InputOption::VALUE_NONE, 'Output only JSON data.')
            ->addOption('json-file', null, InputOption::VALUE_NONE, 'Save JSON data to a file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $campaignId = $input->getOption('id');
        $jsonOnly   = $input->getOption('json-only');
        $jsonFile   = $input->getOption('json-file');

        if (!$campaignId || !is_numeric($campaignId)) {
            $output->writeln('<error>You must specify a valid campaign ID using --id.</error>');

            return self::FAILURE;
        }

        $formData = $this->getFormData((int) $campaignId);

        if (empty($formData)) {
            $output->writeln('<info>No forms found for campaign ID '.$campaignId.'.</info>');

            return self::SUCCESS;
        }

        $jsonOutput = json_encode($formData, JSON_PRETTY_PRINT);

        if ($jsonOnly) {
            $output->write($jsonOutput);
        } elseif ($jsonFile) {
            $filePath = $this->writeToFile($jsonOutput, (int) $campaignId);
            $output->writeln('<info>JSON file created at:</info> '.$filePath);
        } else {
            $output->writeln('<error>You must specify one of --json-only or --json-file options.</error>');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getFormData(int $campaignId): array
    {
        $queryBuilder = $this->entityManager->getConnection()->createQueryBuilder();

        $formResults = $queryBuilder
            ->select('fl.form_id, ff.name, ff.category_id, ff.is_published, ff.description, ff.alias, ff.lang, ff.cached_html, ff.post_action, ff.template, ff.form_type, ff.render_style, ff.post_action_property, ff.form_attr')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_form_xref', 'fl')
            ->innerJoin('fl', MAUTIC_TABLE_PREFIX.'forms', 'ff', 'ff.id = fl.form_id AND ff.is_published = 1')
            ->where('fl.campaign_id = :campaignId')
            ->setParameter('campaignId', $campaignId, \Doctrine\DBAL\ParameterType::INTEGER)
            ->executeQuery()
            ->fetchAllAssociative();

        return array_map(static fn ($result) => [
            'id'           => $result['form_id'],
            'name'         => $result['name'],
            'is_published' => $result['is_published'],
            'category_id'  => $result['category_id'],
            'description'  => $result['description'],
            'alias'        => $result['alias'],
            'lang'         => $result['lang'],
            'cached_html'  => $result['cached_html'],
            'post_action'  => $result['post_action'],
            'template'     => $result['template'],

            'form_type'            => $result['form_type'],
            'render_style'         => $result['render_style'],
            'post_action_property' => $result['post_action_property'],
            'form_attr'            => $result['form_attr'],
        ], $formResults);
    }

    private function writeToFile(string $jsonOutput, int $campaignId): string
    {
        $filePath = sprintf('%s/forms_%d.json', sys_get_temp_dir(), $campaignId);
        file_put_contents($filePath, $jsonOutput);

        return $filePath;
    }
}
