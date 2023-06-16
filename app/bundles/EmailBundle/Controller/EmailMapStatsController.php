<?php

namespace Mautic\EmailBundle\Controller;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\EmailBundle\Model\EmailModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Intl\Countries;

class EmailMapStatsController extends AbstractController
{
    /**
     * Loads a specific form into the detailed panel.
     *
     * @throws \Exception
     */
    public function viewAction(
        Request $request,
        EmailModel $model,
        CorePermissions $security,
        int $objectId,
        string $dateFrom = '',
        string $dateTo = ''
    ): Response {
        /** @var \Mautic\EmailBundle\Entity\Email $email */
        $email     = $model->getEntity($objectId);
        $results   = [];

        if (null === $email || !$security->hasEntityAccess(
            'email:emails:viewown',
            'email:emails:viewother',
            $email->getCreatedBy()
        )) {
            throw new AccessDeniedHttpException();
        }

        // get A/B test information
        [$parent, $children] = $email->getVariants();

        // get related translations
        [$translationParent, $translationChildren] = $email->getTranslations();

        // Prepare stats for bargraph
        if ($chartStatsSource = $request->query->get('stats', false)) {
            $includeVariants = ('all' === $chartStatsSource);
        } else {
            $includeVariants = (($email->isVariant() && $parent === $email) || ($email->isTranslation() && $translationParent === $email));
        }

        $dateFromObject = new \DateTime($dateFrom);
        $dateToObject   = new \DateTime($dateTo);

        if ('template' === $email->getEmailType()) {
            $statsCountries = [];
        } else {
            $statsCountries = $model->getEmailListCountryStats(
                $email,
                $includeVariants,
                $dateFromObject,
                $dateToObject
            );
        }

        $results['read']    = empty($statsCountries['read']) ? [] : $this->mapData($statsCountries['read'], 'count');
        $results['clicked'] = empty($statsCountries['clicked']) ? [] : $this->mapData($statsCountries['clicked'], 'click_count');

        return $this->render(
            '@MauticEmail/Email/map.html.twig',
            [
                'results' => $results,
                'height'  => 300,
            ]
        );
    }

    private function mapData(array $stats, string $countKey): array
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
                $results['data'][$countryCode] = $s[$countKey];
                $results['totalWithCountry'] += $s[$countKey];
            }
        }

        return $results;
    }
}
