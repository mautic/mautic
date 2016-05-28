<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Model;

use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Helper\UrlHelper;
use Symfony\Component\Intl\Intl;

/**
 * Class CommonModel
 *
 * @package Mautic\CoreBundle\Model
 */
class CommonModel
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
     * @var \Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher
     */
    protected $dispatcher;

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Translation\Translator
     */
    protected $translator;

    /**
     * @var MauticFactory
     */
    protected $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->em         = $factory->getEntityManager();
        $this->security   = $factory->getSecurity();
        $this->dispatcher = $factory->getDispatcher();
        $this->translator = $factory->getTranslator();
        $this->factory    = $factory;
    }

    /**
     * Retrieve the supported search commands for a repository
     *
     * @return array
     */
    public function getSupportedSearchCommands()
    {
        return array();
    }

    /**
     * Retrieve the search command list for a repository
     *
     * @return array
     */
    public function getCommandList()
    {
        $repo = $this->getRepository();

        return ($repo instanceof CommonRepository) ? $repo->getSearchCommands() : array();
    }

    /**
     * Retrieve the repository for an entity
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
     * Retrieve the permissions base
     *
     * @return string
     */
    public function getPermissionBase()
    {
        return '';
    }

    /**
     * Return a list of entities
     *
     * @param array $args [start, limit, filter, orderBy, orderByDir]
     *
     * @return \Doctrine\ORM\Tools\Pagination\Paginator|array
     */
    public function getEntities(array $args = array())
    {
        //set the translator
        $repo = $this->getRepository();

        if ($repo instanceof CommonRepository) {
            $repo->setTranslator($this->translator);
            $repo->setCurrentUser(
                $this->factory->getUser()
            );

            return $repo->getEntities($args);
        }

        return array();
    }

    /**
     * Get a specific entity
     *
     * @param $id
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

            return $repo->find($id);
        }

        return null;
    }

    /**
     * Encode an array to append to a URL
     *
     * @param $array
     *
     * @return string
     */
    public function encodeArrayForUrl($array)
    {
        return urlencode(base64_encode(serialize($array)));
    }

    /**
     * Decode a string appended to URL into an array
     *
     * @param $string
     *
     * @return mixed
     */
    public function decodeArrayFromUrl($string)
    {
        return unserialize(base64_decode(urldecode($string)));
    }

    /**
     * @param       $route
     * @param array $routeParams
     * @param bool  $absolute
     * @param array $clickthrough
     * @param bool  $shortenUrl
     *
     * @return string
     */
    public function buildUrl($route, $routeParams = array(), $absolute = true, $clickthrough = array(), $shortenUrl = false)
    {
        $url  = $this->factory->getRouter()->generate($route, $routeParams, $absolute);
        $url .= (!empty($clickthrough)) ? '?ct=' . $this->encodeArrayForUrl($clickthrough) : '';

        if ($shortenUrl) {
            /** @var UrlHelper $urlHelper */
            $urlHelper = $this->factory->getHelper('url');

            return $urlHelper->buildShortUrl($url);
        }

        return $url;
    }

    /**
     * Retrieve entity based on id/alias slugs
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
            case ($slugCount === 3):
                list($lang, $category, $idSlug) = $slugs;

                break;

            case ($slugCount === 2):
                list($category, $idSlug) = $slugs;

                // Check if the first slug is actually a locale
                if (isset($locales[$category])) {
                    $lang     = $category;
                    $category = null;
                }

                break;

            case ($slugCount === 1):
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

        if (strpos($idSlug, ':') !== false) {
            $parts = explode(':', $idSlug);
            if (count($parts) == 2) {
                $entity = $this->getEntity($parts[0]);

                if (!empty($entity)) {

                    return $entity;
                }
            }
        } else {
            $entity = $this->getRepository()->findOneBySlugs($idSlug, $category, $lang);

            if (!empty($entity)) {

                return $entity;
            }
        }

        return false;
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
