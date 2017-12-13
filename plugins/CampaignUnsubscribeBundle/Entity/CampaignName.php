<?php

/*
 * @copyright   2017 Partout D.N.A. All rights reserved
 * @author      Partout D.N.A.
 *
 * @link        https://partout.nl
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CampaignUnsubscribeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\CommonEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * @ORM\Table(name="unsubscribe_campaign_names")
 * @ORM\Entity
 */
class CampaignName extends CommonEntity
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Campaign
     */
    protected $campaign;

    /**
     * @ORM\Column(name="name", type="string")
     */
    protected $name;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('unsubscribe_campaign_names');
        $builder->addId();
        $builder->createOneToOne('campaign', 'Mautic\CampaignBundle\Entity\Campaign')
            ->addJoinColumn('campaign_id', 'id', false, false, 'CASCADE')
            ->build();
        $builder->addNamedField('name', 'string', 'name');
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {

        $metadata->addPropertyConstraint(
            'name',
            new NotBlank(
                [
                    'message' => 'mautic.core.value.required',
                ]
            )
        );

        $metadata->addPropertyConstraint(
            'campaign',
            new NotBlank(
                [
                    'message' => 'mautic.core.value.required',
                ]
            )
        );

        $metadata->addConstraint(new UniqueEntity([
            'fields' => 'campaign'
        ]));
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getCampaign()
    {
        return $this->campaign;
    }

    /**
     * Set campaign.
     *
     * @param $campaign
     */
    public function setCampaign($campaign)
    {
        $this->campaign = $campaign;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
