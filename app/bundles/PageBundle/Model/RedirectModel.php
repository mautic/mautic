<?php

namespace Mautic\PageBundle\Model;

use Mautic\CoreBundle\Helper\UrlHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Shortener\Shortener;
use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Entity\RedirectRepository;
use Mautic\PageBundle\Event\RedirectGenerationEvent;
use Mautic\PageBundle\PageEvents;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @extends FormModel<Redirect>
 */
class RedirectModel extends FormModel
{
    private Shortener $shortener;

    private RedirectRepository $redirectRepository;

    #[Required]
    public function autowireRedirectModel(
        Shortener $shortener,
        RedirectRepository $redirectRepository,
    ): void {
        $this->shortener          = $shortener;
        $this->redirectRepository = $redirectRepository;
    }

    public function getRepository(): RedirectRepository
    {
        return $this->redirectRepository;
    }

    /**
     * @return Redirect|null
     */
    public function getRedirectById($identifier)
    {
        return $this->redirectRepository->findOneBy(['redirectId' => $identifier]);
    }

    /**
     * Generate a Mautic redirect/passthrough URL.
     *
     * @param array $clickthrough
     *
     * @return string
     */
    public function generateRedirectUrl(
        Redirect $redirect,
        $clickthrough = [],
    ) {
        if ($this->dispatcher->hasListeners(PageEvents::ON_REDIRECT_GENERATE)) {
            $event = new RedirectGenerationEvent($redirect, $clickthrough);
            $this->dispatcher->dispatch($event, PageEvents::ON_REDIRECT_GENERATE);

            $clickthrough = $event->getClickthrough();
        }

        return $this->buildUrl(
            'mautic_url_redirect',
            ['redirectId' => $redirect->getRedirectId()],
            true,
            $clickthrough
        );
    }

    /**
     * Generate UTMs params for url.
     */
    public function getUtmTagsForUrl($rawUtmTags): array
    {
        $utmTags = [];
        foreach ($rawUtmTags as $utmTag => $value) {
            $utmTags[str_replace('utm', 'utm_', strtolower($utmTag))] = $value;
        }

        return $utmTags;
    }

    /**
     * Get a Redirect entity by URL.
     *
     * Use Mautic\PageBundle\Model\TrackableModel::getTrackableByUrl() if associated with a channel
     *
     * @return Redirect|null
     */
    public function getRedirectByUrl($url)
    {
        // Ensure the URL saved to the database does not have encoded ampersands
        $url = UrlHelper::decodeAmpersands($url);

        $redirect = $this->redirectRepository->findOneBy(['url' => $url]);

        if (null == $redirect) {
            return $this->createRedirectEntity($url);
        }

        return $redirect;
    }

    /**
     * Get Redirect entities by an array of URLs.
     *
     * @return array<Redirect>
     */
    public function getRedirectsByUrls(array $urls)
    {
        /** @var array<Redirect> $redirects */
        $redirects   = $this->redirectRepository->findByUrls(array_values($urls));
        $newEntities = [];

        /** @var array<string, Redirect> $return */
        $return = [];

        /** @var array<string, Redirect> $byUrl */
        $byUrl = [];

        foreach ($redirects as $redirect) {
            $byUrl[$redirect->getUrl()] = $redirect;
        }

        foreach ($urls as $key => $url) {
            if (empty($url)) {
                continue;
            }

            if (isset($byUrl[$url])) {
                $return[$key] = $byUrl[$url];
            } else {
                $redirect      = $this->createRedirectEntity($url);
                $newEntities[] = $redirect;
                $return[$key]  = $redirect;
            }
        }

        // Save new entities
        if (count($newEntities)) {
            $this->redirectRepository->saveEntities($newEntities);
        }

        unset($redirects, $newEntities, $byUrl);

        return $return;
    }

    /**
     * Create a Redirect entity for URL.
     */
    public function createRedirectEntity($url): Redirect
    {
        $redirect = new Redirect();
        $redirect->setUrl($url);
        $redirect->setRedirectId();

        $this->setTimestamps($redirect, true);

        return $redirect;
    }

    /**
     * @param array<mixed> $utmTags
     */
    public function applyUtmTags(string $url, array $utmTags): string
    {
        if ([] === $utmTags) {
            return $url;
        }

        $utmTags         = $this->getUtmTagsForUrl($utmTags);
        $appendUtmString = http_build_query($utmTags, '', '&');

        return UrlHelper::appendQueryToUrl($url, $appendUtmString);
    }

    public function shortenUrl(string $url): string
    {
        return $this->shortener->shortenUrl($url);
    }
}
