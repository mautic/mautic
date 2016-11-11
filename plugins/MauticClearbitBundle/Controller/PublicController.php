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
     */
    public function callbackAction()
    {
        if (!$this->request->request->has('result') || !$this->request->request->has('webhookId')) {
            return new Response('ERROR');
        }

        $data = $this->request->request->get('result', [], true);
        $id = $this->request->request->get('webhookId', [], true);
        $id = substr($id, strlen('clearbit#'));
        $result = json_decode($data, true);

        $org = null;
        if (array_key_exists('organizations', $result)) {
            foreach ($result['organizations'] as $organization) {
                if ($organization['isPrimary']) {
                    $org = $organization;
                    break;
                }
            }

            if (null === $org && 0 !== count($result['organizations'])) {
                // primary not found, use the first one if exists
                $org = $result['organizations'][0];
            }
        }

        $loc = null;
        if (array_key_exists('demographics', $result) && array_key_exists('locationDeduced', $result['demographics'])) {
            $loc = $result['demographics']['locationDeduced'];
        }

        $social = [];
        $socialProfiles = [];
        if (array_key_exists('socialProfiles', $result)) {
            $socialProfiles = $result['socialProfiles'];
        }
        foreach (['facebook', 'foursquare', 'googleplus', 'instagram', 'linkedin', 'twitter'] as $p) {
            foreach ($socialProfiles as $socialProfile) {
                if (array_key_exists('type', $socialProfile) && $socialProfile['type'] === $p) {
                    $social[$p] = (array_key_exists('url', $socialProfile)) ? $socialProfile['url'] : '';
                    break;
                }
            }
        }

        $data = [];

        if (array_key_exists('contactInfo', $result)) {
            $data = [
                'lastname' => array_key_exists(
                    'familyName',
                    $result['contactInfo']
                ) ? $result['contactInfo']['familyName'] : '',
                'firstname' => array_key_exists(
                    'givenName',
                    $result['contactInfo']
                ) ? $result['contactInfo']['givenName'] : '',
                'website' => (array_key_exists('websites', $result['contactInfo']) && count(
                        $result['contactInfo']['websites']
                    )) ? $result['contactInfo']['websites'][0]['url'] : '',
                'skype' => (array_key_exists('chats', $result['contactInfo']) && array_key_exists(
                        'skype',
                        $result['contactInfo']['chats']
                    )) ? $result['contactInfo']['chats']['skype']['handle'] : '',
            ];
        }
        $data = array_merge(
            $data,
            [
                'company' => (null !== $org) ? $org['name'] : '',
                'position' => (null !== $org) ? $org['title'] : '',
                'city' => (null !== $loc && array_key_exists('city', $loc) && array_key_exists(
                        'name',
                        $loc['city']
                    )) ? $loc['city']['name'] : '',
                'state' => (null !== $loc && array_key_exists('state', $loc) && array_key_exists(
                        'name',
                        $loc['state']
                    )) ? $loc['state']['name'] : '',
                'country' => (null !== $loc && array_key_exists('country', $loc) && array_key_exists(
                        'name',
                        $loc['country']
                    )) ? $loc['country']['name'] : '',
            ]
        );

        $data = array_merge($data, $social);

        /** @var \Mautic\LeadBundle\Model\LeadModel $model */
        $model = $this->getModel('lead');
        /** @var Lead $lead */
        $lead = $model->getEntity($id);
        $model->setFieldValues($lead, $data);
        $model->saveEntity($lead);

        return new Response('OK');
    }

    /**
     *
     * @return Response
     */
    public function compcallbackAction()
    {
        if (!$this->request->request->has('result') || !$this->request->request->has('webhookId')) {
            return new Response('ERROR');
        }

        $result = $this->request->request->get('result', [], true);
        $id = $this->request->request->get('webhookId', [], true);
        $id = substr($id, strlen('clearbitcomp#'));

        $org = [];
        $loc = [];
        $phone = [];
        $fax = [];
        $email = [];
        if (array_key_exists('organization', $result)) {
            $org = $result['organization'];
            if (array_key_exists('contactInfo', $result['organization'])) {
                if (array_key_exists('addresses', $result['organization']['contactInfo']) && count(
                        $result['organization']['contactInfo']['addresses']
                    )
                ) {
                    $loc = $result['organization']['contactInfo']['addresses'][0];
                }
                if (array_key_exists('emailAddresses', $result['organization']['contactInfo']) && count(
                        $result['organization']['contactInfo']['emailAddresses']
                    )
                ) {
                    $email = $result['organization']['contactInfo']['emailAddresses'][0];
                }
                if (array_key_exists('phoneNumbers', $result['organization']['contactInfo']) && count(
                        $result['organization']['contactInfo']['phoneNumbers']
                    )
                ) {
                    $phone = $result['organization']['contactInfo']['phoneNumbers'][0];
                    foreach ($result['organization']['contactInfo']['phoneNumbers'] as $phoneNumber) {
                        if (array_key_exists('label', $phoneNumber) && 0 >= strpos(
                                strtolower($phoneNumber['label']),
                                'fax'
                            )
                        ) {
                            $fax = $phoneNumber;
                        }
                    }
                }
            }
        }

        $data = [
            'companyaddress1' => array_key_exists('addressLine1', $loc) ? $loc['addressLine1'] : '',
            'companyaddress2' => array_key_exists('addressLine2', $loc) ? $loc['addressLine2'] : '',
            'companyemail' => array_key_exists('value', $email) ? $email['value'] : '',
            'companyphone' => array_key_exists('number', $phone) ? $phone['number'] : '',
            'companycity' => array_key_exists('locality', $loc) ? $loc['locality'] : '',
            'companyzipcode' => array_key_exists('postalCode', $loc) ? $loc['postalCode'] : '',
            'companystate' => array_key_exists('region', $loc) ? $loc['region']['name'] : '',
            'companycountry' => array_key_exists('country', $loc) ? $loc['country']['name'] : '',
            'companydescription' => array_key_exists('name', $org) ? $org['name'] : '',
            'companynumber_of_employees' => array_key_exists('approxEmployees', $org) ? $org['approxEmployees'] : '',
            'companyfax' => array_key_exists('number', $fax) ? $fax['number'] : '',
        ];

        /** @var \Mautic\LeadBundle\Model\CompanyModel $model */
        $model = $this->getModel('lead.company');
        /** @var Company $company */
        $company = $model->getEntity($id);
        $model->setFieldValues($company, $data);
        $model->saveEntity($company);

        return new Response('OK');
    }
}
