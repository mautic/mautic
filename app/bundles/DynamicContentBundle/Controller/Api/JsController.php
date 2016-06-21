<?php
/**
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\DynamicContentBundle\Controller\Api;

use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\JsonResponse;

class JsController extends CommonController
{
    public function generateAction()
    {
        $slot = $this->request->query->get('slot');

        $response = $this->forward('MauticDynamicContentBundle:Api\DynamicContentApi:process', ['objectAlias' => $slot]);

        if ($response->getStatusCode() !== 200) {
            return new JsonResponse(['failed' => 'Please specify a slot.']);
        }

        //replace line breaks with literal symbol and escape quotations
        $search  = array("\n", '"');
        $replace = array('\n', '\"');
        $content = str_replace($search, $replace, $response->getContent());

        $js = <<<JS
var MauticJS = MauticJS || {};
MauticJS.documentReady = function(f) {
    /in/.test(document.readyState) ? setTimeout('MauticJS.documentReady(' + f + ')', 9) : f();
};
MauticJS.documentReady(function(){
    document.getElementById('mautic-slot-{$slot}').innerHTML = "{$content}";
});
JS;
        $response->setContent($js);
        $response->headers->set('Content-Type', 'text/javascript');

        return $response;
    }
}
