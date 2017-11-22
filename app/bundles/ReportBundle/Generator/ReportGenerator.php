<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Generator;

use Doctrine\DBAL\Connection;
use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Form\Type\ReportType;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * Report generator.
 */
class ReportGenerator
{
    /**
     * @var Connection
     */
    private $db;

    /**
     * @var EventDispatcher
     */
    private $dispatcher;

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
    private $validInterface = 'Mautic\\ReportBundle\\Builder\\ReportBuilderInterface';

    /**
     * @var string
     */
    private $contentTemplate;

    /**
     * @var ChannelListHelper
     */
    private $channelListHelper;

    /**
     * ReportGenerator constructor.
     *
     * @param EventDispatcherInterface  $dispatcher
     * @param Connection                $db
     * @param Report                    $entity
     * @param ChannelListHelper         $channelListHelper
     * @param FormFactoryInterface|null $formFactory
     */
    public function __construct(EventDispatcherInterface $dispatcher, Connection $db, Report $entity, ChannelListHelper $channelListHelper, FormFactoryInterface $formFactory = null)
    {
        $this->db                = $db;
        $this->dispatcher        = $dispatcher;
        $this->formFactory       = $formFactory;
        $this->channelListHelper = $channelListHelper;
        $this->entity            = $entity;
    }

    /**
     * Gets query.
     *
     * @param array $options Optional options array for the query
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function getQuery(array $options = [])
    {
        $builder = $this->getBuilder();

        $query = $builder->getQuery($options);

        $this->contentTemplate = $builder->getContentTemplate();

        return $query;
    }

    /**
     * Gets form.
     *
     * @param Report $entity  Report Entity
     * @param array  $options Parameters set by the caller
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    public function getForm(Report $entity, $options)
    {
        return $this->formFactory->createBuilder(ReportType::class, $entity, $options)->getForm();
    }

    /**
     * Gets the getContentTemplate path.
     *
     * @return string
     */
    public function getContentTemplate()
    {
        return $this->contentTemplate;
    }

    /**
     * Gets report builder.
     *
     * @return \Mautic\ReportBundle\Builder\ReportBuilderInterface
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\RuntimeException
     */
    protected function getBuilder()
    {
        $className = '\\Mautic\\ReportBundle\\Builder\\MauticReportBuilder';

        if (!class_exists($className)) {
            throw new RuntimeException('The MauticReportBuilder does not exist.');
        }

        $reflection = new \ReflectionClass($className);

        if (!$reflection->implementsInterface($this->validInterface)) {
            throw new RuntimeException(
                sprintf("ReportBuilders have to implement %s, and %s doesn't implement it", $this->validInterface, $className)
            );
        }

        return $reflection->newInstanceArgs([$this->dispatcher, $this->db, $this->entity, $this->channelListHelper]);
    }
}
