<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      dragan-mf
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\RabbitMQBundle\Integration;

use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Integration\AbstractIntegration;

/**
 * Class RabbitMQIntegration
 */
class RabbitMQIntegration extends AbstractIntegration
{
    public function getName()
    {
        return 'RabbitMQ';
    }

    public function getDisplayName()
    {
        return $this->getName();
    }

    public function getRabbitMQData() {
        $keys = $this->getKeys();

        return [
            'location' => $keys['rabbitmq_location'],
            'username' => $keys['rabbitmq_user'],
            'password' => $keys['rabbitmq_password'],
            'cacert'   => $keys['rabbitmq_cacert'],
            'cert'     => $keys['rabbitmq_cert'],
            'key'      => $keys['rabbimtq_key']
        ];
    }

    public function getLocation() {
        $keys = $this->getKeys();

        return $keys['rabbitmq_location'];
    }

    public function getUser() 
    {
        $keys = $this->getKeys();

        return $keys['rabbitmq_user'];
    }

    public function getPassword() 
    {
        $keys = $this->getKeys();

        return $keys['rabbitmq_password'];
    }

    public function getCacert(){
        $keys = $this->getKeys();
        return $keys['rabbitmq_cacert'];
    }

    public function getCert(){
        $keys = $this->getKeys();
        return $keys['rabbitmq_cert'];
    }

    public function getKey(){
        $keys = $this->getKeys();
        return $keys['rabbitmq_key'];
    }

    public function getAuthenticationType()
    {
        return 'key';
    }

    /**
     * Defines the additional key fields required by the plugin.
     * @return array Array of key fields.
     */
    public function getRequiredKeyFields()
    {
        return [
            'rabbitmq_location' => 'mautic.rabbitmq.config.location',
            'rabbitmq_user' => 'mautic.rabbitmq.config.user',
            'rabbitmq_password'  => 'mautic.rabbitmq.config.password',
            'rabbitmq_cacert'   => 'mautic.rabbitmq.config.cacert',
            'rabbitmq_cert' => 'mautic.rabbitmq.config.cert',
            'rabbitmq_key'  => 'mautic.rabbitmq.config.key',
        ];
    }

    /**
     * Defines which key fields are secret from the array returned from getRequiredKeyFields.
     * @return array Array of secret key fields.
     */
    public function getSecretKeys()
    {
        return [
            'rabbitmq_password'
        ];
    }

    /**
     * The field map should be defined here, the keys are the MA field names, while the values are the standardized values in RabbitMQ. 
     * @return array Field map array.
     */
    public function getFieldMap() 
    {
        return [
            'email' => 'email',
            'firstname' => 'first_name',
            'lastname' => 'last_name',
            'mobile' => 'mobile',
            'gender' => 'gender',
            'birthday' => 'birthday',
            'points' => 'points',
            'stage' => 'stage'
        ];
    }

    /**
    * The address field map used for populating address object should be defined here, the keys are the MA field names, while the values are the standardized values in RabbitMQ.
    * @return array address field map array
    */

    public function getAddressFieldMap(){
        //Commented lines are not defined yet
        return [
            "country"=>"country",
            //""=>"country_code", 
            "state"=>"state",
            //""=>"state_code",
            //""=>"county",
            "city"=>"city",
            "zipcode"=>"zip_code",
            "address1"=>"address_line1",
            "address2"=>"address_line2"
        ];
    }

    /**
     * Format the lead data to the structure that RabbitMQ requires.
     *
     * @param array The data we want to format.
     * @param bool Set to true if you want to convert MA format to RabbitMQ format. Set to false if you want to convert RabbitMQ format to MA format.
     * 
     * @return array
     */
    public function formatData($data, $to_standard = true)
    {

        $fieldMap = $this->getFieldMap();

        if(!$to_standard){
            $fieldMap = array_flip($fieldMap);
        }

        $formattedLeadData = array();

        foreach ($data as $key => $value) {
            if(isset($fieldMap[$key])){
                $formattedLeadData[$fieldMap[$key]] = $value;
            }
        }
        if($to_standard)
            $formattedLeadData['address'] = $this->formatAddressData($data, $to_standard);
        else if(isset($data['address'])){
            $formattedLeadData = array_merge($formattedLeadData, $this->formatAddressData($data['address'], $to_standard));
        }

        if(!$to_standard && isset($data['stage'])){
            if(isset($data['stage'])){
                $stages = $this->em->getRepository('MauticStageBundle:Stage')->findBy(['name'=>$data['stage']]);
                
                if(count($stages)>0){
                    $formattedLeadData['stage']=$stages[0]->getId();
                }else{
                    unset($formattedLeadData['stage']);
                }
            }else{
                unset($formattedLeadData['stage']);
            }
        }
        
        return $formattedLeadData;
        
    }

    public function formatAddressData($data, $to_standard = true)
    {

        $addressFieldMap = $this->getAddressFieldMap();

        if(!$to_standard){
            $addressFieldMap = array_flip($addressFieldMap);
        }

        $formattedLeadData = array();

        foreach ($data as $key => $value) {
            if(isset($addressFieldMap[$key])){
                $formattedLeadData[$addressFieldMap[$key]] = $value;
            }
        }

        return $formattedLeadData;
    }
}