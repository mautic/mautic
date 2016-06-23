<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Event;

use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Security\Authentication\Token\PluginToken;
use Mautic\UserBundle\Security\Provider\UserProvider;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Class AuthenticationContentEvent
 */
class AuthenticationContentEvent extends Event
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var array
     */
    protected $content = array();

    /**
     * @var bool
     */
    protected $postLogout = false;

    /**
     * AuthenticationContentEvent constructor.
     *
     * @param Request $request
     */
    public function __construct(Request $request) {
        $this->request               = $request;
        $this->postLogout = $request->getSession()->get('post_logout', false);
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return bool
     */
    public function isLogout()
    {
        return $this->postLogout;
    }

    /**
     * @param $content
     */
    public function addContent($content)
    {
        $this->content[] = $content;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return implode("\n\n", $this->content);
    }
}