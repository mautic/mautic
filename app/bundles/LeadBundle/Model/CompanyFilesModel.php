<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Model;

use Mautic\CoreBundle\Model\FormModel;
use Mautic\LeadBundle\Entity\CompanyFiles;

/**
 * Class CompanyFilesModel
 * {@inheritdoc}
 */
class CompanyFilesModel extends FormModel
{
    /**
     * {@inheritdoc}
     *
     * @return \Mautic\LeadBundle\Entity\CompanyFilesRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticLeadBundle:CompanyFiles');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getPermissionBase()
    {
        return 'lead:leads';
    }

    /**
     * Get a specific entity or generate a new one if id is empty.
     *
     * @param int $id
     *
     * @return null|object
     */
    public function getEntity($id = null)
    {
        if (is_null($id)) {
            return new CompanyFiles();
        }

        return parent::getEntity($id);
    }

    /**
     * @param CompanyFiles $entity
     * @param bool         $unlock
     */
    public function saveEntity($entity, $unlock = true)
    {
        parent::saveEntity($entity, $unlock);
    }
}
