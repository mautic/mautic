<?php

namespace Mautic\CoreBundle\Helper;

use Symfony\Component\HttpFoundation\RequestStack;

class CookieHelper
{
    const SAME_SITE       = '; SameSite=';
    const SAME_SITE_VALUE = 'None';
    private $path;
    private $domain;
    private $secure       = true;
    private $httponly     = false;
    private $request;

    /**
     * @param $cookiePath
     * @param $cookieDomain
     * @param $cookieSecure
     * @param $cookieHttp
     */
    public function __construct($cookiePath, $cookieDomain, $cookieSecure, $cookieHttp, RequestStack $requestStack)
    {
        $this->path     = $cookiePath;
        $this->domain   = $cookieDomain;
        $this->secure   = $cookieSecure;
        $this->httponly = $cookieHttp;

        $this->request = $requestStack->getCurrentRequest();
        if (('' === $this->secure || null === $this->secure) && $this->request) {
            $this->secure = $requestStack->getCurrentRequest()->isSecure();
        }
    }

    /**
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getCookie($key, $default = null)
    {
        if (null === $this->request) {
            return $default;
        }

        return $this->request->cookies->get($key, $default);
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
    public function setCookie($name, $value, $expire = 1800, $path = null, $domain = null, $secure = null, $httponly = null)
    {
        if (null == $this->request || (defined('MAUTIC_TEST_ENV') && MAUTIC_TEST_ENV)) {
            return true;
        }

        // If https, SameSite equals None
        $sameSiteNoneText             = '';
        $sameSiteNoneTextGreaterPhp73 = null;
        if (true === $secure or (null === $secure and true === $this->secure)) {
            $sameSiteNoneText             = self::SAME_SITE.self::SAME_SITE_VALUE;
            $sameSiteNoneTextGreaterPhp73 = self::SAME_SITE_VALUE;
        }

        if (version_compare(phpversion(), '7.3', '>=')) {
            setcookie(
                $name,
                $value,
                [
                    'expires'  => ($expire) ? (int) (time() + $expire) : null,
                    'path'     => ((null == $path) ? $this->path : $path),
                    'domain'   => (null == $domain) ? $this->domain : $domain,
                    'secure'   => (null == $secure) ? $this->secure : $secure,
                    'httponly' => (null == $httponly) ? $this->httponly : $httponly,
                    'samesite' => $sameSiteNoneTextGreaterPhp73,
                ]
            );
        } else {
            setcookie(
                $name,
                $value,
                ($expire) ? (int) (time() + $expire) : null,
                ((null == $path) ? $this->path : $path).$sameSiteNoneText,
                (null == $domain) ? $this->domain : $domain,
                (null == $secure) ? $this->secure : $secure,
                (null == $httponly) ? $this->httponly : $httponly
            );
        }
    }

    /**
     * Deletes a cookie by expiring it.
     *
     * @param           $name
     * @param null      $path
     * @param null      $domain
     * @param null      $secure
     * @param bool|true $httponly
     */
    public function deleteCookie($name, $path = null, $domain = null, $secure = null, $httponly = null)
    {
        $this->setCookie($name, '', -86400, $path, $domain, $secure, $httponly);
    }
}
