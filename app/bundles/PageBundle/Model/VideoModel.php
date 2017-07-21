<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Model;

use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PageBundle\Entity\VideoHit;
use Mautic\PageBundle\Event\VideoHitEvent;
use Mautic\PageBundle\PageEvents;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class PageModel.
 */
class VideoModel extends FormModel
{
    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var IpLookupHelper
     */
    protected $ipLookupHelper;

    /**
     * VideoModel constructor.
     *
     * @param LeadModel      $leadModel
     * @param IpLookupHelper $ipLookupHelper
     */
    public function __construct(LeadModel $leadModel, IpLookupHelper $ipLookupHelper)
    {
        $this->leadModel      = $leadModel;
        $this->ipLookupHelper = $ipLookupHelper;
    }

    /**
     * @return \Mautic\PageBundle\Entity\VideoHitRepository
     */
    public function getHitRepository()
    {
        return $this->em->getRepository('MauticPageBundle:VideoHit');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissionBase()
    {
        return 'page:pages';
    }

    /**
     * {@inheritdoc}
     */
    public function getNameGetter()
    {
        return 'getTitle';
    }

    /**
     * @param Lead   $lead
     * @param string $guid
     *
     * @return VideoHit
     */
    public function getHitForLeadByGuid(Lead $lead, $guid)
    {
        return $this->getHitRepository()->getHitForLeadByGuid($lead, $guid);
    }

    /**
     * @param Request $request
     * @param string  $code
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Exception
     */
    public function hitVideo($request, $code = '200')
    {
        //don't skew results with in-house hits
        if (!$this->security->isAnonymous()) {
            //return;
        }

        $lead = $this->leadModel->getCurrentLead();
        $guid = $request->get('guid');

        $hit = ($lead) ? $this->getHitForLeadByGuid($lead, $guid) : new VideoHit();

        $hit->setGuid($guid);
        $hit->setDateHit(new \Datetime());

        $hit->setDuration($request->get('duration'));
        $hit->setUrl($request->get('url'));
        $hit->setTimeWatched($request->get('total_watched'));

        //check for existing IP
        $ipAddress = $this->ipLookupHelper->getIpAddress();
        $hit->setIpAddress($ipAddress);

        // Store query array
        $query = $request->query->all();
        unset($query['d']);
        $hit->setQuery($query);

        if ($lead) {
            $hit->setLead($lead);
        }

        //glean info from the IP address
        if ($details = $ipAddress->getIpDetails()) {
            $hit->setCountry($details['country']);
            $hit->setRegion($details['region']);
            $hit->setCity($details['city']);
            $hit->setIsp($details['isp']);
            $hit->setOrganization($details['organization']);
        }

        $hit->setCode($code);
        if (!$hit->getReferer()) {
            $hit->setReferer($request->server->get('HTTP_REFERER'));
        }

        $hit->setUserAgent($request->server->get('HTTP_USER_AGENT'));
        $hit->setRemoteHost($request->server->get('REMOTE_HOST'));

        //get a list of the languages the user prefers
        $browserLanguages = $request->server->get('HTTP_ACCEPT_LANGUAGE');
        if (!empty($browserLanguages)) {
            $languages = explode(',', $browserLanguages);
            foreach ($languages as $k => $l) {
                if ($pos = strpos(';q=', $l) !== false) {
                    //remove weights
                    $languages[$k] = substr($l, 0, $pos);
                }
            }
            $hit->setBrowserLanguages($languages);
        }

        // Wrap in a try/catch to prevent deadlock errors on busy servers
        try {
            $this->em->persist($hit);
            $this->em->flush($hit);
        } catch (\Exception $exception) {
            if (MAUTIC_ENV === 'dev') {
                throw $exception;
            } else {
                $this->logger->addError(
                    $exception->getMessage(),
                    ['exception' => $exception]
                );
            }
        }

        if ($this->dispatcher->hasListeners(PageEvents::VIDEO_ON_HIT)) {
            $event = new VideoHitEvent($hit, $request, $code);
            $this->dispatcher->dispatch(PageEvents::VIDEO_ON_HIT, $event);
        }
    }
}
