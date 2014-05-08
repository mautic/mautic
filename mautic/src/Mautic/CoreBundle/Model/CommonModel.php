<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\CoreBundle\Model;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManager;

/**
 * Class CommonModel
 *
 * @package Mautic\CoreBundle\Model
 */
class CommonModel
{

    /**
     * @var \Symfony\Component\DependencyInjection\Container
     */
    protected $container;

    /**
     * @var null|\Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var string
     */
    protected $repository;

    /**
     * @param Container     $container
     * @param RequestStack  $request_stack
     * @param EntityManager $em
     */
    public function __construct(Container $container, RequestStack $request_stack, EntityManager $em)
    {
        $this->container = $container;
        $this->request   = $request_stack->getCurrentRequest();
        $this->em        = $em;

        $this->init();
    }

    /**
     * Used by child model classes to load required variables, etc
     */
    protected function init() { }

    /**
     * Set the repository required for the model
     *
     * @param $repository
     */
    protected function setRepository($repository)
    {
        $this->repository = $repository;
    }

    public function getSupportedSearchCommands()
    {
        return array();
    }
}