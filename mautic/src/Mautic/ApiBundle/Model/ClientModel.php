<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Model;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManager;

/**
 * Class ClientModel
 * {@inheritdoc}
 * @package Mautic\CoreBundle\Model
 */
class ClientModel
{
    /**
     * @var string
     */
    protected $repository = 'MauticApiBundle:Client';

    /**
     * @var
     */
    protected $container;

    /**
     * @var
     */
    protected $request;

    /**
     * @var
     */
    protected $em;

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
    }

    public function deleteEntity($clientId)
    {
        try {
            $entity = $this->em->getRepository($this->repository)->find($clientId);
            return ($this->em->getRepository($this->repository)->deleteEntity($entity)) ? $entity : 0;
        } catch (\Exception $e) {
            //@TODO return error message
            return 0;
        }
    }
}