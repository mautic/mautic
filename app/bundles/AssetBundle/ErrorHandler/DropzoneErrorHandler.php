<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\ErrorHandler;

use Exception;
use Oneup\UploaderBundle\Uploader\ErrorHandler\ErrorHandlerInterface;
use Oneup\UploaderBundle\Uploader\Response\AbstractResponse;

class DropzoneErrorHandler implements ErrorHandlerInterface
{
    public function addException(AbstractResponse $response, Exception $exception): void
    {
        // HTTP status between 400 and 500 should be set here.
        // Dropzone will handle error messages itself then.
        // Unfortunatelly UploaderBundle will have this option in v 3.
        $response['error'] = $exception->getMessage();
    }
}
