<?php

namespace Mautic\PageBundle\Model;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UrlHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Shortener\Shortener;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Entity\RedirectRepository;
use Mautic\PageBundle\Event\RedirectGenerationEvent;
use Mautic\PageBundle\PageEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @extends FormModel<Redirect>
 */
class RedirectModel extends FormModel
{
    public function __construct(
        EntityManagerInterface $em,
        CorePermissions $security,
        EventDispatcherInterface $dispatcher,
        UrlGeneratorInterface $router,
        Translator $translator,
        UserHelper $userHelper,
        LoggerInterface $mauticLogger,
        CoreParametersHelper $coreParametersHelper,
        private Shortener $shortener,
    ) {
        parent::__construct($em, $security, $dispatcher, $router, $translator, $userHelper, $mauticLogger, $coreParametersHelper);
    }

    public function getRepository(): RedirectRepository
    {
        return $this->em->getRepository(Redirect::class);
    }

    /**
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
            $this->dispatcher->dispatch($event, PageEvents::ON_REDIRECT_GENERATE);

            $clickthrough = $event->getClickthrough();
        }

        $url = $this->buildUrl(
            'mautic_url_redirect',
            ['redirectId' => $redirect->getRedirectId()],
            true,
            $clickthrough
        );

        if (!empty($utmTags)) {
            $utmTags         = $this->getUtmTagsForUrl($utmTags);
            $appendUtmString = http_build_query($utmTags, '', '&');
            $url             = UrlHelper::appendQueryToUrl($url, $appendUtmString);
        }

        if ($shortenUrl) {
            $url = $this->shortener->shortenUrl($url);
        }

        return $url;
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
     * @return array<Redirect>
     */
    public function getRedirectsByUrls(array $urls)
    {
        /** @var array<Redirect> $redirects */
        $redirects   = $this->getRepository()->findByUrls(array_values($urls));
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
            $this->getRepository()->saveEntities($newEntities);
        }

        unset($redirects, $newEntities, $byUrl);

        return $return;
    }

    /**
     * Create a Redirect entity for URL.
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
