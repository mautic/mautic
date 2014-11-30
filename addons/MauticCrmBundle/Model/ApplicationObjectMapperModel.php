<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticCrmBundle\Model;

use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Model\FormModel;
use MauticAddon\MauticCrmBundle\Entity\ApplicationObjectMapper;
use MauticAddon\MauticCrmBundle\Form\Type\ApplicationObjectMapperType;

/**
 * Class ApplicationObjectMapper
 * @package MauticAddon\MauticCrmBundle\Model
 */
class ApplicationObjectMapperModel extends FormModel
{
    public function getRepository()
    {
        return $this->em->getRepository('MauticCrmBundle:ApplicationObjectMapper');
    }

    public function getNameGetter()
    {
        return "getObjectName";
    }

    public function getPermissionBase()
    {
        $request = $this->factory->getRequest();
        $bundle  = $request->get('application');
        return $bundle.':ApplicationObjectMapper';
    }

    /**
     * {@inheritdoc}
     *
     * @param      $entity
     * @param      $formFactory
     * @param null $action
     * @param array $options
     * @return mixed
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = array())
    {
        if (!$entity instanceof ApplicationObjectMapper) {
            throw new MethodNotAllowedHttpException(array('ApplicationObjectMapper'));
        }
        if (!empty($action)) {
            $options['action'] = $action;
        }
        return $formFactory->create(new ApplicationObjectMapperType($this->factory), $entity, $options);
    }

    /**
     * Get a specific entity or generate a new one if id is empty
     *
     * @param $id
     * @return null|object
     */
    public function getEntity($object = null, $client_id = null)
    {
        if ($object === null && $client_id === null) {
            return new ApplicationObjectMapper();
        }

        $repo = $this->getRepository();
        $entity = $repo->findOneBy(array('objectName' => $object, 'applicationClientId' => $client_id));

        if ($entity === null) {
            return new ApplicationObjectMapper();
        }

        return $entity;
    }

    public function getByApplicationIdAndObject($client_id, $object)
    {
        $repo = $this->getRepository();
        $repo->getByClientAndObject($client_id, $object);
    }

    /**
     * Delete an entity
     *
     * @param  $entity
     * @return null|object
     */
    public function deleteEntity($entity)
    {
        $repo       = $this->getRepository();
        $tableAlias = $repo->getTableAlias();

        $entities = $this->getEntities(array(
            'filter' => array(
                'force' => array(
                    array(
                        'column' => $tableAlias.'.id',
                        'expr'   => 'eq',
                        'value'  => $entity->getId()
                    )
                )
            )
        ));

        if (!empty($entities)) {
            $this->saveEntities($entities, false);
        }

        parent::deleteEntity($entity);
    }
}
