<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DynamicContentBundle\Model;

use Mautic\CoreBundle\Model\FormModel;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\DynamicContentBundle\Entity\DynamicContentRepository;

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
     * {@inheritdoc}
     *
     * @param      $entity
     * @param      $formFactory
     * @param null $action
     * @param array $options
     * 
     * @return mixed
     * 
     * @throws \InvalidArgumentException
     */
    public function createForm($entity, $formFactory, $action = null, $options = [])
    {
        if (!$entity instanceof DynamicContent) {
            throw new \InvalidArgumentException('Entity must be of class DynamicContent()');
        }

        $params = (! empty($action)) ? ['action' => $action] : [];

        return $formFactory->create('dwc', $entity, $params);
    }
}
