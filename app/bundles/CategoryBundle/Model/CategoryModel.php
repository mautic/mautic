<?php

namespace Mautic\CategoryBundle\Model;

use Mautic\CategoryBundle\CategoryEvents;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CategoryBundle\Event\CategoryEvent;
use Mautic\CategoryBundle\Form\Type\CategoryType;
use Mautic\CoreBundle\Model\FormModel;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class CategoryModel
 * {@inheritdoc}
 */
class CategoryModel extends FormModel
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * CategoryModel constructor.
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getRepository()
    {
        return $this->em->getRepository('MauticCategoryBundle:Category');
    }

    public function getNameGetter()
    {
        return 'getTitle';
    }

    public function getPermissionBase($bundle = null)
    {
        if (null === $bundle) {
            $bundle = $this->requestStack->getCurrentRequest()->get('bundle');
        }

        if ('global' === $bundle || empty($bundle)) {
            $bundle = 'category';
        }

        return $bundle.':categories';
    }

    /**
     * {@inheritdoc}
     *
     * @param $entity
     * @param $unlock
     *
     * @return mixed
     */
    public function saveEntity($entity, $unlock = true)
    {
        $alias = $entity->getAlias();
        if (empty($alias)) {
            $alias = $entity->getTitle();
        }
        $alias = $this->cleanAlias($alias, '', false, '-');

        //make sure alias is not already taken
        $repo      = $this->getRepository();
        $testAlias = $alias;
        $bundle    = $entity->getBundle();
        $count     = $repo->checkUniqueCategoryAlias($bundle, $testAlias, $entity);
        $aliasTag  = $count;

        while ($count) {
            $testAlias = $alias.$aliasTag;
            $count     = $repo->checkUniqueCategoryAlias($bundle, $testAlias, $entity);
            ++$aliasTag;
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
     * @param       $entity
     * @param       $formFactory
     * @param null  $action
     * @param array $options
     *
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = [])
    {
        if (!$entity instanceof Category) {
            throw new MethodNotAllowedHttpException(['Category']);
        }
        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create(CategoryType::class, $entity, $options);
    }

    /**
     * Get a specific entity or generate a new one if id is empty.
     *
     * @param $id
     *
     * @return Category
     */
    public function getEntity($id = null)
    {
        if (null === $id) {
            return new Category();
        }

        return parent::getEntity($id);
    }

    /**
     * {@inheritdoc}
     *
     * @param $action
     * @param $event
     * @param $entity
     * @param $isNew
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null)
    {
        if (!$entity instanceof Category) {
            throw new MethodNotAllowedHttpException(['Category']);
        }

        switch ($action) {
            case 'pre_save':
                $name = CategoryEvents::CATEGORY_PRE_SAVE;
                break;
            case 'post_save':
                $name = CategoryEvents::CATEGORY_POST_SAVE;
                break;
            case 'pre_delete':
                $name = CategoryEvents::CATEGORY_PRE_DELETE;
                break;
            case 'post_delete':
                $name = CategoryEvents::CATEGORY_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new CategoryEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($name, $event);

            return $event;
        } else {
            return null;
        }
    }

    /**
     * Get list of entities for autopopulate fields.
     *
     * @param $bundle
     * @param $filter
     * @param $limit
     *
     * @return array
     */
    public function getLookupResults($bundle, $filter = '', $limit = 10)
    {
        static $results = [];

        $key = $bundle.$filter.$limit;
        if (!isset($results[$key])) {
            $results[$key] = $this->getRepository()->getCategoryList($bundle, $filter, $limit, 0);
        }

        return $results[$key];
    }
}
