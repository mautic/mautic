<?php

declare(strict_types=1);

namespace Mautic\NotificationBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Controller\AjaxLookupControllerTrait;

final class AjaxController extends CommonAjaxController
{
    use AjaxLookupControllerTrait;
}
