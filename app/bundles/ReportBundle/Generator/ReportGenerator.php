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
use Symfony\Component\Security\Core\SecurityContextInterface;
use Mautic\ReportBundle\Form\FormBuilder;

use Symfony\Component\DependencyInjection\Exception\RuntimeException;

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
     * @var \Mautic\ReportBundle\Form\FormBuilder
     */
    private $formBuilder;

    /**
     * @var string
     */
    private $validInterface;

    /**
     * Constructor
     *
     * @param \Doctrine\ORM\EntityManager                               $entityManager   Entity manager
     * @param \Symfony\Component\Security\Core\SecurityContextInterface $securityContext Security context
     * @param \Mautic\ReportBundle\Form\FormBuilder                     $formBuilder     Form builder
     *
     * @author r1pp3rj4ck <attila.bukor@gmail.com>
     */
    public function __construct(EntityManager $entityManager, SecurityContextInterface $securityContext, FormBuilder $formBuilder)
    {
        $this->entityManager   = $entityManager;
        $this->securityContext = $securityContext;
        $this->formBuilder     = $formBuilder;
        $this->validInterface  = "Mautic\\ReportBundle\\Builder\\ReportBuilderInterface";
    }

    /**
     * Gets query
     *
     * @param string $reportId Report ID
     *
     * @return mixed
     *
     * @author r1pp3rj4ck <attila.bukor@gmail.com>
     */
    public function getQuery($reportId)
    {
        $builder = $this->getBuilder($reportId);
        return $builder->getQuery();
    }

    /**
     * Gets form
     *
     * @param string $reportId Report ID
     *
     * @return \Symfony\Component\Form\Form
     *
     * @author r1pp3rj4ck <attila.bukor@gmail.com>
     */
    public function getForm($reportId)
    {
        $builder    = $this->getBuilder($reportId);
        $parameters = $builder->getParameters();

        return $this->formBuilder->getForm($parameters);
    }

    /**
     * Gets modifiers
     *
     * @param string $reportId Report ID
     *
     * @return array
     *
     * @author r1pp3rj4ck <attila.bukor@gmail.com>
     */
    public function getModifiers($reportId)
    {
        $builder = $this->getBuilder($reportId);

        return $builder->getModifiers();
    }

    /**
     * Gets report builder
     *
     * @param string $reportId Report ID
     *
     * @return object
     * @throws \Symfony\Component\DependencyInjection\Exception\RuntimeException
     *
     * @author r1pp3rj4ck <attila.bukor@gmail.com>
     */
    protected function getBuilder($reportId)
    {
        $className  = '\\Mautic\\ReportBundle\\Report\\' . $reportId . 'Report';
        $reflection = new \ReflectionClass($className);
        if ($reflection->implementsInterface($this->validInterface)) {
            $builder = $reflection->newInstanceArgs(array($this->entityManager->createQueryBuilder(), $this->securityContext));
        }
        else {
            throw new RuntimeException(sprintf("ReportBuilders have to implement %s, and %s doesn't implement it", $this->validInterface, $className));
        }

        return $builder;
    }
}
