<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command to send segment emails.
 */
class ProcessFetchEmailCommand extends ContainerAwareCommand {

    /**
     * {@inheritdoc}
     */
    protected function configure() {
        $this
            ->setName('mautic:segmentemails:send')
            ->setAliases(
                [
                    'mautic:segmentsemail:send'
                ]
            )
            ->setDescription('Send Segmented Emails')
            ->setHelp(
                <<<EOT
                The <info>%command.name%</info> command is used to send segmented emails. Use the publish and unpublish date to use it as the window between the emails can be sent.

<info>php %command.full_name%</info>
EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $container = $this->getContainer();

        $factory = $container->get('mautic.factory');

        $model = $factory->getModel('email');

        $emails = $model->getEntities(
            array(
                'iterator_mode' => true
            )
        );
        while (($c = $emails->next()) !== false) {
            $c = reset($c);

            if(($c -> getEmailType()) == 'list') {
                $content = $c -> getPublishUp();
                if($content) {
                    $scheduleTime = date_timestamp_get($content);
                    $scheduledownTime = date_timestamp_get($c -> getPublishDown());
                    if(time() > $scheduleTime && time() < $scheduledownTime)
                        $model->sendEmailToLists($model->getEntity($c->getId()));
                }
            }
        }
    }
}
