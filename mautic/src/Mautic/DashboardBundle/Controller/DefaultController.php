<?php

namespace Mautic\DashboardBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('MauticDashboardBundle:Default:index.html.php');
    }
}
