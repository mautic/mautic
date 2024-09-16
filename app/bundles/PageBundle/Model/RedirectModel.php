<?php

namespace Mautic\PageBundle\Model;

use Mautic\CoreBundle\Helper\UrlHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Event\RedirectGenerationEvent;
use Mautic\PageBundle\PageEvents;

/**
 * Class RedirectModel.
 */
class RedirectModel extends FormModel
{
    /**
     * @var UrlHelper
     */
    protected $urlHelper;

    /**
     * RedirectModel constructor.
     */
    public function __construct(UrlHelper $urlHelper)
    {
        $this->urlHelper = $urlHelper;
    }

    /**
     * {@inheritdoc}
     *
     * @return \Mautic\PageBundle\Entity\RedirectRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticPageBundle:Redirect');
    }

    /**
     * @param $identifier
     *
     * @return Redirect|null
     */
    public function getRedirectById($identifier)
    {
        return $this->getRepository()->findOneBy(['redirectId' => $identifier]);
    }

    /**
     * Generate a Mautic redirect/passthrough URL.
     *
     * @param array $clickthrough
     * @param bool  $shortenUrl
     * @param array $utmTags
     *
     * @return string
     */
    public function generateRedirectUrl(Redirect $redirect, $clickthrough = [], $shortenUrl = false, $utmTags = [])
    {
        if ($this->dispatcher->hasListeners(PageEvents::ON_REDIRECT_GENERATE)) {
            $event = new RedirectGenerationEvent($redirect, $clickthrough);
            $this->dispatcher->dispatch(PageEvents::ON_REDIRECT_GENERATE, $event);

            $clickthrough = $event->getClickthrough();
        }

        $url = $this->buildUrl(
            'mautic_url_redirect',
            ['redirectId' => $redirect->getRedirectId()],
            true,
            $clickthrough,
            $shortenUrl
        );

        if (!empty($utmTags)) {
            $utmTags         = $this->getUtmTagsForUrl($utmTags);
            $appendUtmString = http_build_query($utmTags, '', '&');
            $url             = UrlHelper::appendQueryToUrl($url, $appendUtmString);
        }

        if ($shortenUrl) {
            $url = $this->urlHelper->buildShortUrl($url);
        }

        return $url;
    }

    /**
     * Generate UTMs params for url.
     *
     * @return array
     */
    public function getUtmTagsForUrl($rawUtmTags)
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
     * @param  $url
     *
     * @return Redirect|null
     */
    public function getRedirectByUrl($url)
    {
        // Ensure the URL saved to the database does not have encoded ampersands
        $url = UrlHelper::decodeAmpersands($url);

        $repo     = $this->getRepository();
        $redirect = $repo->findOneBy(['url' => $url]);

        if (null == $redirect) {
            $redirect = $this->createRedirectEntity($url);
        }

        return $redirect;
    }

    /**
     * Get Redirect entities by an array of URLs.
     *
     * @return array
     */
    public function getRedirectsByUrls(array $urls)
    {
        $redirects   = $this->getRepository()->findByUrls(array_values($urls));
        $newEntities = [];
        $return      = [];
        $byUrl       = [];

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
            $this->getRepository()->saveEntities($newEntities);
        }

        unset($redirects, $newEntities, $byUrl);

        return $return;
    }

    /**
     * Create a Redirect entity for URL.
     *
     * @param $url
     *
     * @return Redirect
     */
    public function createRedirectEntity($url)
    {
        $redirect = new Redirect();
        $redirect->setUrl($url);
        $redirect->setRedirectId();

        $this->setTimestamps($redirect, true);

        return $redirect;
    }
}
