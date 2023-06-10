<?php

namespace Mautic\PageBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class UntrackableUrlsEvent.
 */
class UntrackableUrlsEvent extends Event
{
    /**
     * @var array
     */
    private $doNotTrack = [
        '{webview_url}',
        '{unsubscribe_url}',
        '{trackable=(.*?)}',
    ];

    /**
     * TrackableEvent constructor.
     *
     * @param string $content
     */
    public function __construct(private $content)
    {
    }

    /**
     * set a URL or token to not convert to trackables.
     */
    public function addNonTrackable($url)
    {
        $this->doNotTrack[] = $url;
    }

    /**
     * Get array of non-trackables.
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
}
