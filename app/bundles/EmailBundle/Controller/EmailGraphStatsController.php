<?php

namespace Mautic\EmailBundle\Controller;

use Mautic\CoreBundle\Form\Type\DateRangeType;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\EmailBundle\Model\EmailModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class EmailGraphStatsController extends AbstractController
{
    /**
     * Loads a specific form into the detailed panel.
     *
     * @param int    $objectId
     * @param bool   $isVariant
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @throws \Exception
     */
    #[Route('/s/emails-graph-stats/{objectId}/{isVariant}/{dateFrom}/{dateTo}', name: 'mautic_email_graph_stats', requirements: ['objectId' => '[a-zA-Z0-9_-]+'], defaults: ['objectId' => 0], priority: -664)]
    public function viewAction(
        Request $request,
        EmailModel $model,
        CorePermissions $security,
        $objectId,
        $isVariant,
        $dateFrom = null,
        $dateTo = null,
    ): Response {
        /** @var \Mautic\EmailBundle\Entity\Email $email */
        $email = $model->getEntity($objectId);

        // Init the date range filter form
        $dateRangeValues = ['date_from' => $dateFrom, 'date_to' => $dateTo];
        $action          = $this->generateUrl('mautic_email_action', ['objectAction' => 'view', 'objectId' => $objectId]);
        $dateRangeForm   = $this->createForm(DateRangeType::class, $dateRangeValues, ['action' => $action]);

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
        if ($chartStatsSource = $request->query->get('stats')) {
            $includeVariants = ('all' === $chartStatsSource);
        } else {
            $includeVariants = (($email->isVariant() && $parent === $email) || ($email->isTranslation() && $translationParent === $email));
        }

        $dateFromObject = new \DateTime($dateFrom);
        $dateToObject   = new \DateTime($dateTo);

        if ('template' === $email->getEmailType()) {
            $stats = $model->getEmailGeneralStats(
                $email,
                $includeVariants,
                null,
                $dateFromObject,
                $dateToObject
            );
        } else {
            $stats = $model->getEmailListStats(
                $email,
                $includeVariants,
                $dateFromObject,
                $dateToObject
            );
        }

        $statsDevices = $model->getEmailDeviceStats(
            $email,
            $includeVariants,
            $dateFromObject,
            $dateToObject
        );

        return $this->render(
            '@MauticEmail/Email/graph.html.twig',
            [
                'email'         => $email,
                'stats'         => $stats,
                'statsDevices'  => $statsDevices,
                'showAllStats'  => $includeVariants,
                'dateRangeForm' => $dateRangeForm->createView(),
                'isVariant'     => $isVariant,
            ]
        );
    }
}
