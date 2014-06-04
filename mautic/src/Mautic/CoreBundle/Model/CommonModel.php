<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\CoreBundle\Model;

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
    protected $repository;
    protected $factory;

    /**
     * @param $em
     * @param $security
     * @param $dispatcher
     * @param $translator
     * @param $factory
     */
    public function __construct($em, $security, $dispatcher, $translator, $factory)
    {
        $this->em         = $em;
        $this->security   = $security;
        $this->dispatcher = $dispatcher;
        $this->translator = $translator;
        $this->factory    = $factory;

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

    public function getCommandList()
    {
        return $this->em->getRepository($this->repository)->getSearchCommands();
    }
}