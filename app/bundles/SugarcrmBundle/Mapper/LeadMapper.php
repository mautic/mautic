<?php
namespace Mautic\SugarcrmBundle\Mapper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\MapperBundle\Mapper\AbstractMapper;
use Mautic\SugarcrmBundle\Api\Exception\ErrorException;
use Symfony\Component\Form\FormBuilderInterface;
use Mautic\SugarcrmBundle\Api\SugarCRMApi;
use Mautic\SugarcrmBundle\Api\Auth\ApiAuth;
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

        $sugarCRMSettings = $entityClient->getApiKeys();
        $sugarCRMSettings['requestTokenUrl'] = sprintf('%s/rest/v10/oauth2/token',$sugarCRMSettings['url']);
        $sugarCRMSettings['callback'] = $this->factory->getRouter()->generate('mautic_mapper_authentication_callback', array('application' => $application, 'client' => $client));
        if (isset($sugarCRMSettings['accessToken']) && is_null($sugarCRMSettings['accessToken'])) {
            unset($sugarCRMSettings['accessToken']);
        }
        if (isset($sugarCRMSettings['accessTokenExpires']) && is_null($sugarCRMSettings['accessTokenExpires'])) {
            unset($sugarCRMSettings['accessTokenExpires']);
        }
        try {
            $sugarAuth = ApiAuth::initiate($sugarCRMSettings);
            if ($sugarAuth->validateAccessToken()) {
                if ($sugarAuth->accessTokenUpdated()) {
                    $accessTokenData = $sugarAuth->getAccessTokenData();
                    $sugarCRMSettings['accessToken'] = $accessTokenData['access_token'];
                    $sugarCRMSettings['accessTokenExpires'] = $accessTokenData['expires'];
                    $entityClient->setApiKeys($sugarCRMSettings);
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
            $sugarCRMSettings = $entityClient->getApiKeys();

            $sugarAuth = ApiAuth::initiate($sugarCRMSettings);
            $leadObject = SugarCRMApi::getContext("object", $sugarAuth)->getInfo("Leads");

            $sugarFields = array();
            foreach ($leadObject['fields'] as $fieldInfo) {
                if (!isset($fieldInfo['name'])) continue;
                $sugarFields[$fieldInfo['name']] = $fieldInfo['name'];
            }

            $builder->add('sugar_field','choice',array(
                'choices' => $sugarFields
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
                $sugarCRMSettings = $entityClient->getApiKeys();
                unset($sugarCRMSettings['accessToken']);
                unset($sugarCRMSettings['accessTokenExpires']);
                $entityClient->setApiKeys($sugarCRMSettings);
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