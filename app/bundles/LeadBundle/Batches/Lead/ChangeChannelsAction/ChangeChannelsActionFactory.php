<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Batches\Lead\ChangeChannelsAction;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Entity\FrequencyRuleRepository;
use Mautic\LeadBundle\Model\DoNotContact;
use Mautic\LeadBundle\Model\LeadModel;

final class ChangeChannelsActionFactory implements ChangeChannelsActionFactoryInterface
{
    /**
     * @var LeadModel
     */
    private $leadModel;

    /**
     * @var CorePermissions
     */
    private $corePermissions;

    /**
     * @var DoNotContact
     */
    private $doNotContact;

    /**
     * @var FrequencyRuleRepository
     */
    private $frequencyRuleRepository;

    /**
     * ChangeChannelsActionFactory constructor.
     *
     * @param LeadModel               $leadModel
     * @param CorePermissions         $corePermissions
     * @param DoNotContact            $doNotContact
     * @param FrequencyRuleRepository $frequencyRuleRepository
     */
    public function __construct(LeadModel $leadModel, CorePermissions $corePermissions, DoNotContact $doNotContact, FrequencyRuleRepository $frequencyRuleRepository)
    {
        $this->leadModel               = $leadModel;
        $this->corePermissions         = $corePermissions;
        $this->doNotContact            = $doNotContact;
        $this->frequencyRuleRepository = $frequencyRuleRepository;
    }

    /**
     * @see ChangeChannelsActionFactoryInterface::create()
     * {@inheritdoc}
     */
    public function create(
        array $leadsIds,
        array $subscribedChannels,
        array $requestParameters,
        $preferredChannel
    ) {
        return new ChangeChannelsAction(
            $leadsIds,
            $subscribedChannels,
            $requestParameters,
            $this->leadModel,
            $this->corePermissions,
            $this->doNotContact,
            $this->frequencyRuleRepository,
            $preferredChannel
        );
    }
}
