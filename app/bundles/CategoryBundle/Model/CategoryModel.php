<?php

namespace Mautic\CategoryBundle\Model;

use Mautic\CategoryBundle\CategoryEvents;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CategoryBundle\Entity\CategoryRepository;
use Mautic\CategoryBundle\Event\CategoryEvent;
use Mautic\CategoryBundle\Form\Type\CategoryType;
use Mautic\CoreBundle\Model\FormModel;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @extends FormModel<Category>
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

    public function getRepository(): CategoryRepository
    {
        $result = $this->em->getRepository(Category::class);
        \assert($result instanceof CategoryRepository);

        return $result;
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
     * @param             $entity
     * @param             $formFactory
     * @param string|null $action
     * @param array       $options
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
     * @return Category|null
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

            $this->dispatcher->dispatch($event, $name);

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
