<?php
namespace Mautic\SalesforceBundle\Mapper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\MapperBundle\Mapper\AbstractMapper;
use Mautic\SalesforceBundle\Api\Exception\ErrorException;
use Mautic\SalesforceBundle\Api\SalesforceApi;
use Symfony\Component\Form\FormBuilderInterface;
use Mautic\SalesforceBundle\Api\SugarCRMApi;
use Mautic\SalesforceBundle\Api\Auth\ApiAuth;
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

        $salesForceSettings = $entityClient->getApiKeys();
        $salesForceSettings['callback'] = $this->factory->getRouter()->generate('mautic_mapper_authentication_callback', array('application' => $application, 'client' => $client));
        $salesForceSettings['accessTokenUrl'] = 'https://login.salesforce.com/services/oauth2/token';
        $salesForceSettings['authorizationUrl'] = 'https://login.salesforce.com/services/oauth2/authorize';
        if (isset($salesForceSettings['accessToken']) && is_null($salesForceSettings['accessToken'])) {
            unset($salesForceSettings['accessToken']);
        }
        if (isset($salesForceSettings['accessTokenExpires']) && is_null($salesForceSettings['accessTokenExpires'])) {
            unset($salesForceSettings['accessTokenExpires']);
        }
        try {
            $salesAuth = ApiAuth::initiate($salesForceSettings);
            if ($salesAuth->validateAccessToken()) {
                if ($salesAuth->accessTokenUpdated()) {
                    $accessTokenData = $salesAuth->getAccessTokenData();
                    $salesForceSettings['accessToken'] = $accessTokenData['access_token'];
                    $salesForceSettings['instance_url'] = $accessTokenData['instance_url'];
                    $salesForceSettings['tokenType'] = $accessTokenData['token_type'];
                    $entityClient->setApiKeys($salesForceSettings);
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

            $url = $router->generate('mautic_mapper_client_objects_index', array(
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
            $salesForceSettings = $entityClient->getApiKeys();

            $salesAuth = ApiAuth::initiate($salesForceSettings);
            $leadObject = SalesforceApi::getContext("object", $salesAuth)->getInfo("lead");

            $salesFields = array();
            foreach ($leadObject['fields'] as $fieldInfo) {
                if (!isset($fieldInfo['name'])) continue;
                $salesFields[$fieldInfo['name']] = $fieldInfo['name'];
            }

            $builder->add('sales_field','choice',array(
                'choices' => $salesFields
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
                $salesForceSettings = $entityClient->getApiKeys();
                unset($salesForceSettings['accessToken']);
                unset($salesForceSettings['accessTokenExpires']);
                $entityClient->setApiKeys($salesForceSettings);
                $modelClient->saveEntity($entityClient);
            }
            $this->factory->getSession()->getFlashBag()->add('error',
                $this->factory->getTranslator()->trans(
                    $exception->getMessage(),
                    (!empty($flash['msgVars']) ? $flash['msgVars'] : array()),
                    'flashes'
                ));

            $url = $router->generate('mautic_mapper_client_objects_index', array(
                'client'      => $client,
                'application' => $application
            ));

            $redirect = new RedirectResponse($url);
            $redirect->send();
        }
    }
}