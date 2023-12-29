<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Controller;

use Mautic\CoreBundle\Helper\ExportHelper;
use Mautic\CoreBundle\Model\TableModelInterface;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @template S of object
 */
abstract class AbstractCountryTableController extends AbstractController
{
    /**
     * @var TableModelInterface<S>
     */
    protected TableModelInterface $model;

    protected CorePermissions $security;

    protected ExportHelper $exportHelper;

    protected Translator $translator;

    /**
     * @template T
     *
     * @param T $entity
     *
     * @return array<int|string, array<int|string, int|string>>
     */
    abstract public function getData($entity): array;

    /**
     * @template T
     *
     * @param T $entity
     */
    abstract public function hasAccess($entity): bool;

    /**
     * @template T
     *
     * @param T $entity
     *
     * @return array<int, string>
     */
    abstract public function getExportHeader($entity): array;

    /**
     * @throws \Exception
     */
    public function viewAction(
        int $objectId
    ): Response {
        $entity = $this->model->getEntity($objectId);

        if (empty($entity) || !$this->hasAccess($entity)) {
            throw new AccessDeniedHttpException();
        }

        $statsCountries = $this->getData($entity);

        return $this->render(
            '@MauticCore/Helper/countries_table.html.twig',
            [
                'data'           => $statsCountries,
                'object'         => $entity,
            ]
        );
    }

    /**
     * @throws \Exception
     */
    public function exportAction(int $objectId, string $format = 'csv'): StreamedResponse|Response
    {
        $entity = $this->model->getEntity($objectId);

        if (empty($entity) || !$this->hasAccess($entity)) {
            throw new AccessDeniedHttpException();
        }

        $filename       = $this->model->getExportFilename($entity->getName()).'.'.$format;
        $headerRow      = $this->getExportHeader($entity);
        $statsCountries = $this->getData($entity);

        if (empty($statsCountries)) {
            throw new NotFoundHttpException();
        }

        $this->exportHelper->setHeaderRow($headerRow);

        return $this->exportHelper->exportDataAs(array_values($statsCountries), $format, $filename);
    }
}
