<?php

namespace Mautic\CoreBundle\Controller;

use Mautic\CoreBundle\Model\MauticModelInterface;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Intl\Countries;

abstract class AbstractMapController extends AbstractController
{
    protected MauticModelInterface $model;

    abstract protected function getEntity(int $objectId);

    abstract protected function getData($request, $entity, $dateFromObject, $dateToObject);

    /**
     * @throws \Exception
     */
    public function viewAction(
        Request $request,
        CorePermissions $security,
        int $objectId,
        string $dateFrom = '',
        string $dateTo = ''
    ): Response {
        $entity     = $this->getEntity($objectId);

        if (empty($entity) || !$security->hasEntityAccess(
            'email:emails:viewown',
            'email:emails:viewother',
            $entity->getCreatedBy()
        )) {
            throw new AccessDeniedHttpException();
        }

        $dateFromObject = new \DateTime($dateFrom);
        $dateToObject   = new \DateTime($dateTo);
        $legendText     = 'Total: %s (%s with country)';
        $results        = [];

        $statsCountries = $this->getData($request, $entity, $dateFromObject, $dateToObject);

        $results['read']    = empty($statsCountries) ? [] : $this->mapData($statsCountries, 'read_count');
        $results['clicked'] = empty($statsCountries) ? [] : $this->mapData($statsCountries, 'clicked_count');

        return $this->render(
            '@MauticCore/Helper/map.html.twig',
            [
                'data'           => $results['read']['data'],
                'height'         => 315,
                'optionsEnabled' => true,
                'optionsTitle'   => 'Choose stats:',
                'options'        => [
                    [
                        'data'       => $results['read']['data'],
                        'label'      => 'mautic.email.stat.read',
                        'legendText' => vsprintf($legendText, [$results['read']['total'] ?? 0, $results['read']['totalWithCountry'] ?? 0]),
                        'unit'       => 'Read',
                    ],
                    [
                        'data'       => $results['clicked']['data'],
                        'label'      => 'mautic.email.clicked',
                        'legendText' => vsprintf($legendText, [$results['clicked']['total'] ?? 0, $results['clicked']['totalWithCountry'] ?? 0]),
                        'unit'       => 'Click',
                    ],
                ],
                'legendEnabled' => true,
                'statUnit'      => 'Read',
                'results'       => $results,
            ]
        );
    }

    protected function mapData(array $stats, string $countKey): array
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
                    $results['data'][$countryCode] = $s[$countKey];
                }

                $results['totalWithCountry'] += $s[$countKey];
            }
        }

        return $results;
    }
}
