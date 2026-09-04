<?php

namespace Mautic\PageBundle\Model;

use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\PageBundle\Entity\VideoHit;
use Mautic\PageBundle\Entity\VideoHitRepository;
use Mautic\PageBundle\Event\VideoHitEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @extends FormModel<VideoHit>
 */
final class VideoModel extends FormModel
{
    public static function getName(): string
    {
        return 'page.video';
    }

    private IpLookupHelper $ipLookupHelper;

    private ContactTracker $contactTracker;

    private VideoHitRepository $videoHitRepository;

    #[Required]
    public function autowireVideoModel(
        IpLookupHelper $ipLookupHelper,
        ContactTracker $contactTracker,
        VideoHitRepository $videoHitRepository,
    ): void {
        $this->ipLookupHelper     = $ipLookupHelper;
        $this->contactTracker     = $contactTracker;
        $this->videoHitRepository = $videoHitRepository;
    }

    public function getPermissionBase(): string
    {
        return 'page:pages';
    }

    public function getNameGetter(): string
    {
        return 'getTitle';
    }

    /**
     * @param string $guid
     *
     * @return VideoHit
     */
    public function getHitForLeadByGuid(Lead $lead, $guid)
    {
        return $this->videoHitRepository->getHitForLeadByGuid($lead, $guid);
    }

    /**
     * @param Request $request
     * @param string  $code
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Exception
     */
    public function hitVideo($request, $code = '200'): void
    {
        // don't skew results with in-house hits
        if (!$this->security->isAnonymous()) {
            // return;
        }

        $lead = $this->contactTracker->getContact();
        $guid = $request->get('guid');

        $hit = ($lead) ? $this->getHitForLeadByGuid($lead, $guid) : new VideoHit();

        $hit->setGuid($guid);
        $hit->setDateHit(new \DateTime());

        $hit->setDuration($request->get('duration'));
        $hit->setUrl($request->get('url'));
        $hit->setTimeWatched($request->get('total_watched'));

        // check for existing IP
        $ipAddress = $this->ipLookupHelper->getIpAddress();
        $hit->setIpAddress($ipAddress);

        // Store query array
        $query = $request->query->all();
        unset($query['d']);
        $hit->setQuery($query);

        if ($lead) {
            $hit->setLead($lead);
        }

        // glean info from the IP address
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

        // get a list of the languages the user prefers
        $browserLanguages = $request->server->get('HTTP_ACCEPT_LANGUAGE');
        if (!empty($browserLanguages)) {
            $languages = explode(',', $browserLanguages);
            foreach ($languages as $k => $l) {
                if (($pos = strpos(';q=', $l)) !== false) {
                    // remove weights
                    $languages[$k] = substr($l, 0, $pos);
                }
            }
            $hit->setBrowserLanguages($languages);
        }

        // Wrap in a try/catch to prevent deadlock errors on busy servers
        try {
            $this->em->persist($hit);
            $this->em->flush();
        } catch (\Exception $exception) {
            if (MAUTIC_ENV === 'dev') {
                throw $exception;
            }
            $this->logger->error(
                $exception->getMessage(),
                ['exception' => $exception]
            );
        }

        if ($this->dispatcher->hasListeners(VideoHitEvent::class)) {
            $event = new VideoHitEvent($hit, $request, $code);
            $this->dispatcher->dispatch($event);
        }
    }
}
