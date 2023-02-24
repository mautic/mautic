<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Templating\Twig\Extension;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Templating\Helper\AvatarHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class LeadExtension extends AbstractExtension
{
    /**
     * @var AvatarHelper
     */
    protected $avatarHelper;

    public function __construct(AvatarHelper $avatarHelper)
    {
        $this->avatarHelper = $avatarHelper;
    }

    /**
     * @see Twig_Extension::getFunctions()
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('leadGetAvatar', [$this, 'getAvatar']),
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
