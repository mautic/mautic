<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Controller;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Intl\Countries;

/**
 * @template S of object
 */
abstract class AbstractCountryMapController extends AbstractController
{
    protected const MAP_OPTIONS = [];

    protected const LEGEND_TEXT = 'Total: %total (%withCountry with country)';

    /**
     * @var S|null
     */
    protected $model = null;

    /**
     * @template T
     *
     * @param T $entity
     *
     * @return array<int, array<string, int|string>>
     */
    abstract public function getData($entity, \DateTime $dateFromObject, \DateTime $dateToObject): array;

    /**
     * @param array<string, int> $legendValues
     */
    protected static function getOptionLegendText(array $legendValues): string
    {
        return str_replace(array_keys($legendValues), array_values($legendValues), self::LEGEND_TEXT);
    }

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

        if (empty($entity) || !$security->hasEntityAccess(
            'email:emails:viewown',
            'email:emails:viewother',
            $entity->getCreatedBy()
        )) {
            throw new AccessDeniedHttpException();
        }

        $statsCountries = $this->getData($entity, new \DateTime($dateFrom), new \DateTime($dateTo));
        $mapData        = self::buildMapData($statsCountries);

        return $this->render(
            '@MauticCore/Helper/map.html.twig',
            [
                'data'           => $mapData[0]['data'],
                'height'         => 315,
                'optionsEnabled' => true,
                'optionsTitle'   => 'mautic.email.stats.options.title',
                'options'        => $mapData,
                'legendEnabled'  => true,
                'statUnit'       => $mapData[0]['unit'],
            ]
        );
    }

    /**
     * @param array<int, array<string, int|string>> $statsCountries
     *
     * @return array<int, array<string, mixed>>
     */
    protected static function buildMapData(array $statsCountries): array
    {
        foreach (static::MAP_OPTIONS as $key => $value) {
            $mappedData = empty($statsCountries) ? [] : self::mapCountries($statsCountries, $key);

            $result[] = [
                'data'       => $mappedData['data'] ?? [],
                'label'      => $value['label'],
                'legendText' => self::getOptionLegendText([
                    '%total'       => $mappedData['total'] ?? 0,
                    '%withCountry' => $mappedData['totalWithCountry'] ?? 0,
                ]),
                'unit'       => $value['unit'],
            ];
        }

        return $result ?? [];
    }

    /**
     * @param array<int, array<string, int|string>> $stats
     *
     * @return array<string, int|array<string, int>>
     */
    protected static function mapCountries(array $stats, string $countKey): array
    {
        $countries = array_flip(Countries::getNames('en'));
        $results   = [
            'data'             => [],
            'total'            => 0,
            'totalWithCountry' => 0,
        ];

        foreach ($stats as $s) {
            $countryName = $s['country'];
            $results['total'] += $s[$countKey];

            if (isset($countries[$countryName])) {
                $countryCode                   = $countries[$countryName];

                if (!empty($s[$countKey])) {
                    $results['data'][$countryCode] = (int) $s[$countKey];
                }

                $results['totalWithCountry'] += $s[$countKey];
            }
        }

        return $results;
    }
}
