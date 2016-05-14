<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Event;

use Mautic\EmailBundle\Entity\Email;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class UntrackableUrlsEvent
 */
class UntrackableUrlsEvent extends Event
{
    /**
     * @var array
     */
    private $doNotTrack = array(
        '{webview_url}',
        '{unsubscribe_url}',
        '{trackable=(.*?)}',
        // Ignore lead fields with URLs for tracking since each is unique
        '^{leadfield=(.*?)}',
        // @todo - remove in 2.0
        '{externallink=(.*?)}'
    );

    /**
     * @var string
     */
    private $content;

    /**
     * @deprecated to be removed in 2.0
     *
     * @var Email
     */
    private $email;

    /**
     * TrackableEvent constructor.
     *
     * @param $content
     */
    public function __construct($content)
    {
        if ($content instanceof Email) {
            $this->email = $content;
        } else {
            $this->content = $content;
        }
    }

    /**
     * set a URL or token to not convert to trackables
     *
     * @param $url
     */
    public function addNonTrackable($url)
    {
        $this->doNotTrack[$url] = true;
    }

    /**
     * Get array of non-trackables
     *
     * @return array
     */
    public function getDoNotTrackList()
    {
        return $this->doNotTrack;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Get Email
     *
     * @deprecated To be removed in 2.0
     *
     * @return Email
     */
    public function getEmail()
    {
        return $this->email;
    }
}
