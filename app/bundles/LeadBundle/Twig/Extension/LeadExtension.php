<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Twig\Extension;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Twig\Helper\AvatarHelper;
use Twig\Attribute\AsTwigFunction;

final readonly class LeadExtension
{
    public function __construct(
        private AvatarHelper $avatarHelper,
    ) {
    }

    /**
     * @see AvatarHelper::getAvatar
     *
     * @return mixed
     */
    #[AsTwigFunction(name: 'leadGetAvatar')]
    public function getAvatar(Lead $lead)
    {
        return $this->avatarHelper->getAvatar($lead);
    }
}
