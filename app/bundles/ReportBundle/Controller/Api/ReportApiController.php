<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Controller\Api;

use DateTimeImmutable;
use DateTimeZone;
use FOS\RestBundle\Util\Codes;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ReportBundle\Entity\Report;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class ReportApiController extends CommonApiController
{
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * {@inheritdoc}
     */
    public function initialize(FilterControllerEvent $event)
    {
        $this->model            = $this->getModel('report');
        $this->entityClass      = Report::class;
        $this->entityNameOne    = 'report';
        $this->entityNameMulti  = 'reports';
        $this->serializerGroups = ['reportList', 'reportDetails'];
        $this->formFactory      = $this->container->get('form.factory');

        parent::initialize($event);
    }

    /**
     * Obtains a compiled report.
     *
     * @param int $id Report ID
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getReportAction($id)
    {
        $entity = $this->model->getEntity($id);

        if (!$entity instanceof $this->entityClass) {
            return $this->notFound();
        }

        $options = [
            'paginate'        => true,
            'ignoreGraphData' => true,
            'dateFrom'        => new DateTimeImmutable($this->request->query->get('dateFrom', '-30 days'), new DateTimeZone('UTC')),
            'dateTo'          => new DateTimeImmutable($this->request->query->get('dateTo', null), new DateTimeZone('UTC')),
            'page'            => $this->request->query->getInt('page', 1),
            'limit'           => $this->request->query->getInt('limit', 10),
        ];

        $reportData = $this->model->getReportData($entity, $this->formFactory, $options);

        // Unset keys that we don't need to send back
        foreach (['graphs', 'contentTemplate', 'columns'] as $key) {
            unset($reportData[$key]);
        }

        $view = $this->view($reportData, Codes::HTTP_OK);

        return $this->handleView($view);
    }
}
