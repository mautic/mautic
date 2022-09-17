<?php

namespace Mautic\InstallBundle\Configurator\Step;

use Mautic\CoreBundle\Configurator\Step\StepInterface;
use Mautic\InstallBundle\Configurator\Form\EmailStepType;
use Symfony\Component\HttpFoundation\Session\Session;

class EmailStep implements StepInterface
{
    private const SKIP_PARAMETERS = [
        'mailer_transport',
        'mailer_host',
        'mailer_port',
        'mailer_user',
        'mailer_password',
        'mailer_api_key',
        'mailer_encryption',
        'mailer_auth_mode',
        'messenger_type',
        'messenger_transport',
    ];

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
     * @var string
     */
    public $mailer_dsn = 'null://null';

    /**
     * @var string
     */
    public $messenger_dsn = 'doctrine://default';

    /**
     * @var string
     */
    public $messenger_type;

    /**
     * @var string
     */
    public $messenger_transport = 'doctrine';

    public ?int $messenger_retry_strategy_max_retries = 3;

    public ?int $messenger_retry_strategy_delay = 1000;

    public ?int $messenger_retry_strategy_multiplier = 2;

    public ?int $messenger_retry_strategy_max_delay = 0;

    public function __construct(Session $session)
    {
        $user = $session->get('mautic.installer.user');
        if (! empty($user)) {
            $this->mailer_from_email = $user->email;
            $this->mailer_from_name = $user->firstname.' '.$user->lastname;
        }
    }

    /**
     * @inheritdoc
     */
    public function getFormType()
    {
        return EmailStepType::class;
    }

    /**
     * @inheritdoc
     */
    public function checkRequirements()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function checkOptionalSettings()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getTemplate()
    {
        return 'MauticInstallBundle:Install:email.html.php';
    }

    /**
     * @inheritdoc
     */
    public function update(StepInterface $data)
    {
        $parameters = [];

        foreach ($data as $key => $value) {
            if (! in_array($key, self::SKIP_PARAMETERS)) {
                $parameters[$key] = $value;
            }
        }

        return $parameters;
    }
}
