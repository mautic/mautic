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
        /** @var \Debril\RssAtomBundle\Protocol\Parser\Factory $factory */
        $factory = $this->container->get('debril.parser.factory');

        /** @var \Debril\RssAtomBundle\Protocol\Parser\XmlParser $xmlParser */
        $xmlParser = $this->container->get('debril.parser.xml');

        /** @var \Debril\RssAtomBundle\Protocol\FeedReader $reader */
        $reader = $this->container->get('debril.reader');

        // Write the contents of the XML file into a string
        $xmlContents = file_get_contents('feed.xml');

        // Parses the contents into a SimpleXMLElement
        $xmlBody = $xmlParser->parseString($xmlContents);

        // Finds the appropriate parser for the given feed
        $parser = $reader->getAccurateParser($xmlBody);

        // Parses the feed with the correct parser
        $feedContent = $parser->parse($xmlBody, $factory->newFeed());

        return new Response(var_dump($feedContent));

//         return $this->delegateView(array(
//             'contentTemplate' => 'MauticFeedBundle:Feed:hello.html.php'
//         ));
    }
}