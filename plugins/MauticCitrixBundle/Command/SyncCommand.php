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

use MauticPlugin\MauticCitrixBundle\Entity\CitrixEventTypes;
use MauticPlugin\MauticCitrixBundle\Helper\CitrixHelper;
use MauticPlugin\MauticCitrixBundle\Helper\CitrixProducts;
use MauticPlugin\MauticCitrixBundle\Model\CitrixModel;
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
    /** @var  CitrixModel */
    protected $citrixModel;

    /**
     * SyncCommand constructor.
     * @param null $name
     * @throws \Symfony\Component\Console\Exception\LogicException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    public function __construct($name = null)
    {
        parent::__construct($name);
        $container = CitrixHelper::getContainer();
        $factory = $container->get('mautic.model.factory');
        $this->citrixModel = $factory->getModel('citrix.citrix');
    }

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
            foreach (CitrixProducts::toArray() as $p) {
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

        $count = 0;
        foreach ($activeProducts as $product) {
            $output->writeln('<info>Synchronizing registrants for <comment>GoTo'.ucfirst($product).'</comment></info>');

            /** @var array $citrixChoices */
            $citrixChoices = [];
            $productIds = [];
            if (null === $options['id']) {
                // all products
                $citrixChoices = CitrixHelper::getCitrixChoices($product, false);
                $productIds = array_keys($citrixChoices);
            } else {
                $productIds[] = $options['id'];
                $citrixChoices[$options['id']] = $options['id'];
            }

            foreach ($productIds as $productId) {
                try {
                    $eventName = $citrixChoices[$productId];//CitrixHelper::getEventName($product, $productId);
                    $output->writeln('Synchronizing: ['.$productId.'] '.$eventName);

                    $registrants = CitrixHelper::getRegistrants($product, $productId);
                    $knownRegistrants = $this->citrixModel->getEmailsByEvent(
                        $product,
                        $eventName,
                        CitrixEventTypes::REGISTERED
                    );

                    $count += $this->citrixModel->batchAddAndRemove(
                        $product,
                        $eventName,
                        CitrixEventTypes::REGISTERED,
                        array_diff($registrants, $knownRegistrants),
                        array_diff($knownRegistrants, $registrants),
                        $output
                    );

                    $attendees = CitrixHelper::getAttendees($product, $productId);
                    $knownAttendees = $this->citrixModel->getEmailsByEvent(
                        $product,
                        $eventName,
                        CitrixEventTypes::ATTENDED
                    );

                    $count += $this->citrixModel->batchAddAndRemove(
                        $product,
                        $eventName,
                        CitrixEventTypes::ATTENDED,
                        array_diff($attendees, $knownAttendees),
                        array_diff($knownAttendees, $attendees),
                        $output
                    );

                } catch (\Exception $ex) {
                    $output->writeln('<error>Error syncing '.$product.': '.$productId.'.</error>');
                    $output->writeln('<error>'.$ex->getMessage().'</error>');
                }
            }

        }

        $output->writeln($count.' contacts synchronized.');
        $output->writeln('<info>Done.</info>');
    }
}
