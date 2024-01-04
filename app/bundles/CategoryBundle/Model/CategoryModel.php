<?php

namespace Mautic\CategoryBundle\Model;

use Doctrine\ORM\EntityManager;
use Mautic\CategoryBundle\CategoryEvents;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CategoryBundle\Entity\CategoryRepository;
use Mautic\CategoryBundle\Event\CategoryEvent;
use Mautic\CategoryBundle\Form\Type\CategoryType;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @extends FormModel<Category>
 */
class CategoryModel extends FormModel
{
    public function __construct(
        protected RequestStack $requestStack,
        EntityManager $em,
        CorePermissions $security,
        EventDispatcherInterface $dispatcher,
        UrlGeneratorInterface $router,
        Translator $translator,
        UserHelper $userHelper,
        LoggerInterface $mauticLogger,
        CoreParametersHelper $coreParametersHelper
    ) {
        parent::__construct($em, $security, $dispatcher, $router, $translator, $userHelper, $mauticLogger, $coreParametersHelper);
    }

    public function getRepository(): CategoryRepository
    {
        return $this->em->getRepository(Category::class);
    }

    public function getNameGetter(): string
    {
        return 'getTitle';
    }

    public function getPermissionBase($bundle = null): string
    {
        if (null === $bundle) {
            $bundle = $this->requestStack->getCurrentRequest()->get('bundle');
        }

        if ('global' === $bundle || empty($bundle)) {
            $bundle = 'category';
        }

        return $bundle.':categories';
    }

    public function saveEntity($entity, $unlock = true): void
    {
        $alias = $entity->getAlias();
        if (empty($alias)) {
            $alias = $entity->getTitle();
        }
        $alias = $this->cleanAlias($alias, '', 0, '-');

        // make sure alias is not already taken
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
     * @param string|null $action
     * @param array       $options
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): \Symfony\Component\Form\FormInterface
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
     */
    public function getEntity($id = null): ?Category
    {
        if (null === $id) {
            return new Category();
        }

        return parent::getEntity($id);
    }

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null): ?Event
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
     */
    public function getLookupResults($bundle, $filter = '', $limit = 10): array
    {
        static $results = [];

        $key = $bundle.$filter.$limit;
        if (!isset($results[$key])) {
            $results[$key] = $this->getRepository()->getCategoryList($bundle, $filter, $limit, 0);
        }

        return $results[$key];
    }
}
