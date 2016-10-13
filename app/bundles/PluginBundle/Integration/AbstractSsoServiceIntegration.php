<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Integration;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Used by SSO auth plugins that use OAuth2, etc means of logins.
 *
 * Class AbstractSsoIntegration
 */
abstract class AbstractSsoServiceIntegration extends AbstractIntegration
{
    /**
     * Called after the user is authenticated with the 3rd party service to obtain the users
     * details.
     *
     * @param $response mixed Typically the response from request to authenticating service
     *
     * @return mixed
     */
    abstract public function getUser($response);

    /**
     * Get the user role for new users.
     *
     * @return bool|\Doctrine\Common\Proxy\Proxy|null|object
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function getUserRole()
    {
        $featureSettings = $this->settings->getFeatureSettings();

        $role = (isset($featureSettings['new_user_role'])) ? $featureSettings['new_user_role'] : false;

        if ($role) {
            return $this->factory->getEntityManager()->getReference('MauticUserBundle:Role', $role);
        }

        throw new AuthenticationException('mautic.integration.sso.error.no_role');
    }

    /**
     * Returns if a new user should be created if authenticated and not found locally.
     *
     * @return bool
     */
    public function shouldAutoCreateNewUser()
    {
        $featureSettings = $this->settings->getFeatureSettings();

        return (isset($featureSettings['auto_create_user'])) ? (bool) $featureSettings['auto_create_user'] : false;
    }

    /**
     * Set the callback URL to sso_login.
     */
    public function getAuthCallbackUrl()
    {
        return $this->factory->getRouter()->generate('mautic_sso_login_check',
            ['integration' => $this->getName()],
            true //absolute
        );
    }

    /**
     * @param array $settings
     * @param array $parameters
     *
     * @return bool|string
     */
    public function ssoAuthCallback($settings = [], $parameters = [])
    {
        $response = $this->authCallback($settings, $parameters);

        // Get user data
        return $this->getUser($response);
    }

    /**
     * Don't save the keys as they are only used to validate user login.
     *
     * @param      $data
     * @param null $tokenOverride
     *
     * @return array
     */
    public function extractAuthKeys($data, $tokenOverride = null)
    {
        // Prepare the keys for extraction such as renaming, setting expiry, etc
        $data = $this->prepareResponseForExtraction($data);

        //parse the response
        $authTokenKey = ($tokenOverride) ? $tokenOverride : $this->getAuthTokenKey();
        if (is_array($data) && isset($data[$authTokenKey])) {
            return $data;
        }

        $error = $this->getErrorsFromResponse($data);
        if (empty($error)) {
            $error = $this->factory->getTranslator()->trans('mautic.integration.error.genericerror', [], 'flashes');
        }

        throw new AuthenticationException($error);
    }

    /**
     * @return array
     */
    public function getSupportedFeatures()
    {
        return [
            'sso_service',
        ];
    }

    /**
     * Get form settings; authorization is not needed since it is done when a user logs in.
     *
     * @return array
     */
    public function getFormSettings()
    {
        return [
            'requires_callback'      => true,
            'requires_authorization' => false,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param Form|\Symfony\Component\Form\FormBuilder $builder
     * @param array                                    $data
     * @param string                                   $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ($formArea == 'features') {
            $builder->add('auto_create_user',
                'yesno_button_group',
                [
                    'label' => 'mautic.integration.sso.auto_create_user',
                    'data'  => (isset($data['auto_create_user'])) ? (bool) $data['auto_create_user'] : false,
                    'attr'  => [
                        'tooltip' => 'mautic.integration.sso.auto_create_user.tooltip',
                    ],
                ]
            );

            $builder->add(
                'new_user_role',
                'role_list',
                [
                    'label'      => 'mautic.integration.sso.new_user_role',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'   => 'form-control',
                        'tooltip' => 'mautic.integration.sso.new_user_role.tooltip',
                    ],
                ]
            );
        }
    }
}
