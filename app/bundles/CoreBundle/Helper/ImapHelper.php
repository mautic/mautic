<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

use Mautic\CoreBundle\Exception\ImapException;
use Mautic\CoreBundle\Factory\MauticFactory;
use PhpImap\Mailbox;

class ImapHelper
{
    /**
     * @var MauticFactory
     */
    private $factory;

    /**
     * @var array
     */
    private $settings;

    /**
     * @var string
     */
    private $attachmentDir;

    /**
     * @var Mailbox
     */
    public $mailbox;

    /**
     * @var bool
     */
    private $isGmail = false;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;

        $this->settings = array(
            'host'      => $this->factory->getParameter('monitored_email_host'),
            'port'      => $this->factory->getParameter('monitored_email_port'),
            'ssl'       => $this->factory->getParameter('monitored_email_ssl'),
            'user'      => $this->factory->getParameter('monitored_email_user'),
            'password'  => $this->factory->getParameter('monitored_email_password'),
            'path'      => $this->factory->getParameter('monitored_email_path'),
            'move_to'   => $this->factory->getParameter('monitored_email_processed_path')
        );

        // Check that cache attachments directory exists
        $cacheDir            = $factory->getSystemPath('cache');
        $this->attachmentDir = $cacheDir . '/attachments';

        if (!file_exists($this->attachmentDir)) {
            mkdir($this->attachmentDir);
        }

        if ($this->settings['host'] == 'imap.gmail.com') {
            $this->isGmail = true;
        }
    }

    /**
     * Override connection settings
     *
     * @param array $settings
     */
    public function setConnectionSettings(array $settings)
    {
        $this->settings = array_merge($this->settings, $settings);

        $this->isGmail = ($this->settings['host'] == 'imap.gmail.com');
    }

    /**
     * Validate server details by attemptimg to connect
     */
    public function connect()
    {
        /**
         * @var $host
         * @var $port
         * @var $ssl
         * @var $path
         * @var $user
         * @var $password
         */
        extract($this->settings);

        $useSsl        = !empty($ssl) ? '/ssl/novalidate-cert' : '';
        $this->mailbox = new Mailbox("{{$host}:{$port}/imap{$useSsl}}$path", $user, $password, $this->attachmentDir);

        $this->mailbox->getImapStream();
    }

    /**
     * Fetch new messages
     *
     * @return array
     */
    public function fetchNew()
    {
        return $this->mailbox->searchMailBox('UNSEEN');
    }
}