<?php

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\EventListener;

use Mautic\LeadBundle\Helper\PrimaryCompanyHelper;
use Mautic\LeadBundle\Helper\TokenHelper;
use Mautic\PageBundle\Event as Events;
use Mautic\PageBundle\PageEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PageRedirectSubscriber implements EventSubscriberInterface
{
    /**
     * @var PrimaryCompanyHelper
     */
    private $primaryCompanyHelper;

    /**
     * @var \Mautic\AssetBundle\Helper\tokenhelper
     */
    private $assetTokenHelper;

    /**
     * @var \Mautic\PageBundle\Helper\TokenHelper
     */
    private $pageTokenHelper;

    public function __construct(
        PrimaryCompanyHelper $primaryCompanyHelper,
        \Mautic\AssetBundle\Helper\tokenhelper $assetTokenHelper,
        \Mautic\PageBundle\Helper\TokenHelper $pageTokenHelper
    ) {
        $this->primaryCompanyHelper = $primaryCompanyHelper;
        $this->assetTokenHelper     = $assetTokenHelper;
        $this->pageTokenHelper      = $pageTokenHelper;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            PageEvents::ON_REDIRECT   => ['onRedirectReplaceTokens', 0],
        ];
    }

    public function onRedirectReplaceTokens(Events\RedirectEvent $event): void
    {
        $url  = $event->getUrl();
        $lead = $event->getLead();

        if (!$lead) {
            return;
        }

        $url = TokenHelper::findLeadTokens($url, $this->primaryCompanyHelper->getProfileFieldsWithPrimaryCompany($lead), true);
        $url = $this->replacePageTokenUrl($url);
        $url = $this->replaceAssetTokenUrl($url);
        $event->setUrl($url);
    }

    /**
     * @param string $url
     */
    private function replaceAssetTokenUrl($url): string
    {
        if ($this->urlIsToken($url)) {
            $tokens = $this->assetTokenHelper->findAssetTokens($url);

            return str_replace(array_keys($tokens), $tokens, $url);
        }

        return $url;
    }

    /**
     * @param string $url
     */
    private function replacePageTokenUrl($url): string
    {
        if ($this->urlIsToken($url)) {
            $tokens = $this->pageTokenHelper->findPageTokens($url);

            return str_replace(array_keys($tokens), $tokens, $url);
        }

        return $url;
    }

    /**
     * @param string $url
     */
    private function urlIsToken($url): bool
    {
        return '{' === substr($url, 0, 1);
    }
}
