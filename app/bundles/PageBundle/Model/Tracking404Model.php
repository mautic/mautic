<?php

namespace Mautic\PageBundle\Model;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Entity\Redirect;
use Symfony\Component\HttpFoundation\Request;

class Tracking404Model
{
    /**
     * Tracking404Model constructor.
     */
    public function __construct(private CoreParametersHelper $coreParametersHelper, private ContactTracker $contactTracker, private PageModel $pageModel)
    {
    }

    /**
     * @throws \Exception
     */
    public function hitPage(Page|Redirect $entity, Request $request): void
    {
        $this->pageModel->hitPage($entity, $request, 404);
    }

    /**
     * @return bool
     */
    public function isTrackable()
    {
        if (!$this->coreParametersHelper->get('do_not_track_404_anonymous')) {
            return true;
        }
        // already tracked and identified contact
        if ($lead = $this->contactTracker->getContactByTrackedDevice()) {
            if (!$lead->isAnonymous()) {
                return true;
            }
        }

        return false;
    }
}
