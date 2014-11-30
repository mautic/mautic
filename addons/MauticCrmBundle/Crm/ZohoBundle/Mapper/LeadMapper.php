<?php
namespace MauticAddon\MauticCrmBundle\Crm\ZohoBundle\Mapper;

use Mautic\CoreBundle\Factory\MauticFactory;
use MauticAddon\MauticCrmBundle\Mapper\AbstractMapper;
use MauticAddon\MauticCrmBundle\Crm\ZohoBundle\Api\Exception\ErrorException;
use Symfony\Component\Form\FormBuilderInterface;
use MauticAddon\MauticCrmBundle\Crm\ZohoBundle\Api\ZohoCRMApi;
use MauticAddon\MauticCrmBundle\Crm\ZohoBundle\Api\Auth\ApiAuth;
use Symfony\Component\HttpFoundation\RedirectResponse;

class LeadMapper extends AbstractMapper
{
    /**
     * Check API Authentication
     */
    public function checkApiAuth()
    {
        $router = $this->factory->getRouter();
        $request = $this->factory->getRequest();
        $client = $request->get('client');
        $application = $request->get('application');
        $modelClient = $this->factory->getModel('mapper.ApplicationClient');
        $entityClient = $modelClient->loadByAlias($client);

        $zohoSettings = $entityClient->getApiKeys();

        if (isset($zohoSettings['accessToken']) && is_null($zohoSettings['accessToken'])) {
            unset($zohoSettings['accessToken']);
        }
        if (isset($zohoSettings['accessTokenExpires']) && is_null($zohoSettings['accessTokenExpires'])) {
            unset($zohoSettings['accessTokenExpires']);
        }

        try {
            $zohoAuth = ApiAuth::initiate($zohoSettings);
            if ($zohoAuth->validateAccessToken()) {
                if ($zohoAuth->accessTokenUpdated()) {
                    $accessTokenData = $zohoAuth->getAccessTokenData();
                    $zohoSettings = array_merge($zohoSettings, $accessTokenData);
                    $entityClient->setApiKeys($zohoSettings);
                    $modelClient->saveEntity($entityClient);
                }
            }
        } catch (ErrorException $exception) {
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

    /**
     * @param MauticFactory $factory
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(MauticFactory $factory,FormBuilderInterface $builder, array $options)
    {
        try {
            $this->factory = $factory;
            $router = $this->factory->getRouter();
            $request = $this->factory->getRequest();
            $client = $request->get('client');
            $application = $request->get('application');

            $this->checkApiAuth();

            $client = $this->factory->getRequest()->get('client');

            $modelClient = $this->factory->getModel('mapper.ApplicationClient');
            $entityClient = $modelClient->loadByAlias($client);
            $zohoSettings = $entityClient->getApiKeys();

            $zohoAuth = ApiAuth::initiate($zohoSettings);
            $leadObject = ZohoCRMApi::getContext("object", $zohoAuth)->getFields('Leads');

            if (isset($leadObject['response']) && isset($leadObject['response']['error'])) {
                throw new ErrorException($leadObject['response']['error']['message'],$leadObject['response']['error']['code']);
            }

            $zohoFields = array();
            foreach ($leadObject['Leads']['section'] as $optgroup) {
                $zohoFields[$optgroup['dv']] = array();
                if (!array_key_exists(0,$optgroup['FL'])) $optgroup['FL'] = array($optgroup['FL']);
                foreach ($optgroup['FL'] as $field) {
                    $zohoFields[$optgroup['dv']][$field['dv']] = $field['dv'];
                }
            }


            $builder->add('zoho_field','choice',array(
                'choices' => $zohoFields
            ));

            $leadEntities = $this->factory->getModel('lead.field')->getEntities();
            $mauticFields = array();

            foreach ($leadEntities as $leadEntity) {
                $alias = $leadEntity->getAlias();
                $mauticFields[$alias] = $alias;
            }

            $builder->add('mautic_field', 'choice', array(
                'choices' => $mauticFields
            ));
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
            $leadObject = ZohoCRMApi::getContext("object", $zohoAuth)->insert('Leads', $xmlData);

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