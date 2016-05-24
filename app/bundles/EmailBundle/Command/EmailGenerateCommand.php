<?php

/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\EmailBundle\Command;

use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\QueueEmailEvent;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * CLI command to generate Segment Email
 */
class EmailGenerateCommand extends ContainerAwareCommand
{

    /**
     *
     * {@inheritdoc}
     *
     */
    protected function configure()
    {
        $this->setName('mautic:email:generate')
            ->setDescription('generate Segment Email')
            ->addOption('--id', null, InputOption::VALUE_REQUIRED, 'Email ID');
        // ->addOption('--time-limit', null, InputOption::VALUE_OPTIONAL, 'Limit the number of seconds per batch. Defaults to value set in config.')
        // ->addOption('--do-not-clear', null, InputOption::VALUE_NONE, 'By default, failed messages older than the --recover-timeout setting will be attempted one more time then deleted if it fails again. If this is set, sending of failed messages will continue to be attempted.')
        // ->addOption('--recover-timeout', null, InputOption::VALUE_OPTIONAL, 'Sets the amount of time in seconds before attempting to resend failed messages. Defaults to value set in config.')
        // ->addOption('--clear-timeout', null, InputOption::VALUE_OPTIONAL, 'Sets the amount of time in seconds before deleting failed messages. Defaults to value set in config.')
        // ->setHelp(<<<EOT
        // The <info>%command.name%</info> command is used to process the application's e-mail queue

        // <info>php %command.full_name%</info>
        // EOT
        // )

    }

    /**
     *
     * {@inheritdoc}
     *
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $options = $input->getOptions();
        $objectId=$options['id'];
        $factory = $this->getContainer()->get('mautic.factory');

        /** @var \Mautic\EmailBundle\Model\EmailModel $model */
        $model = $factory->getModel('email');
        $entity = $model->getEntity($objectId);

        if ($entity->getEmailType() == 'template') {
            // return $this->accessDenied();
            return 0;
        }

        // make sure email and category are published
        $category = $entity->getCategory();
        $catPublished = (!empty($category)) ? $category->isPublished() : true;
        $published = $entity->isPublished();

        if (!$catPublished || !$published) {
            return 0;
        }

//         die('');
        $action = $model->generateUrl('mautic_email_action', array(
            'objectAction' => 'send',
            'objectId' => $objectId
        ));
        $pending = $model->getPendingLeads($entity, null, true);

        $form = $this->get('form.factory')->create('batch_send', array(), array(
            'action' => $action
        ));
        $complete = $this->request->request->get('complete', false);


        die('');
        if ($this->request->getMethod() == 'POST' && ($complete || $this->isFormValid($form))) {
            if (!$complete) {
                $progress = array(
                    0,
                    (int) $pending
                );
                $session->set('mautic.email.send.progress', $progress);

                $stats = array(
                    'sent' => 0,
                    'failed' => 0,
                    'failedRecipients' => array()
                );
                $session->set('mautic.email.send.stats', $stats);

                $status = 'inprogress';
                $batchlimit = $form['batchlimit']->getData();

                $session->set('mautic.email.send.active', false);
            } else {
                $stats = $session->get('mautic.email.send.stats');
                $progress = $session->get('mautic.email.send.progress');
                $batchlimit = 100;
                $status = (!empty($stats['failed'])) ? 'with_errors' : 'success';
            }

            $contentTemplate = 'MauticEmailBundle:Send:progress.html.php';
            $viewParameters = array(
                'progress' => $progress,
                'stats' => $stats,
                'status' => $status,
                'email' => $entity,
                'batchlimit' => $batchlimit
            );
        } else {
            // process and send
            $contentTemplate = 'MauticEmailBundle:Send:form.html.php';
            $viewParameters = array(
                'form' => $form->createView(),
                'email' => $entity,
                'pending' => $pending
            );
        }

        return $this->delegateView(array(
            'viewParameters' => $viewParameters,
            'contentTemplate' => $contentTemplate,
            'passthroughVars' => array(
                'mauticContent' => 'emailSend',
                'route' => $action
            )
        ));
    }
}
