<?php

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Helper;

use Mautic\CoreBundle\Exception\InvalidDecodedStringException;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\UrlHelper;
use Mautic\LeadBundle\Helper\PrimaryCompanyHelper;
use Mautic\LeadBundle\Helper\TokenHelper;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Model\PageModel;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

class RedirectHelper
{
    private ?Request $request;

    private IpLookupHelper $ipLookupHelper;

    private LeadModel $leadModel;

    private PageModel $pageModel;

    private Logger $logger;

    private PrimaryCompanyHelper $primaryCompanyHelper;

    private TranslatorInterface $translator;

    public function __construct(RequestStack $requestStack, IpLookupHelper $ipLookupHelper, LeadModel $leadModel, PageModel $pageModel, Logger $logger, PrimaryCompanyHelper $primaryCompanyHelper, TranslatorInterface $translator)
    {
        $this->request              = $requestStack->getCurrentRequest();
        $this->ipLookupHelper       = $ipLookupHelper;
        $this->leadModel            = $leadModel;
        $this->pageModel            = $pageModel;
        $this->logger               = $logger;
        $this->primaryCompanyHelper = $primaryCompanyHelper;
        $this->translator           = $translator;
    }

    /**
     * @throws \Exception
     */
    public function trackedRedirect(Redirect $redirect): RedirectResponse
    {
        // Ensure the URL does not have encoded ampersands
        $url = str_replace('&amp;', '&', $redirect->getUrl());

        // Get query string
        $query = $this->request->query->all();

        // Unset the clickthrough from the URL query
        $ct = $query['ct'];
        unset($query['ct']);

        // Tak on anything left to the URL
        if (count($query)) {
            $url = UrlHelper::appendQueryToUrl($url, http_build_query($query));
        }

        // If the IP address is not trackable, it means it came form a configured "do not track" IP or a "do not track" user agent
        // This prevents simulated clicks from 3rd party services such as URL shorteners from simulating clicks
        $ipAddress = $this->ipLookupHelper->getIpAddress();
        if ($ipAddress->isTrackable()) {
            // Search replace lead fields in the URL
            $leadModel = $this->leadModel;
            $pageModel = $this->pageModel;

            try {
                $lead = $leadModel->getContactFromRequest(['ct' => $ct]);
                $pageModel->hitPage($redirect, $this->request, 200, $lead);
            } catch (InvalidDecodedStringException $e) {
                // Invalid ct value so we must unset it
                // and process the request without it

                $this->logger->error(sprintf('Invalid clickthrough value: %s', $ct), ['exception' => $e]);

                $this->request->request->set('ct', '');
                $this->request->query->set('ct', '');
                $lead = $leadModel->getContactFromRequest();
                $pageModel->hitPage($redirect, $this->request, 200, $lead);
            }

            $leadArray            = ($lead) ? $this->primaryCompanyHelper->getProfileFieldsWithPrimaryCompany($lead) : [];

            $url = TokenHelper::findLeadTokens($url, $leadArray, true);
        }

        $url = UrlHelper::sanitizeAbsoluteUrl($url);

        if (!UrlHelper::isValidUrl($url)) {
            return new NotFoundHttpException($this->translator->trans('mautic.core.url.error.404', ['%url%' => $url]));
        }

        return new RedirectResponse($url);
    }
}
