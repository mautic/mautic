<?php

namespace Mautic\PageBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class UntrackableUrlsEvent extends Event
{
    /**
     * @var string[]
     */
    private array $doNotTrack = [
        '{webview_url}',
        '{unsubscribe_url}',
        '{trackable=(.*?)}',
    ];

    /**
     * @param mixed $content
     */
    public function __construct(
        private $content
    ) {
    }

    /**
     * set a URL or token to not convert to trackables.
     */
    public function addNonTrackable($url): void
    {
        $this->doNotTrack[] = $url;
    }

    /**
     * Get array of non-trackables.
     *
     * @return string[]
     */
    public function getDoNotTrackList(): array
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
