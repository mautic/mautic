<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SocialBundle\Network;

class InstagramNetwork extends AbstractNetwork
{

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'Instagram';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getIdentifierField()
    {
        return 'instagram';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getOAuthLoginUrl()
    {
        return $this->factory->getRouter()->generate('mautic_social_callback', array('network' => $this->getName()));
    }

    /**
     * {@inheritdoc}
     *
     * @return bool|string
     */
    public function getAccessTokenUrl()
    {
        return 'https://api.instagram.com/oauth2/token';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function oAuthCallback($clientId = '', $clientSecret = '')
    {
        $url      = $this->getAccessTokenUrl();
        $keys     = $this->settings->getApiKeys();

        if (!$url || !isset($keys['clientId']) || !isset($keys['clientSecret'])) {
            return array(false, $this->factory->getTranslator()->trans('mautic.social.missingkeys'));
        }

        $bearer = $this->getBearerToken($keys);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Basic {$bearer}",
            "Content-Type: application/x-www-form-urlencoded;charset=UTF-8"
        ));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');

        $data = curl_exec($ch);

        //get the body response
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body        = substr($data, $header_size);

        curl_close($ch);

        $values = json_decode($body, true);

        //check to see if an entity exists
        $entity = $this->getSettings();
        if ($entity == null) {
            $entity = new SocialNetwork();
            $entity->setName($this->getName());
        }

        if (isset($values['access_token'])) {
            $keys['access_token'] = $values['access_token'];
            $error = false;
        } else {
            $error = $this->parseResponse($values);
        }

        $entity->setApiKeys($keys);

        //save the data
        $em = $this->factory->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return array($entity, $error);
    }

    /**
     * Generate a Twitter bearer token
     *
     * @param $keys
     * @return string
     */
    private function getBearerToken($keys)
    {
        //Per Twitter's recommendations
        $consumer_key    = rawurlencode($keys['clientId']);
        $consumer_secret = rawurlencode($keys['clientSecret']);

        return base64_encode($consumer_key . ':' . $consumer_secret);
    }

    /**
     * {@inheritdoc}
     *
     * @param $response
     * @return string
     */
    public function getErrorsFromResponse($response)
    {
        if (is_array($response) && isset($response['errors'])) {
            $errors = array();
            foreach ($response->errors as $e) {
                $errors[] = $e['message'] . ' ('.$e['code'].')';
            }

            return implode('; ', $errors);
        }

        return '';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return array(
            'clientId'      => 'mautic.social.keyfield.clientid',
            'clientSecret'  => 'mautic.social.keyfield.clientsecret'
        );
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'oauth2';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getSupportedFeatures()
    {
        return array(
            'public_profile',
            'public_activity',
            'suggestion_matching'
        );
    }
}