<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticMessengerBundle\Helper;

use Mautic\CoreBundle\Exception as MauticException;
use Joomla\Http\Http;
use Symfony\Component\HttpFoundation\RequestStack;
use Mautic\CoreBundle\Helper\CoreParametersHelper;

class MessengerHelper
{
    /**
     * @var Http $connector ;
     */
    protected $connector;

    /**
     * @var RequestStack $request ;
     */
    protected $request;

    /**
     * @var CoreParametersHelper $coreParameterHelper
     */
    protected $coreParameterHelper;


    public function __construct(
        Http $connector,
        RequestStack $request,
        CoreParametersHelper $coreParametersHelper
    ) {
        $this->connector = $connector;
        $this->request = $request->getCurrentRequest();
        $this->coreParameterHelper = $coreParametersHelper;
    }

    public function subscribeApp()
    {
        try {
            $data = $this->connector->get(
                'https://graph.facebook.com/v2.9/me/subscribed_apps?access_token='.$this->coreParameterHelper->getParameter(
                    'pageAccessTokken'
                ),
                [],
                10
            );
            if($data->success == true){
                return true;
            }
        } catch (\Exception $e) {
            //nothing
        }

        return false;
    }


}