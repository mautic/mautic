<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MapperBundle\Model;

use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\MapperBundle\Entity\ApplicationClient;
use Mautic\MapperBundle\Form\Type\ApplicationClientType;

/**
 * Class ApplicationClientModel
 * @package Mautic\MapperBundle\Model
 */
class ApplicationClientModel extends FormModel
{
    public function getRepository()
    {
        return $this->em->getRepository('MauticMapperBundle:ApplicationClient');
    }

    public function getNameGetter()
    {
        return "getTitle";
    }

    public function getPermissionBase()
    {
        $request = $this->factory->getRequest();
        $bundle  = $request->get('application');
        return $bundle.':ApplicationClient';
    }

    /**
     * {@inheritdoc}
     *
     * @param       $entity
     * @param       $unlock
     * @return mixed
     */
    public function saveEntity($entity, $unlock = true)
    {
        $alias = $entity->getAlias();
        if (empty($alias)) {
            $alias = strtolower(InputHelper::alphanum($entity->getTitle(), true));
        } else {
            $alias = strtolower(InputHelper::alphanum($alias, true));
        }

        //make sure alias is not already taken
        $repo      = $this->getRepository();
        $testAlias = $alias;
        $count     = $repo->checkUniqueAlias($testAlias, $entity);
        $aliasTag  = $count;

        while ($count) {
            $testAlias = $alias . $aliasTag;
            $count     = $repo->checkUniqueAlias($testAlias, $entity);
            $aliasTag++;
        }
        if ($testAlias != $alias) {
            $alias = $testAlias;
        }
        $entity->setAlias($alias);

        parent::saveEntity($entity, $unlock);
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
        if (!$entity instanceof ApplicationClient) {
            throw new MethodNotAllowedHttpException(array('ApplicationClient'));
        }
        if (!empty($action)) {
            $options['action'] = $action;
        }
        return $formFactory->create(new ApplicationClientType($this->factory), $entity, $options);
    }

    /**
     * Get a specific entity or generate a new one if id is empty
     *
     * @param $id
     * @return null|object
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            return new ApplicationClient();
        }

        $entity = parent::getEntity($id);

        return $entity;
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