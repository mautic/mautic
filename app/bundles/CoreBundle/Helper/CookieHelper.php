<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;

/**
 * Class CookieHelper
 *
 * @package Mautic\CoreBundle\Helper
 */
class CookieHelper
{
    private $path = null;
    private $domain = null;
    private $secure = false;
    private $httponly = false;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->path = $factory->getParameter('cookie_path');
        $this->domain = $factory->getParameter('cookie_domain');
        $this->secure = $factory->getParameter('cookie_secure');

        if ($this->secure == '' || $this->secure == null) {
            $this->secure = ( $factory->getRequest()->server->get('HTTPS', false));
        }

        $this->httponly = $factory->getParameter('cookie_httponly');
    }

    /**
     * @param      $name
     * @param      $value
     * @param int  $expire
     * @param null $path
     * @param null $domain
     * @param null $secure
     * @param bool $httponly
     */
    public function setCookie($name, $value, $expire = 1800, $path = null, $domain = null, $secure = null, $httponly = true)
    {
        setcookie(
            $name,
            $value,
            ($expire) ? time() + $expire : null,
            ($path == null) ? $this->path : $path,
            ($domain == null) ? $this->domain : $domain,
            ($secure == null) ? $this->secure : $secure,
            ($httponly == null) ? $this->httponly : $httponly
        );
    }

    /**
     * Deletes a cookie by expiring it
     *
     * @param           $name
     * @param null      $path
     * @param null      $domain
     * @param null      $secure
     * @param bool|true $httponly
     */
    public function deleteCookie($name, $path = null, $domain = null, $secure = null, $httponly = true)
    {
        $this->setCookie($name, '', time() - 3600, $path, $domain, $secure, $httponly);
    }
}