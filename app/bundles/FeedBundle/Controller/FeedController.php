<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Webmecanik
 * @link        http://webmecanik.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FeedBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Symfony\Component\HttpFoundation\Response;
use Mautic\FeedBundle\Helper\FeedHelper;

/**
 * Class FeedController
 */
class FeedController extends FormController
{

    /**
     * Generates the default view
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        // Write the contents of the XML file into a string
        $xmlString = file_get_contents('feed.xml');

        /** @var FeedHelper $feedHelper  */
        $feedHelper = $this->get('mautic.helper.feed');

        $feedContent = $feedHelper->getFeedContentFromString($xmlString);

        return new Response(var_dump($feedContent));

//         return $this->delegateView(array(
//             'contentTemplate' => 'MauticFeedBundle:Feed:hello.html.php'
//         ));
    }
}
