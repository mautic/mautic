<?php

namespace Mautic\CoreBundle\Model;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Helper\ClickthroughHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Intl\Locales;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @template T of object
 */
abstract class AbstractCommonModel implements MauticModelInterface
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected CorePermissions $security,
        protected EventDispatcherInterface $dispatcher,
        protected UrlGeneratorInterface $router,
        protected Translator $translator,
        protected UserHelper $userHelper,
        protected LoggerInterface $logger,
        protected CoreParametersHelper $coreParametersHelper
    ) {
    }

    /**
     * Retrieve the supported search commands for a repository.
     *
     * @return array
     */
    public function getSupportedSearchCommands()
    {
        return [];
    }

    /**
     * Retrieve the search command list for a repository.
     *
     * @return array
     */
    public function getCommandList()
    {
        $repo = $this->getRepository();

        return $repo->getSearchCommands();
    }

    /**
     * Retrieve the repository for an entity.
     *
     * @return CommonRepository<T>
     */
    public function getRepository()
    {
        static $commonRepo;

        if (null === $commonRepo) {
            $commonRepo = $this->em->getRepository(FormEntity::class);
        }

        return $commonRepo;
    }

    /**
     * Retrieve the permissions base.
     *
     * @return string
     */
    public function getPermissionBase()
    {
        return '';
    }

    /**
     * Return a list of entities.
     *
     * @param array $args [start, limit, filter, orderBy, orderByDir]
     *
     * @return \Doctrine\ORM\Tools\Pagination\Paginator|array
     */
    public function getEntities(array $args = [])
    {
        // set the translator
        $repo = $this->getRepository();

        $repo->setTranslator($this->translator);
        $repo->setCurrentUser($this->userHelper->getUser());

        return $repo->getEntities($args);
    }

    /**
     * Get a specific entity.
     */
    public function getEntity($id = null): ?object
    {
        if (null !== $id) {
            $repo = $this->getRepository();
            if (method_exists($repo, 'getEntity')) {
                return $repo->getEntity($id);
            }

            return $repo->find((int) $id);
        }

        return null;
    }

    /**
     * Encode an array to append to a URL.
     *
     * @return string
     */
    public function encodeArrayForUrl($array)
    {
        return ClickthroughHelper::encodeArrayForUrl((array) $array);
    }

    /**
     * Decode a string appended to URL into an array.
     *
     * @param bool $urlDecode
     *
     * @return mixed
     */
    public function decodeArrayFromUrl($string, $urlDecode = true)
    {
        return ClickthroughHelper::decodeArrayFromUrl($string, $urlDecode);
    }

    /**
     * @param array $routeParams
     * @param bool  $absolute
     * @param array $clickthrough
     *
     * @return string
     */
    public function buildUrl($route, $routeParams = [], $absolute = true, $clickthrough = [])
    {
        $referenceType = ($absolute) ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH;
        $url           = $this->router->generate($route, $routeParams, $referenceType);

        return $url.((!empty($clickthrough)) ? '?ct='.$this->encodeArrayForUrl($clickthrough) : '');
    }

    /**
     * Retrieve entity based on id/alias slugs.
     *
     * @param string $slug
     *
     * @return object|bool
     */
    public function getEntityBySlugs($slug)
    {
        $slugs    = explode('/', $slug);
        $idSlug   = '';
        $category = null;
        $lang     = null;

        $slugCount = count($slugs);
        $locales   = Locales::getNames();

        switch (true) {
            case 3 === $slugCount:
                [$lang, $category, $idSlug] = $slugs;

                break;

            case 2 === $slugCount:
                [$category, $idSlug] = $slugs;

                // Check if the first slug is actually a locale
                if (isset($locales[$category])) {
                    $lang     = $category;
                    $category = null;
                }

                break;

            case 1 === $slugCount:
                $idSlug = $slugs[0];

                break;
        }

        // Check for uncategorized
        if ($this->translator->trans('mautic.core.url.uncategorized') == $category) {
            $category = null;
        }

        if ($lang && !isset($locales[$lang])) {
            // Language doesn't exist so return false

            return false;
        }

        $entity = false;
        if (str_contains($idSlug, ':')) {
            $parts = explode(':', $idSlug);
            if (2 == count($parts)) {
                $entity = $this->getEntity($parts[0]);
            }
        } else {
            $entity = $this->getRepository()->findOneBySlugs($idSlug, $category, $lang);
        }

        if ($entity && $lang) {
            // Set the slug used to fetch the entity
            $entity->languageSlug = $lang;
        }

        return $entity;
    }

    /**
     * @param string      $alias
     * @param string|null $categoryAlias
     * @param string|null $lang
     *
     * @return object|null
     */
    public function getEntityByAlias($alias, $categoryAlias = null, $lang = null)
    {
        return null;
    }

    /**
     * @phpstan-param class-string<T> $class
     *
     * @return CommonRepository<T>
     */
    protected function getServiceRepository(string $class)
    {
        return $this->em->getRepository($class);
    }
}
