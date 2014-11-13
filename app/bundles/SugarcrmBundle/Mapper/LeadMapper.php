<?php
namespace Mautic\SugarcrmBundle\Mapper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\MapperBundle\Mapper\AbstractMapper;
use Symfony\Component\Form\FormBuilderInterface;

//import 3rd API library
require_once dirname(dirname(__FILE__)).'/Library/SugarCRMApi.php';

class LeadMapper extends AbstractMapper
{
    /**
     * Check API Authentication
     */
    private function checkApiAuth()
    {
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
        $sugarAuth = \SugarCRM\Auth\ApiAuth::initiate($sugarCRMSettings);
        if ($sugarAuth->validateAccessToken()) {
            if ($sugarAuth->accessTokenUpdated()) {
                $accessTokenData = $sugarAuth->getAccessTokenData();
                $sugarCRMSettings['accessToken'] = $accessTokenData['access_token'];
                $sugarCRMSettings['accessTokenExpires'] = $accessTokenData['expires'];
                $entityClient->setApiKeys($sugarCRMSettings);
                $modelClient->saveEntity($entityClient);
            }
        }
    }

    /**
     * @param MauticFactory $factory
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(MauticFactory $factory,FormBuilderInterface $builder, array $options)
    {
        $this->factory = $factory;

        $this->checkApiAuth();

        $client = $this->factory->getRequest()->get('client');

        $modelClient = $this->factory->getModel('mapper.ApplicationClient');
        $entityClient = $modelClient->loadByAlias($client);
        $sugarCRMSettings = $entityClient->getApiKeys();

        $sugarAuth = \SugarCRM\Auth\ApiAuth::initiate($sugarCRMSettings);
        $leadObject = \SugarCRM\SugarCRMApi::getContext("object", $sugarAuth)->getInfo("Leads");

        var_dump($leadObject);
        die(__FILE__);





    }
}