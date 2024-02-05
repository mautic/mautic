<?php

namespace Mautic\ReportBundle\Generator;

use Doctrine\DBAL\Connection;
use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\ReportBundle\Builder\MauticReportBuilder;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Form\Type\ReportType;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * Report generator.
 */
class ReportGenerator
{
    private string $validInterface = \Mautic\ReportBundle\Builder\ReportBuilderInterface::class;

    /**
     * @var string
     */
    private $contentTemplate;

    public function __construct(
        private EventDispatcherInterface $dispatcher,
        private Connection $db,
        private Report $entity,
        private ChannelListHelper $channelListHelper,
        private ?FormFactoryInterface $formFactory = null
    ) {
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
     * @return \Symfony\Component\Form\FormInterface<Report>
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
     * @throws \Symfony\Component\DependencyInjection\Exception\RuntimeException
     */
    protected function getBuilder(): MauticReportBuilder
    {
        $className = MauticReportBuilder::class;

        if (!class_exists($className)) {
            throw new RuntimeException('The MauticReportBuilder does not exist.');
        }

        $reflection = new \ReflectionClass($className);

        if (!$reflection->implementsInterface($this->validInterface)) {
            throw new RuntimeException(sprintf("ReportBuilders have to implement %s, and %s doesn't implement it", $this->validInterface, $className));
        }

        return $reflection->newInstanceArgs([$this->dispatcher, $this->db, $this->entity, $this->channelListHelper]);
    }
}
