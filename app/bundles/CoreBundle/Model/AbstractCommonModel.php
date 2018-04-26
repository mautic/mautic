<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Helper\ClickthroughHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class AbstractCommonModel.
 */
abstract class AbstractCommonModel
{
    /**
     * Do not use Factory in Models. There's a couple places where we
     * still need to in core, but we are working on refactoring. This
     * is completely temporary.
     *
     * @param MauticFactory $factory
     */
    public function setFactory(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @deprecated 2.0; to be removed in 3.0
     *
     * @var MauticFactory
     */
    protected $factory;

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

    /**
     * @param EntityManager $em
     */
    public function setEntityManager(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param CorePermissions $security
     */
    public function setSecurity(CorePermissions $security)
    {
        $this->security = $security;
    }

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function setDispatcher(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param Router $router
     */
    public function setRouter(Router $router)
    {
        $this->router = $router;
    }

    /**
     * @param TranslatorInterface $translator
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Initialize the user parameter for use in locking procedures.
     *
     * @param UserHelper $userHelper
     */
    public function setUserHelper(UserHelper $userHelper)
    {
        $this->userHelper = $userHelper;
    }

    /**
     * Initialize the CoreParameters parameter.
     *
     * @param CoreParametersHelper $coreParametersHelper
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

        if ($commonRepo === null) {
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
     * @return null|object
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
        $url = $this->router->generate($route, $routeParams, $absolute);
        $url .= (!empty($clickthrough)) ? '?ct='.$this->encodeArrayForUrl($clickthrough) : '';

        return $url;
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
        $locales   = Intl::getLocaleBundle()->getLocaleNames();

        switch (true) {
            case $slugCount === 3:
                list($lang, $category, $idSlug) = $slugs;

                break;

            case $slugCount === 2:
                list($category, $idSlug) = $slugs;

                // Check if the first slug is actually a locale
                if (isset($locales[$category])) {
                    $lang     = $category;
                    $category = null;
                }

                break;

            case $slugCount === 1:
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
        if (strpos($idSlug, ':') !== false) {
            $parts = explode(':', $idSlug);
            if (count($parts) == 2) {
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
     * @param $alias
     *
     * @return null|object
     */
    public function getEntityByAlias($alias, $categoryAlias = null, $lang = null)
    {
    }
}
