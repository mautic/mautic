<?php
namespace Mautic\ZohoBundle\Api\Auth;

/**
 * Class Auth
 * @package Salesforce\Auth
 */
class ApiAuth
{
    /**
     * @param array  $parameters
     * @param string $authMethod
     *
     * @return mixed
     */
    public static function initiate ($parameters = array(), $authMethod = 'Rest')
    {
        $class      = __NAMESPACE__ .'\\'. $authMethod;
        $authObject = new $class();

        $reflection = new \ReflectionMethod($class, 'setup');
        $pass       = array();
        foreach ($reflection->getParameters() as $param) {
            if (isset($parameters[$param->getName()])) {
                $pass[] = $parameters[$param->getName()];
            } else {
                $pass[] = null;
            }
        }

        $reflection->invokeArgs($authObject, $pass);

        return $authObject;
    }
}