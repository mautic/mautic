<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Model;

use Mautic\CoreBundle\Model\FormModel;
use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class PluginModel.
 */
class PluginModel extends FormModel
{
    /**
     * @var FieldModel
     */
    protected $leadFieldModel;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * PluginModel constructor.
     *
     * @param FieldModel          $leadFieldModel
     * @param TranslatorInterface $translator
     */
    public function __construct(FieldModel $leadFieldModel, TranslatorInterface $translator)
    {
        $this->leadFieldModel = $leadFieldModel;
        $this->translator     = $translator;
    }

    /**
     * {@inheritdoc}
     *
     * @return \Mautic\PluginBundle\Entity\PluginRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticPluginBundle:Plugin');
    }

    public function getIntegrationEntityRepository()
    {
        return $this->em->getRepository('MauticPluginBundle:IntegrationEntity');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissionBase()
    {
        return 'plugin:plugins';
    }

    /**
     * Get lead fields used in selects/matching.
     */
    public function getLeadFields()
    {
        $leadFields                                                                 =  $this->leadFieldModel->getFieldList();
        $leadFields[$this->translator->trans('mautic.lead.field.group.core')]['id'] = $this->translator->trans('mautic.lead.report.contact_id');

        return $leadFields;
    }

    /**
     * Get Company fields.
     */
    public function getCompanyFields()
    {
        return $this->leadFieldModel->getFieldList(true, true, ['isPublished' => true, 'object' => 'company']);
    }

    public function saveFeatureSettings($entity)
    {
        $this->em->persist($entity);
        $this->em->flush();
    }
}
