<?php
/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticClearbitBundle\Controller;

use Mautic\FormBundle\Controller\FormController;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\HttpFoundation\Response;

class PublicController extends FormController
{

    /**
     *
     * @return Response
     * @throws \InvalidArgumentException
     */
    public function callbackAction()
    {
        if (!$this->request->request->has('body') || !$this->request->request->has('id') ||
            !$this->request->request->has('type') || !$this->request->request->has('status') ||
            200 !== $this->request->request->get('status')
        ) {
            return new Response('ERROR');
        }

        /** @var array $result */
        $result = $this->request->request->get('body', [], true);
        $id = $this->request->request->get('id', [], true);

        $logger = $this->get('monolog.logger.mautic');

        try {

            if ('person' === $this->request->request->get('type', [], true)) {
                $id = substr($id, strlen('clearbit#'));

                $loc = [];
                if (array_key_exists('geo', $result)) {
                    $loc = $result['geo'];
                }

                $social = [];
                foreach ([
                             'facebook' => 'http://www.facebook.com/',
                             'googleplus' => 'http://plus.google.com/',
                             'linkedin' => 'http://www.linkedin.com/',
                             'twitter' => 'http://www.twitter.com/',
                         ] as $p => $u) {
                    foreach ($result as $type => $socialProfile) {
                        if ($type === $p) {
                            $social[$p] = (array_key_exists('handle', $socialProfile) && $socialProfile['handle']) ? $u.$socialProfile['handle'] : '';
                            break;
                        }
                    }
                }

                $data = array_merge(
                    $social,
                    [
                        'lastname' => (array_key_exists('name', $result) && array_key_exists(
                            'familyName',
                            $result['name']
                        )) ? $result['name']['familyName'] : '',
                        'firstname' => (array_key_exists('name', $result) && array_key_exists(
                            'givenName',
                            $result['name']
                        )) ? $result['name']['givenName'] : '',
                        'website' => array_key_exists('site', $result) ? $result['site'] : '',
                        'company' => (array_key_exists('employment', $result) && array_key_exists(
                                'name',
                                $result['employment']
                            )) ? $result['employment']['name'] : '',
                        'position' => (array_key_exists('employment', $result) && array_key_exists(
                                'title',
                                $result['employment']
                            )) ? $result['employment']['title'] : '',
                        'city' => array_key_exists('city', $loc) ? $loc['city'] : '',
                        'state' => array_key_exists('state', $loc)? $loc['state'] : '',
                        'country' => array_key_exists('country', $loc) ? $loc['country'] : '',
                    ]
                );

                /** @var \Mautic\LeadBundle\Model\LeadModel $model */
                $model = $this->getModel('lead');
                /** @var Lead $lead */
                $lead = $model->getEntity($id);
                $model->setFieldValues($lead, $data);
                $model->saveEntity($lead);

            } else {

                /******************  COMPANY STUFF  *********************/

                if ('company' === $this->request->request->get('type', [], true)) {
                    $id = substr($id, strlen('clearbitcomp#'));
                    $loc = [];
                    if (array_key_exists('geo', $result)) {
                        $loc = $result['geo'];
                    }

                    $data = [
                        'companyaddress1' => (array_key_exists('streetNumber', $loc) && array_key_exists(
                                'streetName',
                                $loc
                            )) ? $loc['streetNumber'].' '.$loc['streetName'] : '',
                        'companycity' => array_key_exists('city', $loc) ? $loc['city'] : '',
                        'companystate' => array_key_exists('state', $loc) ? $loc['state'] : '',
                        'companyzipcode' => array_key_exists('postalCode', $loc) ? $loc['postalCode'] : '',
                        'companycountry' => array_key_exists('country', $loc) ? $loc['country'] : '',
                        'companyemail' => (array_key_exists('site', $result) && array_key_exists(
                                'emailAddresses',
                                $result['site']
                            ) && count($result['site']['emailAddresses'])) ? $result['site']['emailAddresses'][0] : '',
                        'companyphone' => array_key_exists('phone', $result) ? $result['phone'] : '',
                        'companydescription' => array_key_exists('description', $result) ? $result['description'] : '',
                        'companynumber_of_employees' =>
                            (array_key_exists('metrics', $result) && array_key_exists(
                                    'employees',
                                    $result['metrics']
                                )) ? $result['metrics']['employees'] : '',
                    ];

                    /** @var \Mautic\LeadBundle\Model\CompanyModel $model */
                    $model = $this->getModel('lead.company');
                    /** @var Company $company */
                    $company = $model->getEntity($id);
                    $model->setFieldValues($company, $data);
                    $model->saveEntity($company);
                }
            }

        } catch (\Exception $ex) {
            $logger->log('error', 'ERROR on Clearbit callback: '.$ex->getMessage());
        }

        return new Response('OK');
    }

}
