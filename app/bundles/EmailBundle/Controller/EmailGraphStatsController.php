<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\CoreBundle\Controller\BuilderControllerTrait;
use Mautic\CoreBundle\Controller\FormErrorMessagesTrait;
use Mautic\LeadBundle\Controller\EntityContactsTrait;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class EmailGraphStatsController extends Controller
{
    use BuilderControllerTrait;
    use FormErrorMessagesTrait;
    use EntityContactsTrait;

    /**
     * Loads a specific form into the detailed panel.
     *
     * @param int  $objectId
     * @param bool isVariant
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function viewAction(Request $request, $objectId, $isVariant = false)
    {
        /** @var \Mautic\EmailBundle\Model\EmailModel $model */
        $model    = $this->get('mautic.email.model.email');
        $security = $this->get('mautic.security');

        /** @var \Mautic\EmailBundle\Entity\Email $email */
        $email = $model->getEntity($objectId);

        // Init the date range filter form
        $dateRangeValues = $request->get('daterange', []);
        $action          = $this->generateUrl('mautic_email_action', ['objectAction' => 'view', 'objectId' => $objectId]);
        $dateRangeForm   = $this->get('form.factory')->create('daterange', $dateRangeValues, ['action' => $action]);

        if ($email === null) {
            return $this->accessDenied();
        } elseif (!$this->get('mautic.security')->hasEntityAccess(
            'email:emails:viewown',
            'email:emails:viewother',
            $email->getCreatedBy()
        )
        ) {
            return $this->accessDenied();
        }

        //get A/B test information
        list($parent, $children) = $email->getVariants();
        $properties              = [];
        $variantError            = false;
        $weight                  = 0;
        if (count($children)) {
            foreach ($children as $c) {
                $variantSettings = $c->getVariantSettings();

                if (is_array($variantSettings) && isset($variantSettings['winnerCriteria'])) {
                    if ($c->isPublished()) {
                        if (!isset($lastCriteria)) {
                            $lastCriteria = $variantSettings['winnerCriteria'];
                        }

                        //make sure all the variants are configured with the same criteria
                        if ($lastCriteria != $variantSettings['winnerCriteria']) {
                            $variantError = true;
                        }

                        $weight += $variantSettings['weight'];
                    }
                } else {
                    $variantSettings['winnerCriteria'] = '';
                    $variantSettings['weight']         = 0;
                }

                $properties[$c->getId()] = $variantSettings;
            }

            $properties[$parent->getId()]['weight']         = 100 - $weight;
            $properties[$parent->getId()]['winnerCriteria'] = '';
        }

        //get related translations
        list($translationParent, $translationChildren) = $email->getTranslations();

        // Prepare stats for bargraph
        if ($chartStatsSource = $request->query->get('stats', false)) {
            $includeVariants = ('all' === $chartStatsSource);
        } else {
            $includeVariants = (($email->isVariant() && $parent === $email) || ($email->isTranslation() && $translationParent === $email));
        }

        if ($email->getEmailType() === 'template') {
            $stats = $model->getEmailGeneralStats(
                $email,
                $includeVariants,
                null,
                new \DateTime($dateRangeForm->get('date_from')->getData()),
                new \DateTime($dateRangeForm->get('date_to')->getData())
            );
        } else {
            $stats = $model->getEmailListStats(
                $email,
                $includeVariants,
                new \DateTime($dateRangeForm->get('date_from')->getData()),
                new \DateTime($dateRangeForm->get('date_to')->getData())
            );
        }

        $statsDevices = $model->getEmailDeviceStats(
            $email,
            $includeVariants,
            new \DateTime($dateRangeForm->get('date_from')->getData()),
            new \DateTime($dateRangeForm->get('date_to')->getData())
        );

        return $this->render(
            'MauticEmailBundle:Email:graph.html.php',
            [
                'email'        => $email,
                'stats'        => $stats,
                'statsDevices' => $statsDevices,
                'showAllStats' => $includeVariants,
                'dateRangeForm' => $dateRangeForm->createView(),
                'isVariant'     => $isVariant,
            ]
        );
        /*
        return $this->delegateView(
            [
                'returnUrl' => $this->generateUrl(
                    'mautic_email_action',
                    [
                        'objectAction' => 'view',
                        'objectId'     => $email->getId(),
                    ]
                ),
                'viewParameters' => [
                    'email'        => $email,
                    'stats'        => $stats,
                    'statsDevices' => $statsDevices,
                    'showAllStats' => $includeVariants,
                    'dateRangeForm' => $dateRangeForm->createView(),
                    'isVariant'     => $isVariant,
                ],
                'contentTemplate' => 'MauticEmailBundle:Email:graph.html.php',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_email_index',
                    'mauticContent' => 'email',
                ],
            ]
        );
        */
    }
}
