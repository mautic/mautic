<?php
/**
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCitrixBundle\Command;

use MauticPlugin\MauticCitrixBundle\Helper\CitrixHelper;
use MauticPlugin\MauticCitrixBundle\Helper\CitrixProducts;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI Command : Synchronizes registrant information from Citrix products
 *
 * php app/console citrix:sync [--product=webinar|meeting|assist|training [--id=%productId%]]
 *
 */
class SyncCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this->setName('citrix:sync')
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
        $options = $input->getOptions();
        $product = $options['product'];
        $activeProducts = [];
        if (null === $product) {
            // all products
            foreach (CitrixProducts::toArray() as $p){
                if (CitrixHelper::isAuthorized('Goto'.$p)) {
                    $activeProducts[] = $p;
                }
            }

            if (0 === count($activeProducts)) {
                return;
            }
        } else {
            if (!CitrixProducts::isValidValue($product)) {
                $output->writeln('<error>Invalid product: '.$product.'. Aborted</error>');
                return;
            }
            $activeProducts[] = $product;
        }

        foreach ($activeProducts as $name) {
            $output->writeln('<info>Synchronizing registrants for <comment>GoTo'.ucfirst($name).'</comment></info>');
            $output->writeln('0 total contacts');
        }

        $output->writeln('<info>Done.</info>');
    }
}
