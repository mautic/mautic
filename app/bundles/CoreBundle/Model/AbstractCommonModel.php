<?php

namespace Mautic\CoreBundle\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\ClickthroughHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Intl\Locales;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;

abstract class AbstractCommonModel
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var \Mautic\CoreBundle\Security\Permissions\CorePermissions
     */
    protected $security;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var UserHelper
     */
    protected $userHelper;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var CoreParametersHelper
     */
    protected $coreParametersHelper;

    public function setEntityManager(EntityManager $em)
    {
        $this->em = $em;
    }

    public function setSecurity(CorePermissions $security)
    {
        $this->security = $security;
    }

    public function setDispatcher(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function setRouter(Router $router)
    {
        $this->router = $router;
    }

    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Initialize the user parameter for use in locking procedures.
     */
    public function setUserHelper(UserHelper $userHelper)
    {
        $this->userHelper = $userHelper;
    }

    /**
     * Initialize the CoreParameters parameter.
     */
    public function setCoreParametersHelper(CoreParametersHelper $coreParametersHelper)
    {
        $this->coreParametersHelper = $coreParametersHelper;
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

        return ($repo instanceof CommonRepository) ? $repo->getSearchCommands() : [];
    }

    /**
     * Retrieve the repository for an entity.
     *
     * @return \Mautic\CoreBundle\Entity\CommonRepository|bool
     */
    public function getRepository()
    {
        static $commonRepo;

        if (null === $commonRepo) {
            $commonRepo = new CommonRepository($this->em, new ClassMetadata('MauticCoreBundle:FormEntity'));
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
        //set the translator
        $repo = $this->getRepository();

        if ($repo instanceof CommonRepository) {
            $repo->setTranslator($this->translator);
            $repo->setCurrentUser($this->userHelper->getUser());

            return $repo->getEntities($args);
        }

        return [];
    }

    /**
     * Get a specific entity.
     *
     * @param int|array id
     *
     * @return object|null
     */
    public function getEntity($id = null)
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
     * @param $array
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
     * @param      $string
     * @param bool $urlDecode
     *
     * @return mixed
     */
    public function decodeArrayFromUrl($string, $urlDecode = true)
    {
        return ClickthroughHelper::decodeArrayFromUrl($string, $urlDecode);
    }

    /**
     * @param       $route
     * @param array $routeParams
     * @param bool  $absolute
     * @param array $clickthrough
     * @param array $utmTags
     *
     * @return string
     */
    public function buildUrl($route, $routeParams = [], $absolute = true, $clickthrough = [], $utmTags = [])
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
                list($lang, $category, $idSlug) = $slugs;

                break;

            case 2 === $slugCount:
                list($category, $idSlug) = $slugs;

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
        if (false !== strpos($idSlug, ':')) {
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
    }
}
