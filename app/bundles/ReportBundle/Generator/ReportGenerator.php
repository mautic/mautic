<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 *
 * This file was originally distributed as part of VelvelReportBundle (C) 2012 Velvel IT Solutions
 * and distributed under the GNU Lesser General Public License version 3.
 */

namespace Mautic\ReportBundle\Generator;

use Doctrine\ORM\EntityManager;
use Mautic\ReportBundle\Entity\Report;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * Report generator
 *
 * @author r1pp3rj4ck <attila.bukor@gmail.com>
 */
class ReportGenerator
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * @var \Symfony\Component\Security\Core\SecurityContextInterface
     */
    private $securityContext;

    /**
     * @var \Symfony\Component\Form\FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var \Mautic\ReportBundle\Entity\Report
     */
    private $entity;

    /**
     * @var string
     */
    private $validInterface = "Mautic\\ReportBundle\\Builder\\ReportBuilderInterface";

    /**
     * Constructor
     *
     * @param \Doctrine\ORM\EntityManager                               $entityManager   Entity manager
     * @param \Symfony\Component\Security\Core\SecurityContextInterface $securityContext Security context
     * @param \Symfony\Component\Form\FormFactoryInterface              $formFactory     Form factory
     * @param \Mautic\ReportBundle\Entity\Report                        $entity          Report entity
     *
     * @author r1pp3rj4ck <attila.bukor@gmail.com>
     */
    public function __construct(EntityManager $entityManager, SecurityContextInterface $securityContext, FormFactoryInterface $formFactory, Report $entity)
    {
        $this->entityManager   = $entityManager;
        $this->securityContext = $securityContext;
        $this->formFactory     = $formFactory;
        $this->entity          = $entity;
    }

    /**
     * Gets query
     *
     * @param array $options Optional options array for the query
     *
     * @return \Doctrine\ORM\Query
     *
     * @author r1pp3rj4ck <attila.bukor@gmail.com>
     */
    public function getQuery(array $options = array())
    {
        $builder = $this->getBuilder();

        return $builder->getQuery($options);
    }

    /**
     * Gets form
     *
     * @param \Mautic\ReportBundle\Entity\Report $entity  Report Entity
     * @param array                              $options Parameters set by the caller
     *
     * @return \Symfony\Component\Form\Form
     *
     * @author r1pp3rj4ck <attila.bukor@gmail.com>
     */
    public function getForm(Report $entity, $options)
    {
        return $this->formFactory->createBuilder('report', $entity, $options)->getForm();
    }

    /**
     * Gets report builder
     *
     * @return \Mautic\ReportBundle\Builder\ReportBuilderInterface
     * @throws \Symfony\Component\DependencyInjection\Exception\RuntimeException
     *
     * @author r1pp3rj4ck <attila.bukor@gmail.com>
     */
    protected function getBuilder()
    {
        $className  = '\\Mautic\\ReportBundle\\Builder\\MauticReportBuilder';

        if (!class_exists($className)) {
            throw new RuntimeException("The MauticReportBuilder does not exist.");
        }

        $reflection = new \ReflectionClass($className);
        if ($reflection->implementsInterface($this->validInterface)) {
            $builder = $reflection->newInstanceArgs(array($this->entityManager, $this->securityContext, $this->entity));
        }
        else {
            throw new RuntimeException(sprintf("ReportBuilders have to implement %s, and %s doesn't implement it", $this->validInterface, $className));
        }

        return $builder;
    }
}
