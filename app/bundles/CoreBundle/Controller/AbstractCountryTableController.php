<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Controller;

use Mautic\CoreBundle\Model\MapModelInterface;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @template S of object
 */
abstract class AbstractCountryTableController extends AbstractController
{
    public const MAP_OPTIONS = [];

    public const LEGEND_TEXT = 'Total: %total (%withCountry with country)';

    /**
     * @var MapModelInterface<S>
     */
    protected MapModelInterface $model;

    /**
     * @template T
     *
     * @param T $entity
     *
     * @return array<string, array<int, array<string, int|string>>>
     */
    abstract public function getData($entity, \DateTime $dateFromObject, \DateTime $dateToObject): array;

    /**
     * @template T
     *
     * @param T $entity
     */
    abstract public function hasAccess(CorePermissions $security, $entity): bool;

    abstract public function getMapOptionsTitle(): string;

    /**
     * @template T
     *
     * @param T $entity
     *
     * @return array<string,array<string, string>>
     */
    abstract public function getMapOptions($entity): array;

    /**
     * @throws \Exception
     */
    public function viewAction(
        CorePermissions $security,
        int $objectId,
        string $dateFrom = '',
        string $dateTo = ''
    ): Response {
        $entity = $this->model->getEntity($objectId);

        if (empty($entity) || !$this->hasAccess($security, $entity)) {
            throw new AccessDeniedHttpException();
        }

        $statsCountries = $this->getData($entity, new \DateTime($dateFrom), new \DateTime($dateTo));
        // $mapData        = TableHelper::buildMapData($statsCountries, $this->getMapOptions($entity), self::LEGEND_TEXT);

        return $this->render(
            '@MauticCore/Helper/geotable.html.twig',
            [
                'data'           => 'test',
                'optionsEnabled' => true,
                'optionsTitle'   => $this->getMapOptionsTitle(),
                'legendEnabled'  => true,
            ]
        );
    }
}
