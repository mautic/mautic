<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Swiftmailer\Transport;

class MandrillTransport extends \Swift_SmtpTransport
{
    /**
     * Create a new SmtpTransport, optionally with $host, $port and $security.
     *
     * @param string  $host
     * @param int     $port
     * @param string  $security
     */
    public function __construct($host = 'localhost', $port = 25, $security = null)
    {
        parent::__construct('smtp.mandrillapp.com', 587, 'tls');
    }
}