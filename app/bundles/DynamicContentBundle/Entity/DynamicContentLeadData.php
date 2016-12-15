<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DynamicContentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\CommonEntity;
use Mautic\LeadBundle\Entity\Lead;

class DynamicContentLeadData extends CommonEntity
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var DynamicContent
     */
    private $dynamicContent;

    /**
     * @var Lead
     */
    private $lead;

    /**
     * @var \DateTime
     */
    private $dataAdded;

    /**
     * @var string
     */
    private $slot;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('dynamic_content_lead_data');

        $builder->addIdColumns(false, false);

        $builder->addDateAdded(true);

        $builder->createManyToOne('lead', 'Mautic\LeadBundle\Entity\Lead')
            ->inversedBy('id')
            ->addJoinColumn('lead_id', 'id')
            ->build();

        $builder->createManyToOne('dynamicContent', 'DynamicContent')
            ->inversedBy('id')
            ->addJoinColumn('dynamic_content_id', 'id')
            ->build();

        $builder->createField('slot', 'text')
            ->columnName('slot')
            ->build();
    }
}
