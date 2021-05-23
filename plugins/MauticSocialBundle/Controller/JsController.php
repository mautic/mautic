<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\Response;

class JsController extends CommonController
{
    /**
     * @return Response
     */
    public function generateAction($formName)
    {
        $js = <<<JS

    function openOAuthWindow(authUrl){
        if (authUrl) {
            var generator = window.open(authUrl, 'integrationauth', 'height=500,width=500');
            if (!generator || generator.closed || typeof generator.closed == 'undefined') {
                alert(mauticLang.popupBlockerMessage);
            }
        }
    }
    
    function postAuthCallback(response){
        var elements = document.getElementById("mauticform_{$formName}").elements;
        var field, fieldName;
        values = JSON.parse(JSON.stringify(response));
        
        for (var i = 0, element; element = elements[i++];) {
            field = element.name
            fieldName = field.replace("mauticform[","");
            fieldName = fieldName.replace("]","");
            var element = document.getElementsByName("mauticform["+fieldName+"]");
            
            // Remove underscores, dashes, and f_ prefix for comparison
            fieldName = fieldName.replace("f_", "").replace(/_/g,"").replace(/-/g, "");
            for(var key in values) {
                var compareKey = key.replace(/_/g,"").replace(/-/g, "");
                if (key != 'id' && (key.indexOf(fieldName) >= 0 || fieldName.indexOf(key) >= 0) && element[0].value == "") {
                    if (values[key].constructor === Array && values[key][0].value) {
                        element[0].value = values[key][0].value;
                    } else {
                        element[0].value = values[key];
                    }
                    
                    break;
                }
            }
        }
    }
JS;

        return new Response(
            $js,
            200,
            [
                'Content-Type'           => 'application/javascript',
            ]
        );
    }
}
