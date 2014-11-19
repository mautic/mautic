<?php
namespace Mautic\SugarcrmBundle\Mapper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\MapperBundle\Mapper\AbstractMapper;
use Mautic\SalesforceBundle\Api\Exception\ErrorException;
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
            $salesforceSettings = $entityClient->getApiKeys();





        } catch (ErrorException $exception) {
            //remove keys and try again
            if ($exception->getCode() == 1) {
                $salesforceSettings = $entityClient->getApiKeys();
                unset($salesforceSettings['accessToken']);
                unset($salesforceSettings['accessTokenExpires']);
                $entityClient->setApiKeys($salesforceSettings);
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