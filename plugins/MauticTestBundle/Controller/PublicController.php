<?php

namespace MauticPlugin\MauticTestBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;

class PublicController extends CommonController
{
    public function builderAction()
    {
        return $this->render('MauticTestBundle:Default:index.html.php');
    }
}
