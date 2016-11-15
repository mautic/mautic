<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\InstallBundle\Configurator\Step;

use Mautic\CoreBundle\Configurator\Step\StepInterface;
use Mautic\InstallBundle\Configurator\Form\EmailStepType;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Email Step.
 */
class EmailStep implements StepInterface
{
    /*
     * From name for email sent from Mautic
     *
     * @var string
     */
    public $mailer_from_name;

    /*
     * From email sent from Mautic
     *
     * @var string
     */
    public $mailer_from_email;

    /*
     * Mail transport
     *
     * @var string
     */
    public $mailer_transport = 'mail';

    /*
     * SMTP host
     *
     * @var string
     */
    public $mailer_host;

    /*
     * SMTP port
     *
     * @var string
     */
    public $mailer_port;

    /*
     * SMTP username
     *
     * @var string
     */
    public $mailer_user;

    /*
     * SMTP password
     *
     * @var string
     */
    public $mailer_password;

    /*
     * SMTP encryption
     *
     * @var string
     */
    public $mailer_encryption; // null|tls|ssl

    /*
     * SMTP auth mode
     *
     * @var string
     */
    public $mailer_auth_mode; //  null|plain|login|cram-md5

    /*
     * Spool mode
     *
     * @var string
     */
    public $mailer_spool_type = 'memory'; // file|memory

    /*
     * Spool path
     *
     * @var string
     */
    public $mailer_spool_path = '%kernel.root_dir%/spool';

    /**
     * @param Session $session
     */
    public function __construct(Session $session)
    {
        $user = $session->get('mautic.installer.user');
        if (!empty($user)) {
            $this->mailer_from_email = $user->email;
            $this->mailer_from_name  = $user->firstname.' '.$user->lastname;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getFormType()
    {
        return new EmailStepType();
    }

    /**
     * {@inheritdoc}
     */
    public function checkRequirements()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function checkOptionalSettings()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return 'MauticInstallBundle:Install:email.html.php';
    }

    /**
     * {@inheritdoc}
     */
    public function update(StepInterface $data)
    {
        $parameters = [];

        foreach ($data as $key => $value) {
            $parameters[$key] = $value;
        }

        return $parameters;
    }
}
