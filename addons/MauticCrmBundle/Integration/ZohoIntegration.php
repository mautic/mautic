<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticCrmBundle\Integration;
use MauticAddon\MauticCrmBundle\Api\Exception\ErrorException;

/**
 * Class ZohoIntegration
 */
abstract class ZohoIntegration extends CrmAbstractIntegration
{

    /**
     * Returns the name of the social integration that must match the name of the file
     *
     * @return string
     */
    public function getName()
    {
        return 'Zoho';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return array(
            'email_id' => 'mautic.zoho.form.email',
            'password' => 'mautic.zoho.form.password'
        );
    }

    /**
     * @param array  $parameters
     * @param string $authMethod
     *
     * @return \MauticAddon\MauticCrmBundle\Api\Auth\AbstractAuth|void
     */
    public function createApiAuth($parameters = array(), $authMethod = 'Auth')
    {
        $zohoSettings = $this->settings->getApiKeys();

        parent::createApiAuth($zohoSettings);
    }

    /**
     * Check API Authentication
     */
    public function checkApiAuth($silenceExceptions = true)
    {
        try {
            if (!$this->auth->isAuthorized()) {
                return false;
            } else {
                return true;
            }
        } catch (ErrorException $exception) {
            $this->logIntegrationError($exception);

            if (!$silenceExceptions) {
                throw $exception;
            }
            return false;
        }
    }

    /**
     * @return array
     */
    public function getAvailableFields($silenceExceptions = true)
    {
        $zohoFields = array();

        try {
            if ($this->checkApiAuth($silenceExceptions)) {
                $leadObject = CrmApi::getContext($this->getName(), "lead", $this->auth)->getFields('Leads');

                if (isset($leadObject['response']) && isset($leadObject['response']['error'])) {
                    return array();
                }

                //@todo need to set array("type" => "string");
                $zohoFields = array();
                foreach ($leadObject['Leads']['section'] as $optgroup) {
                    $zohoFields[$optgroup['dv']] = array();
                    if (!array_key_exists(0, $optgroup['FL']))
                        $optgroup['FL'] = array($optgroup['FL']);
                    foreach ($optgroup['FL'] as $field) {
                        $zohoFields[$optgroup['dv']][$field['dv']] = $field['dv'];
                    }
                }
            }
        } catch (ErrorException $exception) {
            $this->logIntegrationError($exception);

            if (!$silenceExceptions) {
                throw $exception;
            }
            return false;
        }

        return $zohoFields;
    }

    /**
     * @param $data
     * @return mixed|void
     */
    public function create(MauticFactory $factory, $data)
    {
        try {
            $this->factory = $factory;
            $router = $this->factory->getRouter();
            $request = $this->factory->getRequest();
            $modelClient = $this->factory->getModel('mapper.ApplicationClient');
            $application = 'zoho';
            $request->set('application', $application);
            $entityClient = $modelClient->loadByApplication($application);
            $client = $entityClient->getAlias();
            $request->set('client', $client);

            $this->checkApiAuth();

            $modelMapper = $this->factory->getModel('mapper.ApplicationObjectMapper');
            $mapperEntity = $modelMapper->getByClientAndObject($entityClient->getId(),$this->getBaseName());

            $mappedFields = array();

            $xmlData = '<Leads>';
            $xmlData .= '<row no="1">';
            foreach ($mappedFields as $field) {
                $xmlData .= sprintf('<FL val="%s"><![CDATA[%s]]></FL>', $field['name'], $field['value']);
            }
            $xmlData .= '</row>';
            $xmlData .= '</Leads>';

            $zohoSettings = $entityClient->getApiKeys();

            $zohoAuth = ApiAuth::initiate($zohoSettings);
            $leadObject = CrmApi::getContext('Zoho', "object", $zohoAuth)->insert('Leads', $xmlData);

            if (isset($leadObject['response']) && isset($leadObject['response']['error'])) {
                throw new ErrorException($leadObject['response']['error']['message'],$leadObject['response']['error']['code']);
            }


        } catch (ErrorException $exception) {
            //remove keys and try again
            if ($exception->getCode() == 1) {
                $zohoSettings = $entityClient->getApiKeys();
                unset($zohoSettings['accessToken']);
                unset($zohoSettings['accessTokenExpires']);
                $entityClient->setApiKeys($zohoSettings);
                $modelClient->saveEntity($entityClient);
            }
            $this->factory->getSession()->getFlashBag()->add('error',
                $this->factory->getTranslator()->trans(
                    $exception->getMessage(),
                    (!empty($flash['msgVars']) ? $flash['msgVars'] : array()),
                    'flashes'
                ));

            $url = $router->generate('mautic_crm_client_objects_index', array(
                'client'      => $client,
                'application' => $application
            ));

            $redirect = new RedirectResponse($url);
            $redirect->send();
        }
    }
}
