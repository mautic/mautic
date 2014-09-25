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

    protected $em;
    protected $security;
    protected $dispatcher;
    protected $translator;
    protected $factory;

    /**
     * @param $factory
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
     * @return array
     */
    public function getSupportedSearchCommands()
    {
        return array();
    }

    /**
     * @return mixed
     */
    public function getCommandList()
    {
        return $this->getRepository()->getSearchCommands();
    }

    /**
     * {@inheritdoc}
     *
     * @return \Mautic\CoreBundle\Entity\CommonRepository
     */
    public function getRepository()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getPermissionBase()
    {
        return false;
    }

    /**
     * Return list of entities
     *
     * @param array $args [start, limit, filter, orderBy, orderByDir]
     * @return mixed
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
