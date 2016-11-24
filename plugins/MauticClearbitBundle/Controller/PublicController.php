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
        $oid = $this->request->request->get('id', [], true);
        list($w, $id, $uid) = explode('#', $oid, 3);
        $notify = FALSE !== strpos($w, '_notify');

        $logger = $this->get('monolog.logger.mautic');

        try {

            if ('person' === $this->request->request->get('type', [], true)) {
                /** @var \Mautic\LeadBundle\Model\LeadModel $model */
                $model = $this->getModel('lead');
                /** @var Lead $lead */
                $lead = $model->getEntity($id);
                $currFields = $lead->getFields(true);

                $loc = [];
                if (array_key_exists('geo', $result)) {
                    $loc = $result['geo'];
                }

                $data = [];

                foreach ([
                             'facebook' => 'http://www.facebook.com/',
                             'googleplus' => 'http://plus.google.com/',
                             'linkedin' => 'http://www.linkedin.com/',
                             'twitter' => 'http://www.twitter.com/',
                         ] as $p => $u) {
                    foreach ($result as $type => $socialProfile) {
                        if ($type === $p && empty($currFields[$p])) {
                            $data[$p] = (array_key_exists('handle', $socialProfile) && $socialProfile['handle']) ? $u.$socialProfile['handle'] : '';
                            break;
                        }
                    }
                }

                if (array_key_exists('name', $result) && array_key_exists(
                        'familyName',
                        $result['name']
                    ) && empty($currFields['lastname'])) {
                    $data['lastname'] = $result['name']['familyName'];
                }

                if (array_key_exists('name', $result) && array_key_exists(
                        'givenName',
                        $result['name']
                    ) && empty($currFields['firstname'])) {
                    $data['firstname'] = $result['name']['givenName'];
                }

                if (array_key_exists('site', $result) && empty($currFields['website'])) {
                    $data['website'] = $result['site'];
                }

                if (array_key_exists('employment', $result) && array_key_exists(
                        'name',
                        $result['employment']
                    ) && empty($currFields['company'])) {
                    $data['company'] = $result['employment']['name'];
                }

                if (array_key_exists('employment', $result) && array_key_exists(
                        'title',
                        $result['employment']
                    ) && empty($currFields['position'])) {
                    $data['position'] = $result['employment']['title'];
                }

                if (array_key_exists('city', $loc) && empty($currFields['city'])) {
                    $data['city'] = $loc['city'];
                }
                if (array_key_exists('state', $loc) && empty($currFields['state'])) {
                    $data['state'] = $loc['state'];
                }

                if (array_key_exists('country', $loc) && empty($currFields['country'])) {
                    $data['country'] = $loc['country'];
                }

                $model->setFieldValues($lead, $data);
                $model->getRepository()->saveEntity($lead);

                if ($notify && (!isset($lead->imported) || !$lead->imported)) {
                    /** @var UserModel $userModel */
                    $userModel = $this->getModel('user');
                    $user = $userModel->getEntity($uid);
                    if ($user) {
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
                    $company = $model->getEntity($id);
                    $currFields = $company->getFields(true);

                    $loc = [];
                    if (array_key_exists('geo', $result)) {
                        $loc = $result['geo'];
                    }

                    $data = [];

                    if (array_key_exists('streetNumber', $loc) && array_key_exists(
                            'streetName',
                            $loc
                        ) && empty($currFields['companyaddress1'])) {
                        $data['companyaddress1'] = $loc['streetNumber'].' '.$loc['streetName'];
                    }

                    if (array_key_exists('city', $loc) && empty($currFields['companycity'])) {
                        $data['companycity'] = $loc['city'];
                    }

                    if (array_key_exists('metrics', $result) && array_key_exists(
                            'employees',
                            $result['metrics']
                        ) && empty($currFields['companynumber_of_employees'])) {
                        $data['companynumber_of_employees'] = $result['metrics']['employees'];
                    }

                    if (array_key_exists('description', $result) && empty($currFields['companydescription'])) {
                        $data['companydescription'] = $result['description'];
                    }

                    if (array_key_exists('phone', $result) && empty($currFields['companyphone'])) {
                        $data['companyphone'] = $result['phone'];
                    }

                    if (array_key_exists('site', $result) && array_key_exists(
                            'emailAddresses',
                            $result['site']
                        ) && count($result['site']['emailAddresses']) && empty($currFields['companyemail'])) {
                        $data['companyemail'] = $result['site']['emailAddresses'][0];
                    }

                    if (array_key_exists('country', $loc) && empty($currFields['companycountry'])) {
                        $data['companycountry'] = $loc['country'];
                    }

                    if (array_key_exists('postalCode', $loc) && empty($currFields['companyzipcode'])) {
                        $loc['postalCode'];
                    }

                    if (array_key_exists('state', $loc) && empty($currFields['companystate'])) {
                        $data['companystate'] = $loc['state'];
                    }

                    $model->setFieldValues($company, $data);
                    $model->getRepository()->saveEntity($company);

                    if ($notify) {
                        /** @var UserModel $userModel */
                        $userModel = $this->getModel('user');
                        $user = $userModel->getEntity($uid);
                        if ($user) {
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
                if ($notify && $uid) {
                    /** @var UserModel $userModel */
                    $userModel = $this->getModel('user');
                    $user = $userModel->getEntity($uid);
                    if ($user) {
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
            } catch(\Exception $ex2) {
                $this->get('monolog.mautic.logger')->log('error', 'Clearbit: ' . $ex2->getMessage());
            }
        }

        return new Response('OK');
    }

}
