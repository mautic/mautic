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
        $logger = $this->get('monolog.logger.mautic');

        if (!$this->request->request->has('body') || !$this->request->request->has('id')
            || !$this->request->request->has('type')
            || !$this->request->request->has('status')
            || 200 !== $this->request->request->get('status')
        ) {
            $logger->log('error', 'ERROR on Clearbit callback: Malformed request variables: '.json_encode($this->request->request->all(), JSON_PRETTY_PRINT));

            return new Response('ERROR');
        }

        /** @var array $result */
        $result           = $this->request->request->get('body', []);
        $oid              = $this->request->request->get('id');
        $validatedRequest = $this->get('mautic.plugin.clearbit.lookup_helper')->validateRequest($oid, $this->request->request->get('type'));

        if (!$validatedRequest || !is_array($result)) {
            $logger->log('error', 'ERROR on Clearbit callback: Wrong body or id in request: id='.$oid.' body='.json_encode($result, JSON_PRETTY_PRINT));

            return new Response('ERROR');
        }

        $notify = $validatedRequest['notify'];

        try {
            if ('person' === $this->request->request->get('type')) {
                /** @var \Mautic\LeadBundle\Model\LeadModel $model */
                $model = $this->getModel('lead');
                /** @var Lead $lead */
                $lead       = $validatedRequest['entity'];
                $currFields = $lead->getFields(true);
                $logger->log('debug', 'CURRFIELDS: '.var_export($currFields, true));

                $loc = [];
                if (array_key_exists('geo', $result)) {
                    $loc = $result['geo'];
                }

                $data = [];

                foreach ([
                             'facebook' => 'http://www.facebook.com/',
                             'linkedin' => 'http://www.linkedin.com/',
                             'twitter' => 'http://www.twitter.com/',
                         ] as $p => $u) {
                    foreach ($result as $type => $socialProfile) {
                        if ($type === $p && empty($currFields[$p]['value'])) {
                            $data[$p] = (array_key_exists('handle', $socialProfile) && $socialProfile['handle']) ? $u.$socialProfile['handle'] : '';
                            break;
                        }
                    }
                }

                if (array_key_exists('name', $result)
                    && array_key_exists(
                        'familyName',
                        $result['name']
                    )
                    && empty($currFields['lastname']['value'])
                ) {
                    $data['lastname'] = $result['name']['familyName'];
                }

                if (array_key_exists('name', $result)
                    && array_key_exists(
                        'givenName',
                        $result['name']
                    )
                    && empty($currFields['firstname']['value'])
                ) {
                    $data['firstname'] = $result['name']['givenName'];
                }

                if (array_key_exists('site', $result) && empty($currFields['website']['value'])) {
                    $data['website'] = $result['site'];
                }

                if (array_key_exists('employment', $result)
                    && array_key_exists(
                        'name',
                        $result['employment']
                    )
                    && empty($currFields['company']['value'])
                ) {
                    $data['company'] = $result['employment']['name'];
                }

                if (array_key_exists('employment', $result)
                    && array_key_exists(
                        'title',
                        $result['employment']
                    )
                    && empty($currFields['position']['value'])
                ) {
                    $data['position'] = $result['employment']['title'];
                }

                if (array_key_exists('city', $loc) && empty($currFields['city']['value'])) {
                    $data['city'] = $loc['city'];
                }
                if (array_key_exists('state', $loc) && empty($currFields['state']['value'])) {
                    $data['state'] = $loc['state'];
                }

                if (array_key_exists('country', $loc) && empty($currFields['country']['value'])) {
                    $data['country'] = $loc['country'];
                }

                $logger->log('debug', 'SETTING FIELDS: '.print_r($data, true));

                // Unset the nonce so that it's not used again
                $socialCache = $lead->getSocialCache();
                unset($socialCache['clearbit']['nonce']);
                $lead->setSocialCache($socialCache);

                $model->setFieldValues($lead, $data);
                $model->saveEntity($lead);

                if ($notify && (!isset($lead->imported) || !$lead->imported)) {
                    /** @var UserModel $userModel */
                    $userModel = $this->getModel('user');
                    if ($user = $userModel->getEntity($notify)) {
                        $this->addNewNotification(
                            sprintf($this->translator->trans('mautic.plugin.clearbit.contact_retrieved'), $lead->getEmail()),
                            'Clearbit Plugin',
                            'fa-search',
                            $user
                        );
                    }
                }
            } else {
                /******************  COMPANY STUFF  *********************/

                if ('company' === $this->request->request->get('type', [], true)) {
                    /** @var \Mautic\LeadBundle\Model\CompanyModel $model */
                    $model = $this->getModel('lead.company');
                    /** @var Company $company */
                    $company    = $validatedRequest['entity'];
                    $currFields = $company->getFields(true);

                    $loc = [];
                    if (array_key_exists('geo', $result)) {
                        $loc = $result['geo'];
                    }

                    $data = [];

                    if (array_key_exists('streetNumber', $loc)
                        && array_key_exists(
                            'streetName',
                            $loc
                        )
                        && empty($currFields['companyaddress1']['value'])
                    ) {
                        $data['companyaddress1'] = $loc['streetNumber'].' '.$loc['streetName'];
                    }

                    if (array_key_exists('city', $loc) && empty($currFields['companycity']['value'])) {
                        $data['companycity'] = $loc['city'];
                    }

                    if (array_key_exists('metrics', $result)
                        && array_key_exists(
                            'employees',
                            $result['metrics']
                        )
                        && empty($currFields['companynumber_of_employees']['value'])
                    ) {
                        $data['companynumber_of_employees'] = $result['metrics']['employees'];
                    }

                    if (array_key_exists('description', $result) && empty($currFields['companydescription']['value'])) {
                        $data['companydescription'] = $result['description'];
                    }

                    if (array_key_exists('phone', $result) && empty($currFields['companyphone']['value'])) {
                        $data['companyphone'] = $result['phone'];
                    }

                    if (array_key_exists('site', $result)
                        && array_key_exists(
                            'emailAddresses',
                            $result['site']
                        )
                        && count($result['site']['emailAddresses'])
                        && empty($currFields['companyemail']['value'])
                    ) {
                        $data['companyemail'] = $result['site']['emailAddresses'][0];
                    }

                    if (array_key_exists('country', $loc) && empty($currFields['companycountry']['value'])) {
                        $data['companycountry'] = $loc['country'];
                    }

                    if (array_key_exists('state', $loc) && empty($currFields['companystate']['value'])) {
                        $data['companystate'] = $loc['state'];
                    }

                    $logger->log('debug', 'SETTING FIELDS: '.print_r($data, true));

                    // Unset the nonce so that it's not used again
                    $socialCache = $company->getSocialCache();
                    unset($socialCache['clearbit']['nonce']);
                    $company->setSocialCache($socialCache);

                    $model->setFieldValues($company, $data);
                    $model->saveEntity($company);

                    if ($notify) {
                        /** @var UserModel $userModel */
                        $userModel = $this->getModel('user');
                        if ($user = $userModel->getEntity($notify)) {
                            $this->addNewNotification(
                                sprintf($this->translator->trans('mautic.plugin.clearbit.company_retrieved'), $company->getName()),
                                'Clearbit Plugin',
                                'fa-search',
                                $user
                            );
                        }
                    }
                }
            }
        } catch (\Exception $ex) {
            $logger->log('error', 'ERROR on Clearbit callback: '.$ex->getMessage());
            try {
                if ($notify) {
                    /** @var UserModel $userModel */
                    $userModel = $this->getModel('user');
                    if ($user = $userModel->getEntity($notify)) {
                        $this->addNewNotification(
                            sprintf(
                                $this->translator->trans('mautic.plugin.clearbit.unable'),
                                $ex->getMessage()
                            ),
                            'Clearbit Plugin',
                            'fa-exclamation',
                            $user
                        );
                    }
                }
            } catch (\Exception $ex2) {
                $this->get('monolog.logger.mautic')->log('error', 'Clearbit: '.$ex2->getMessage());
            }
        }

        return new Response('OK');
    }
}
