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

class ReportGenerator
{
    private string $validInterface = \Mautic\ReportBundle\Builder\ReportBuilderInterface::class;

    private ?string $contentTemplate = null;

    public function __construct(
        private EventDispatcherInterface $dispatcher,
        private Connection $db,
        private Report $entity,
        private ChannelListHelper $channelListHelper,
        private ?FormFactoryInterface $formFactory = null,
    ) {
    }

    /**
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
     * @param array $options Parameters set by the caller
     *
     * @return \Symfony\Component\Form\FormInterface<Report>
     */
    public function getForm(Report $entity, $options): \Symfony\Component\Form\FormInterface
    {
        return $this->formFactory->createBuilder(ReportType::class, $entity, $options)->getForm();
    }

    /**
     * Gets the getContentTemplate path.
     */
    public function getContentTemplate(): ?string
    {
        return $this->contentTemplate;
    }

    /**
     * @throws RuntimeException
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
