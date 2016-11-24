<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticFullContactBundle\Controller;

use Mautic\FormBundle\Controller\FormController;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\HttpFoundation\Response;

class PublicController extends FormController
{
    /**
     * Write a notification.
     *
     * @param string    $message   Message of the notification
     * @param string    $header    Header for message
     * @param string    $iconClass Font Awesome CSS class for the icon (e.g. fa-eye)
     * @param User|null $user      User object; defaults to current user
     */
    public function addNewNotification($message, $header, $iconClass, User $user)
    {
        /** @var \Mautic\CoreBundle\Model\NotificationModel $notificationModel */
        $notificationModel = $this->getModel('core.notification');
        $notificationModel->addNotification($message, 'FullContact', false, $header, $iconClass, null, $user);
    }

    /**
     * @return Response
     *
     * @throws \InvalidArgumentException
     */
    public function callbackAction()
    {
        if (!$this->request->request->has('result') || !$this->request->request->has('webhookId')) {
            return new Response('ERROR');
        }

        $data               = $this->request->request->get('result', [], true);
        $oid                = $this->request->request->get('webhookId', [], true);
        list($w, $id, $uid) = explode('#', $oid, 3);

        if (0 === strpos($w, 'fullcontactcomp')) {
            return $this->compcallbackAction();
        }

        $notify = false !== strpos($w, '_notify');
        /** @var array $result */
        $result = json_decode($data, true);

        try {
            /** @var \Mautic\LeadBundle\Model\LeadModel $model */
            $model = $this->getModel('lead');
            /** @var Lead $lead */
            $lead = $model->getEntity($id);
            $currFields = $lead->getFields(true);

            $org = null;
            if (array_key_exists('organizations', $result)) {
                /** @var array $organizations */
                $organizations = $result['organizations'];
                foreach ($organizations as $organization) {
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
            if (array_key_exists('demographics', $result) && array_key_exists(
                    'locationDeduced',
                    $result['demographics']
                )
            ) {
                $loc = $result['demographics']['locationDeduced'];
            }

            $data = [];
            /** @var array $socialProfiles */
            $socialProfiles = [];
            if (array_key_exists('socialProfiles', $result)) {
                $socialProfiles = $result['socialProfiles'];
            }
            foreach (['facebook', 'foursquare', 'googleplus', 'instagram', 'linkedin', 'twitter'] as $p) {
                foreach ($socialProfiles as $socialProfile) {
                    if (array_key_exists('type', $socialProfile) && $socialProfile['type'] === $p && empty($currFields[$p])) {
                        $data[$p] = array_key_exists('url', $socialProfile) ? $socialProfile['url'] : '';
                        break;
                    }
                }
            }

            if (array_key_exists('contactInfo', $result)) {

                if (array_key_exists(
                        'familyName',
                        $result['contactInfo']
                    ) && empty($currFields['lastname'])) {
                    $data['lastname'] = $result['contactInfo']['familyName'];
                }

                if (array_key_exists(
                        'givenName',
                        $result['contactInfo']
                    ) && empty($currFields['firstname'])) {
                    $data['firstname'] = $result['contactInfo']['givenName'];
                }

                if ((array_key_exists('websites', $result['contactInfo']) && count(
                            $result['contactInfo']['websites']
                        )) && empty($currFields['website'])) {
                    $data['website'] = $result['contactInfo']['websites'][0]['url'];
                }

                if ((array_key_exists('chats', $result['contactInfo']) && array_key_exists(
                            'skype',
                            $result['contactInfo']['chats']
                        )) && empty($currFields['skype'])) {
                    $data['skype'] = $result['contactInfo']['chats']['skype']['handle'];
                }

            }

            if ((null !== $org && array_key_exists('name', $org)) && empty($currFields['company'])) {
                $data['company'] = $org['name'];
            }

            if ((null !== $org && array_key_exists('title', $org)) && empty($currFields['position'])) {
                $data['position'] = $org['title'];
            }

            if ((null !== $loc && array_key_exists('city', $loc) && array_key_exists(
                        'name',
                        $loc['city']
                    )) && empty($currFields['city'])) {
                $data['city'] = $loc['city']['name'];
            }

            if ((null !== $loc && array_key_exists('state', $loc) && array_key_exists(
                        'name',
                        $loc['state']
                    )) && empty($currFields['state'])) {
                $data['state'] = $loc['state']['name'];
            }

            if ((null !== $loc && array_key_exists('country', $loc) && array_key_exists(
                        'name',
                        $loc['country']
                    )) && empty($currFields['country'])) {
                $data['country'] = $loc['country']['name'];
            }

            $model->setFieldValues($lead, $data);
            $model->getRepository()->saveEntity($lead);

            if ($notify && (!isset($lead->imported) || !$lead->imported)) {
                /** @var UserModel $userModel */
                $userModel = $this->getModel('user');
                $user      = $userModel->getEntity($uid);
                if ($user) {
                    $this->addNewNotification(
                        sprintf($this->translator->trans('mautic.plugin.fullcontact.company_retrieved'), $lead->getEmail()),
                        'FullContact Plugin',
                        'fa-search',
                        $user
                    );
                }
            }
        } catch (\Exception $ex) {
            try {
                if ($notify && isset($lead, $uid) && (!isset($lead->imported) || !$lead->imported)) {
                    /** @var UserModel $userModel */
                    $userModel = $this->getModel('user');
                    $user      = $userModel->getEntity($uid);
                    if ($user) {
                        $this->addNewNotification(
                            sprintf(
                                $this->translator->trans('mautic.plugin.fullcontact.unable'),
                                $lead->getEmail(),
                                $ex->getMessage()
                            ),
                            'FullContact Plugin',
                            'fa-exclamation',
                            $user
                        );
                    }
                }
            } catch (\Exception $ex2) {
                $this->get('monolog.mautic.logger')->log('error', 'FullContact: '.$ex2->getMessage());
            }
        }

        return new Response('OK');
    }

    /**
     * This is only called internally.
     *
     * @return Response
     *
     * @throws \InvalidArgumentException
     */
    private function compcallbackAction()
    {
        if (!$this->request->request->has('result') || !$this->request->request->has('webhookId')) {
            return new Response('ERROR');
        }

        $result             = $this->request->request->get('result', [], true);
        $oid                = $this->request->request->get('webhookId', [], true);
        list($w, $id, $uid) = explode('#', $oid, 3);
        $notify             = false !== strpos($w, '_notify');

        try {
            /** @var \Mautic\LeadBundle\Model\CompanyModel $model */
            $model = $this->getModel('lead.company');
            /** @var Company $company */
            $company = $model->getEntity($id);
            $currFields = $company->getFields(true);
            
            $org   = [];
            $loc   = [];
            $phone = [];
            $fax   = [];
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

            $data = [];

            if (array_key_exists('addressLine1', $loc) && empty($currFields['companyaddress1'])) {
                $data['companyaddress1'] = $loc['addressLine1'];
            }

            if (array_key_exists('addressLine2', $loc) && empty($currFields['companyaddress2'])) {
                $data['companyaddress2'] = $loc['addressLine2'];
            }

            if (array_key_exists('value', $email) && empty($currFields['companyemail'])) {
                $data['companyemail'] = $email['value'];
            }

            if (array_key_exists('number', $phone) && empty($currFields['companyphone'])) {
                $data['companyphone'] = $phone['number'];
            }

            if (array_key_exists('locality', $loc) && empty($currFields['companycity'])) {
                $data['companycity'] = $loc['locality'];
            }

            if (array_key_exists('postalCode', $loc) && empty($currFields['companyzipcode'])) {
                $data['companyzipcode'] = $loc['postalCode'];
            }

            if (array_key_exists('region', $loc) && empty($currFields['companystate'])) {
                $data['companystate'] = $loc['region']['name'];
            }

            if (array_key_exists('country', $loc) && empty($currFields['companycountry'])) {
                $data['companycountry'] = $loc['country']['name'];
            }

            if (array_key_exists('name', $org) && empty($currFields['companydescription'])) {
                $data['companydescription'] = $org['name'];
            }

            if (array_key_exists(
                    'approxEmployees',
                    $org
                ) && empty($currFields['companynumber_of_employees'])) {
                $data['companynumber_of_employees'] = $org['approxEmployees'];
            }

            if (array_key_exists('number', $fax) && empty($currFields['companyfax'])) {
                $data['companyfax'] = $fax['number'];
            }

            $model->setFieldValues($company, $data);
            $model->getRepository()->saveEntity($company);

            if ($notify) {
                /** @var UserModel $userModel */
                $userModel = $this->getModel('user');
                $user      = $userModel->getEntity($uid);
                if ($user) {
                    $this->addNewNotification(
                        sprintf($this->translator->trans('mautic.plugin.fullcontact.company_retrieved'), $company->getName()),
                        'FullContact Plugin',
                        'fa-search',
                        $user
                    );
                }
            }
        } catch (\Exception $ex) {
            try {
                if ($notify && isset($uid, $company)) {
                    /** @var UserModel $userModel */
                    $userModel = $this->getModel('user');
                    $user      = $userModel->getEntity($uid);
                    if ($user) {
                        $this->addNewNotification(
                            sprintf(
                                $this->translator->trans('mautic.plugin.fullcontact.unable'),
                                $company->getName(),
                                $ex->getMessage()
                            ),
                            'FullContact Plugin',
                            'fa-exclamation',
                            $user
                        );
                    }
                }
            } catch (\Exception $ex2) {
                $this->get('monolog.mautic.logger')->log('error', 'FullContact: '.$ex2->getMessage());
            }
        }

        return new Response('OK');
    }
}
