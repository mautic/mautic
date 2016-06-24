<?php
/**
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\DynamicContentBundle\Model;

use Mautic\CoreBundle\Model\FormModel;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\DynamicContentBundle\Entity\DynamicContentRepository;
use Mautic\DynamicContentBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\Lead;

class DynamicContentModel extends FormModel
{
    /**
     * {@inheritdoc}
     *
     * @return DynamicContentRepository
     */
    public function getRepository()
    {
        /** @var DynamicContentRepository $repo */
        $repo = $this->em->getRepository('MauticDynamicContentBundle:DynamicContent');

        $repo->setTranslator($this->translator);

        return $repo;
    }

    /**
     * @return \Mautic\DynamicContentBundle\Entity\StatRepository
     */
    public function getStatRepository()
    {
        return $this->em->getRepository('MauticDynamicContentBundle:Stat');
    }

    /**
     * Here just so PHPStorm calms down about type hinting.
     * 
     * @param null $id
     *
     * @return null|DynamicContent
     */
    public function getEntity($id = null)
    {
        return parent::getEntity($id);
    }

    /**
     * {@inheritdoc}
     *
     * @param       $entity
     * @param       $formFactory
     * @param null  $action
     * @param array $options
     * 
     * @return mixed
     * 
     * @throws \InvalidArgumentException
     */
    public function createForm($entity, $formFactory, $action = null, $options = [])
    {
        if (!$entity instanceof DynamicContent) {
            throw new \InvalidArgumentException('Entity must be of class DynamicContent');
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create('dwc', $entity, $options);
    }

    /**
     * Get the variant parent/children.
     *
     * @param DynamicContent $entity
     *
     * @return array
     */
    public function getVariants(DynamicContent $entity)
    {
        $parent = $entity->getVariantParent();

        if (!empty($parent)) {
            $children = $parent->getVariantChildren();
        } else {
            $parent = $entity;
            $children = $entity->getVariantChildren();
        }

        if (empty($children)) {
            $children = [];
        }

        return [$parent, $children];
    }

    /**
     * @param DynamicContent $dwc
     * @param Lead           $lead
     * @param                $slot
     */
    public function setSlotContentForLead(DynamicContent $dwc, Lead $lead, $slot)
    {
        $qb = $this->em->getConnection()->createQueryBuilder();

        $qb->insert(MAUTIC_TABLE_PREFIX.'dynamic_content_lead_data')
            ->values([
                'lead_id' => $lead->getId(),
                'dynamic_content_id' => $dwc->getId(),
                'slot' => ':slot',
                'date_added' => $qb->expr()->literal((new \DateTime())->format('Y-m-d H:i:s'))
            ])->setParameter('slot', $slot);
        
        $qb->execute();
    }

    /**
     * @param      $slot
     * @param Lead $lead
     * 
     * @return DynamicContent
     */
    public function getSlotContentForLead($slot, Lead $lead)
    {
        $qb = $this->em->getConnection()->createQueryBuilder();
        
        $qb->select('dc.id, dc.content')
            ->from(MAUTIC_TABLE_PREFIX.'dynamic_content', 'dc')
            ->leftJoin('dc', MAUTIC_TABLE_PREFIX.'dynamic_content_lead_data', 'dcld', 'dcld.dynamic_content_id = dc.id')
            ->andWhere($qb->expr()->eq('dcld.slot', ':slot'))
            ->andWhere($qb->expr()->eq('dcld.lead_id', ':lead_id'))
            ->setParameter('slot', $slot)
            ->setParameter('lead_id', $lead->getId())
            ->orderBy('dcld.date_added', 'DESC')
            ->addOrderBy('dcld.id', 'DESC');

        return $qb->execute()->fetch();
    }

    /**
     * @param DynamicContent $dynamicContent
     * @param Lead           $lead
     * @param string         $source
     */
    public function createStatEntry(DynamicContent $dynamicContent, Lead $lead, $source = null)
    {
        $stat = new Stat();
        $stat->setDateSent(new \DateTime());
        $stat->setLead($lead);
        $stat->setDynamicContent($dynamicContent);
        $stat->setSource($source);

        $this->getStatRepository()->saveEntity($stat);
    }
}
