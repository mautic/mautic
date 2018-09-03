<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Auth\Oauth1a;

interface CredentialsInterface
{
    /**
     * @return string
     */
    public function getAuthUrl();

    /**
     * @return string
     */
    public function getConsumerKey();

    /**
     * @return string
     */
    public function getConsumerSecret();

    /**
     * Return empty string for two-legged OAuth.
     * 
     * @return string
     */
    public function getToken();

    /**
     * Return empty string for two-legged OAuth.
     * 
     * @return string
     */
    public function getTokenSecret();
}
