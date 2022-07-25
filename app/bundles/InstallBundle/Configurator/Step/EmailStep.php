<?php

namespace Mautic\InstallBundle\Configurator\Step;

use Mautic\CoreBundle\Configurator\Step\StepInterface;
use Mautic\InstallBundle\Configurator\Form\EmailStepType;
use Symfony\Component\HttpFoundation\Session\Session;

class EmailStep implements StepInterface
{
    /**
     * From name for email sent from Mautic.
     *
     * @var string
     */
    public $mailer_from_name;

    /**
     * From email sent from Mautic.
     *
     * @var string
     */
    public $mailer_from_email;

    /**
     * Mail transport.
     *
     * @var string
     */
    public $mailer_transport = 'smtp';

    /**
     * SMTP host
     * Required in step.
     *
     * @var string
     */
    public $mailer_host;

    /**
     * SMTP port
     * Required in step.
     *
     * @var string
     */
    public $mailer_port;

    /**
     * Mailer username
     * Required in step.
     *
     * @var string
     */
    public $mailer_user;

    /**
     * Mailer password.
     *
     * @var string
     */
    public $mailer_password;

    /**
     * Amazon Region.
     *
     * @var string
     */
    public $mailer_amazon_region = 'us-east-1';

    /**
     * Amazon Region.
     *
     * @var string
     */
    public $mailer_amazon_other_region;

    /**
     * Mailer API key if applicable.
     *
     * @var string
     */
    public $mailer_api_key;

    /**
     * SMTP encryption
     * Required in step.
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

    /**
     * Spool mode.
     *
     * @var string
     */
    public $mailer_spool_type = 'memory'; // file|memory

    /**
     * Spool path.
     *
     * @var string
     */
    public $mailer_spool_path = '%kernel.root_dir%/../var/spool';

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
        return EmailStepType::class;
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
