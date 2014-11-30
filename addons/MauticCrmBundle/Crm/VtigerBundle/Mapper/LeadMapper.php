<?php
namespace MauticAddon\MauticCrmBundle\Crm\VtigerBundle\Mapper;

use Mautic\CoreBundle\Factory\MauticFactory;
use MauticAddon\MauticCrmBundle\Mapper\AbstractMapper;
use MauticAddon\MauticCrmBundle\Crm\VtigerBundle\Api\Exception\ErrorException;
use Symfony\Component\Form\FormBuilderInterface;
use MauticAddon\MauticCrmBundle\Crm\VtigerBundle\Api\vTigerCRMApi;
use MauticAddon\MauticCrmBundle\Crm\VtigerBundle\Api\Auth\ApiAuth;
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

        $vtigerSettings = $entityClient->getApiKeys();

        if (isset($vtigerSettings['accessToken']) && is_null($vtigerSettings['accessToken'])) {
            unset($vtigerSettings['accessToken']);
        }
        if (isset($vtigerSettings['accessTokenExpires']) && is_null($vtigerSettings['accessTokenExpires'])) {
            unset($vtigerSettings['accessTokenExpires']);
        }
        try {
            $vTigerAuth = ApiAuth::initiate($vtigerSettings);
            if ($vTigerAuth->validateAccessToken()) {
                if ($vTigerAuth->accessTokenUpdated()) {
                    $accessTokenData = $vTigerAuth->getAccessTokenData();
                    $vtigerSettings = array_merge($vtigerSettings, $accessTokenData);
                    $entityClient->setApiKeys($vtigerSettings);
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
            $vtigerSettings = $entityClient->getApiKeys();

            $vTigerAuth = ApiAuth::initiate($vtigerSettings);
            $leadObject = vTigerCRMApi::getContext("object", $vTigerAuth)->describe("Leads");

            $vtigerFields = array();
            foreach ($leadObject['fields'] as $fieldInfo) {
                if (!isset($fieldInfo['name'])) continue;
                $vtigerFields[$fieldInfo['name']] = $fieldInfo['name'];
            }

            $builder->add('vtiger_field','choice',array(
                'choices' => $vtigerFields
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
                $vtigerSettings = $entityClient->getApiKeys();
                unset($vtigerSettings['accessToken']);
                unset($vtigerSettings['accessTokenExpires']);
                $entityClient->setApiKeys($vtigerSettings);
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

    }
}