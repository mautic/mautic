<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\MonitoredEmail\Organizer;

use Mautic\EmailBundle\MonitoredEmail\Accessor\ConfigAccessor;

class MailboxContainer
{
    /**
     * @var ConfigAccessor
     */
    protected $config;

    /**
     * @var array
     */
    protected $criteria = [];

    /**
     * @var bool
     */
    protected $markAsSeen = true;

    /**
     * @var array
     */
    protected $messages = [];

    /**
     * MailboxAccessor constructor.
     *
     * @param ConfigAccessor $config
     */
    public function __construct(ConfigAccessor $config)
    {
        $this->config = $config;
    }

    /**
     * @param $criteria
     * @param $mailbox
     */
    public function addCriteria($criteria, $mailbox)
    {
        if (!isset($this->criteria[$criteria])) {
            $this->criteria[$criteria] = [];
        }

        $this->criteria[$criteria][] = $mailbox;
    }

    /**
     * Keep the messages in this mailbox as unseen.
     */
    public function keepAsUnseen()
    {
        $this->markAsSeen = false;
    }

    /**
     * @return bool
     */
    public function shouldMarkAsSeen()
    {
        return $this->markAsSeen;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->config->getPath();
    }

    /**
     * @return array
     */
    public function getCriteria()
    {
        return $this->criteria;
    }
}
