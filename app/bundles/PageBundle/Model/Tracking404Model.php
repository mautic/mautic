<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Model;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Entity\Redirect;
use Symfony\Component\HttpFoundation\Request;

class Tracking404Model
{
    public function __construct(
        private readonly CoreParametersHelper $coreParametersHelper,
        private readonly ContactTracker $contactTracker,
        private readonly PageModel $pageModel,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function hitPage(Redirect|Page|null $entity, Request $request): void
    {
        $this->pageModel->hitPage($entity, $request, 404);
    }

    public function isTrackable(): bool
    {
        if (!$this->coreParametersHelper->get('do_not_track_404_anonymous')) {
            return true;
        }
        // already tracked and identified contact
        return ($lead = $this->contactTracker->getContactByTrackedDevice()) && !$lead->isAnonymous();
    }
}
