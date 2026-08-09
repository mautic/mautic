<?php

namespace Mautic\CategoryBundle\Model;

use Mautic\CategoryBundle\CategoryEvents;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CategoryBundle\Entity\CategoryRepository;
use Mautic\CategoryBundle\Event\CategoryEvent;
use Mautic\CategoryBundle\Event\CategoryTypeEntityEvent;
use Mautic\CategoryBundle\Form\Type\CategoryType;
use Mautic\CoreBundle\Model\AjaxLookupModelInterface;
use Mautic\CoreBundle\Model\FormModel;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @extends FormModel<Category>
 */
class CategoryModel extends FormModel implements AjaxLookupModelInterface
{
    /**
     * @var array<string,mixed[]>
     */
    private array $categoriesByBundleCache = [];

    protected RequestStack $requestStack;

    private CategoryRepository $categoryRepository;

    #[Required]
    public function autowireCategoryModel(
        RequestStack $requestStack,
        CategoryRepository $categoryRepository,
    ): void {
        $this->requestStack       = $requestStack;
        $this->categoryRepository = $categoryRepository;
    }

    public function getRepository(): CategoryRepository
    {
        return $this->categoryRepository;
    }

    public function getNameGetter(): string
    {
        return 'getTitle';
    }

    public function getPermissionBase(?string $bundle = null): string
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
        $testAlias = $alias;
        $bundle    = $entity->getBundle();
        $count     = $this->categoryRepository->checkUniqueCategoryAlias($bundle, $testAlias, $entity);
        $aliasTag  = $count;

        while ($count) {
            $testAlias = $alias.$aliasTag;
            $count     = $this->categoryRepository->checkUniqueCategoryAlias($bundle, $testAlias, $entity);
            ++$aliasTag;
        }
        if ($testAlias !== $alias) {
            $alias = $testAlias;
        }
        $entity->setAlias($alias);

        parent::saveEntity($entity, $unlock);
    }

    /**
     * @param string|null $action
     * @param array       $options
     */
    public function createForm($entity, $action = null, $options = []): FormInterface
    {
        if (!$entity instanceof Category) {
            throw new MethodNotAllowedHttpException(['Category']);
        }
        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $this->formFactory->create(CategoryType::class, $entity, $options);
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
     * @throws MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, ?Event $event = null): ?Event
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
            if (!$event instanceof Event) {
                $event = new CategoryEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($event, $name);

            return $event;
        }

        return null;
    }

    /**
     * @param string|array<int, string> $filter
     * @param array<string, mixed>      $options
     *
     * @return array<mixed>
     */
    public function getLookupResults(string $type, string|array $filter = '', int $limit = 10, int $start = 0, array $options = []): array
    {
        $filterString = is_array($filter) ? implode('.', $filter) : $filter;
        $key          = $type.$filterString.$limit;

        if (!isset($options['for_lookup']) && !empty($this->categoriesByBundleCache[$key])) {
            return $this->categoriesByBundleCache[$key];
        }

        $result = $this->categoryRepository->getCategoryList($type, $filter, $limit, $start);

        if (!isset($options['for_lookup'])) {
            return $this->categoriesByBundleCache[$key] = $result;
        }

        $data = [];
        foreach ($result as $entity) {
            $data[] = [
                'label' => $entity['title'],
                'value' => $entity['id'],
            ];
        }

        return $data;
    }

    /**
     * @return array<mixed>
     */
    public function getUsage(Category $category): array
    {
        $bundle = $category->getBundle();

        $types = [];
        if ($this->dispatcher->hasListeners(CategoryTypeEntityEvent::class)) {
            $event = $this->dispatcher->dispatch(new CategoryTypeEntityEvent());
            $types = $event->getCategoryTypeEntity($bundle);
        }

        $data = [];
        foreach ($types as $type) {
            $class     = $type['class'];
            $resources = $this->em->getRepository($class)->findBy(['category' => $category->getId()]);

            if (!$resources) {
                continue;
            }

            $data = array_merge(array_map(fn ($resource): array => [
                'label' => $type['label'],
                'id'    => $resource->getId(),
            ], $resources), $data);
        }

        return $data;
    }
}
