<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Swiftmailer\Transport;

/**
 * Class AmazonTransport
 */
class AmazonTransport extends \Swift_SmtpTransport
{
    /**
     * {@inheritdoc}
     */
    public function __construct($host = 'localhost', $port = 25, $security = null)
    {
        parent::__construct('email-smtp.us-east-1.amazonaws.com', 465, 'tls');
    }
}
