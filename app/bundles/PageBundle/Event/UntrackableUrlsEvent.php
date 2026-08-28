<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class UntrackableUrlsEvent extends Event
{
    /**
     * @var string[]
     */
    private array $doNotTrack = [
        '{webview_url}',
        '{resubscribe_url}',
        '{unsubscribe_url}',
        '{dnc_url}',
        '{trackable=(.*?)}',
    ];

    /**
     * @param string|string[]|null $content
     */
    public function __construct(
        private readonly string|array|null $content,
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
     * @return string|string[]|null
     */
    public function getContent(): string|array|null
    {
        return $this->content;
    }
}
