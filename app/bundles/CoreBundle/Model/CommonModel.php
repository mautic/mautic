<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Model;

use Mautic\CoreBundle\Factory\MauticFactory;

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
        return $this->getRepository()->getSearchCommands();
    }

    /**
     * Retrieve the repository for an entity
     *
     * @return \Mautic\CoreBundle\Entity\CommonRepository|bool
     */
    public function getRepository()
    {
        return false;
    }

    /**
     * Retrieve the permissions base
     *
     * @return string|bool
     */
    public function getPermissionBase()
    {
        return false;
    }

    /**
     * Return a list of entities
     *
     * @param array $args [start, limit, filter, orderBy, orderByDir]
     *
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function getEntities(array $args = array())
    {
        //set the translator
        $repo = $this->getRepository();
        $repo->setTranslator($this->translator);
        $repo->setCurrentUser(
            $this->factory->getUser()
        );

        return $repo->getEntities($args);
    }
}
