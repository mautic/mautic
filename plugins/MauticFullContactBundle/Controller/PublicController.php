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

        $result           = json_decode($this->request->request->get('result', []), true);
        $oid              = $this->request->request->get('webhookId', '');
        $validatedRequest = $this->get('mautic.plugin.fullcontact.lookup_helper')->validateRequest($oid);

        if (!$validatedRequest || !is_array($result)) {
            return new Response('ERROR');
        }

        if ('company' == $validatedRequest['type']) {
            return $this->compcallbackAction($result, $validatedRequest);
        }

        $notify = $validatedRequest['notify'];
        $logger = $this->get('monolog.logger.mautic');

        try {
            /** @var \Mautic\LeadBundle\Model\LeadModel $model */
            $model = $this->getModel('lead');
            /** @var Lead $lead */
            $lead       = $validatedRequest['entity'];
            $currFields = $lead->getFields(true);

            $org = [];
            if (array_key_exists('organizations', $result)) {
                /** @var array $organizations */
                $organizations = $result['organizations'];
                foreach ($organizations as $organization) {
                    if (array_key_exists('isPrimary', $organization) && !empty($organization['isPrimary'])) {
                        $org = $organization;
                        break;
                    }
                }

                if (0 === count($org) && 0 !== count($result['organizations'])) {
                    // primary not found, use the first one if exists
                    $org = $result['organizations'][0];
                }
            }

            $loc = [];
            if (array_key_exists('demographics', $result)
                && array_key_exists(
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
                    if (array_key_exists('type', $socialProfile) && $socialProfile['type'] === $p && empty($currFields[$p]['value'])) {
                        $data[$p] = array_key_exists('url', $socialProfile) ? $socialProfile['url'] : '';
                        break;
                    }
                }
            }

            if (array_key_exists('contactInfo', $result)) {
                if (array_key_exists(
                        'familyName',
                        $result['contactInfo']
                    )
                    && empty($currFields['lastname']['value'])
                ) {
                    $data['lastname'] = $result['contactInfo']['familyName'];
                }

                if (array_key_exists(
                        'givenName',
                        $result['contactInfo']
                    )
                    && empty($currFields['firstname']['value'])
                ) {
                    $data['firstname'] = $result['contactInfo']['givenName'];
                }

                if ((array_key_exists('websites', $result['contactInfo'])
                        && count(
                            $result['contactInfo']['websites']
                        ))
                    && empty($currFields['website']['value'])
                ) {
                    $data['website'] = $result['contactInfo']['websites'][0]['url'];
                }

                if ((array_key_exists('chats', $result['contactInfo'])
                        && array_key_exists(
                            'skype',
                            $result['contactInfo']['chats']
                        ))
                    && empty($currFields['skype']['value'])
                ) {
                    $data['skype'] = $result['contactInfo']['chats']['skype']['handle'];
                }
            }

            if (array_key_exists('name', $org) && empty($currFields['company']['value'])) {
                $data['company'] = $org['name'];
            }

            if (array_key_exists('title', $org) && empty($currFields['position']['value'])) {
                $data['position'] = $org['title'];
            }

            if ((array_key_exists('city', $loc)
                    && array_key_exists(
                        'name',
                        $loc['city']
                    ))
                && empty($currFields['city']['value'])
            ) {
                $data['city'] = $loc['city']['name'];
            }

            if ((array_key_exists('state', $loc)
                    && array_key_exists(
                        'name',
                        $loc['state']
                    ))
                && empty($currFields['state']['value'])
            ) {
                $data['state'] = $loc['state']['name'];
            }

            if ((array_key_exists('country', $loc)
                    && array_key_exists(
                        'name',
                        $loc['country']
                    ))
                && empty($currFields['country']['value'])
            ) {
                $data['country'] = $loc['country']['name'];
            }

            $logger->log('debug', 'SET FIELDS: '.print_r($data, true));

            // Unset the nonce so that it's not used again
            $socialCache = $lead->getSocialCache();
            unset($socialCache['fullcontact']['nonce']);
            $lead->setSocialCache($socialCache);

            $model->setFieldValues($lead, $data);
            $model->getRepository()->saveEntity($lead);

            if ($notify && (!isset($lead->imported) || !$lead->imported)) {
                /** @var UserModel $userModel */
                $userModel = $this->getModel('user');
                if ($user = $userModel->getEntity($notify)) {
                    $this->addNewNotification(
                        sprintf($this->translator->trans('mautic.plugin.fullcontact.contact_retrieved'), $lead->getEmail()),
                        'FullContact Plugin',
                        'fa-search',
                        $user
                    );
                }
            }
        } catch (\Exception $ex) {
            try {
                if ($notify && isset($lead) && (!isset($lead->imported) || !$lead->imported)) {
                    /** @var UserModel $userModel */
                    $userModel = $this->getModel('user');
                    if ($user = $userModel->getEntity($notify)) {
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
    private function compcallbackAction($result, $validatedRequest)
    {
        $notify = $validatedRequest['notify'];
        $logger = $this->get('monolog.logger.mautic');

        try {
            /** @var \Mautic\LeadBundle\Model\CompanyModel $model */
            $model = $this->getModel('lead.company');
            /** @var Company $company */
            $company    = $validatedRequest['entity'];
            $currFields = $company->getFields(true);

            $org   = [];
            $loc   = [];
            $phone = [];
            $fax   = [];
            $email = [];
            if (array_key_exists('organization', $result)) {
                $org = $result['organization'];
                if (array_key_exists('contactInfo', $result['organization'])) {
                    if (array_key_exists('addresses', $result['organization']['contactInfo'])
                        && count(
                            $result['organization']['contactInfo']['addresses']
                        )
                    ) {
                        $loc = $result['organization']['contactInfo']['addresses'][0];
                    }
                    if (array_key_exists('emailAddresses', $result['organization']['contactInfo'])
                        && count(
                            $result['organization']['contactInfo']['emailAddresses']
                        )
                    ) {
                        $email = $result['organization']['contactInfo']['emailAddresses'][0];
                    }
                    if (array_key_exists('phoneNumbers', $result['organization']['contactInfo'])
                        && count(
                            $result['organization']['contactInfo']['phoneNumbers']
                        )
                    ) {
                        $phone = $result['organization']['contactInfo']['phoneNumbers'][0];
                        foreach ($result['organization']['contactInfo']['phoneNumbers'] as $phoneNumber) {
                            if (array_key_exists('label', $phoneNumber)
                                && 0 >= strpos(
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

            if (array_key_exists('addressLine1', $loc) && empty($currFields['companyaddress1']['value'])) {
                $data['companyaddress1'] = $loc['addressLine1'];
            }

            if (array_key_exists('addressLine2', $loc) && empty($currFields['companyaddress2']['value'])) {
                $data['companyaddress2'] = $loc['addressLine2'];
            }

            if (array_key_exists('value', $email) && empty($currFields['companyemail']['value'])) {
                $data['companyemail'] = $email['value'];
            }

            if (array_key_exists('number', $phone) && empty($currFields['companyphone']['value'])) {
                $data['companyphone'] = $phone['number'];
            }

            if (array_key_exists('locality', $loc) && empty($currFields['companycity']['value'])) {
                $data['companycity'] = $loc['locality'];
            }

            if (array_key_exists('postalCode', $loc) && empty($currFields['companyzipcode']['value'])) {
                $data['companyzipcode'] = $loc['postalCode'];
            }

            if (array_key_exists('region', $loc) && empty($currFields['companystate']['value'])) {
                $data['companystate'] = $loc['region']['name'];
            }

            if (array_key_exists('country', $loc) && empty($currFields['companycountry']['value'])) {
                $data['companycountry'] = $loc['country']['name'];
            }

            if (array_key_exists('name', $org) && empty($currFields['companydescription']['value'])) {
                $data['companydescription'] = $org['name'];
            }

            if (array_key_exists(
                    'approxEmployees',
                    $org
                )
                && empty($currFields['companynumber_of_employees']['value'])
            ) {
                $data['companynumber_of_employees'] = $org['approxEmployees'];
            }

            if (array_key_exists('number', $fax) && empty($currFields['companyfax']['value'])) {
                $data['companyfax'] = $fax['number'];
            }

            $logger->log('debug', 'SET FIELDS: '.print_r($data, true));

            // Unset the nonce so that it's not used again
            $socialCache = $company->getSocialCache();
            unset($socialCache['fullcontact']['nonce']);
            $company->setSocialCache($socialCache);

            $model->setFieldValues($company, $data);
            $model->getRepository()->saveEntity($company);

            if ($notify) {
                /** @var UserModel $userModel */
                $userModel = $this->getModel('user');
                if ($user = $userModel->getEntity($notify)) {
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
                if ($notify && isset($company)) {
                    /** @var UserModel $userModel */
                    $userModel = $this->getModel('user');
                    if ($user = $userModel->getEntity($notify)) {
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
