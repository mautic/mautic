<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCitrixBundle\Command;

use Mautic\CoreBundle\Command\ModeratedCommand;
use MauticPlugin\MauticCitrixBundle\Helper\CitrixHelper;
use MauticPlugin\MauticCitrixBundle\Helper\CitrixProducts;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI Command : Synchronizes registrant information from Citrix products.
 *
 * php bin/console mautic:citrix:sync [--product=webinar|meeting|assist|training [--id=%productId%]]
 */
class SyncCommand extends ModeratedCommand
{
    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this->setName('mautic:citrix:sync')
            ->setDescription('Synchronizes registrant information from Citrix products')
            ->addOption(
                'product',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Product to sync (webinar, meeting, training, assist)',
                null
            )
            ->addOption('id', 'i', InputOption::VALUE_OPTIONAL, 'The id of an individual registration to sync', null);

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $model   = $this->getContainer()->get('mautic.citrix.model.citrix');
        $options = $input->getOptions();
        $product = $options['product'];

        if (!$this->checkRunStatus($input, $output, $options['product'].$options['id'])) {
            return 0;
        }

        $activeProducts = [];
        if (null === $product) {
            // all products
            foreach (CitrixProducts::toArray() as $p) {
                if (CitrixHelper::isAuthorized('Goto'.$p)) {
                    $activeProducts[] = $p;
                }
            }

            if (0 === count($activeProducts)) {
                $this->completeRun();

                return;
            }
        } else {
            if (!CitrixProducts::isValidValue($product)) {
                $output->writeln('<error>Invalid product: '.$product.'. Aborted</error>');
                $this->completeRun();

                return;
            }
            $activeProducts[] = $product;
        }

        $count = 0;
        foreach ($activeProducts as $product) {
            $output->writeln('<info>Synchronizing registrants for <comment>GoTo'.ucfirst($product).'</comment></info>');

            /** @var array $citrixChoices */
            $citrixChoices = [];
            $productIds    = [];
            if (null === $options['id']) {
                // all products
                $citrixChoices = CitrixHelper::getCitrixChoices($product, false);
                $productIds    = array_keys($citrixChoices);
            } else {
                $productIds[]                  = $options['id'];
                $citrixChoices[$options['id']] = $options['id'];
            }

            foreach ($productIds as $productId) {
                try {
                    $eventDesc = $citrixChoices[$productId];
                    $eventName = CitrixHelper::getCleanString(
                            $eventDesc
                        ).'_#'.$productId;
                    $output->writeln('Synchronizing: ['.$productId.'] '.$eventName);

                    $model->syncEvent($product, $productId, $eventName, $eventDesc, $count, $output);
                } catch (\Exception $ex) {
                    $output->writeln('<error>Error syncing '.$product.': '.$productId.'.</error>');
                    $output->writeln('<error>'.$ex->getMessage().'</error>');
                    if ('dev' === MAUTIC_ENV) {
                        $output->writeln('<info>'.$ex.'</info>');
                    }
                }
            }
        }

        $output->writeln($count.' contacts synchronized.');
        $output->writeln('<info>Done.</info>');

        $this->completeRun();
    }
}
