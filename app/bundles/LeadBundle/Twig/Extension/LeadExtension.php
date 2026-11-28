<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Twig\Extension;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Twig\Helper\AvatarHelper;
use Mautic\LeadBundle\Twig\Helper\ListRowTemplateHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class LeadExtension extends AbstractExtension
{
    public function __construct(
        private readonly AvatarHelper $avatarHelper,
        private readonly ListRowTemplateHelper $listRowTemplateHelper,
    ) {
    }

    /**
     * @see Twig_Extension::getFunctions()
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('leadGetAvatar', $this->getAvatar(...)),
            new TwigFunction('leadListRowTemplateOverridden', $this->listRowTemplateHelper->isOverridden(...)),
        ];
    }

    /**
     * @see AvatarHelper::getAvatar
     *
     * @return mixed
     */
    public function getAvatar(Lead $lead)
    {
        return $this->avatarHelper->getAvatar($lead);
    }
}
