<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Twig\Extension;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Twig\Helper\AvatarHelper;

class LeadExtension
{
    public function __construct(
        protected AvatarHelper $avatarHelper,
    ) {
    }

    /**
     * @see AvatarHelper::getAvatar
     *
     * @return mixed
     */
    #[\Twig\Attribute\AsTwigFunction('leadGetAvatar')]
    public function getAvatar(Lead $lead)
    {
        return $this->avatarHelper->getAvatar($lead);
    }
}
