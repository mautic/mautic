<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Monolog\Handler;

use Monolog\Handler\StreamHandler;

/**
 * Class PhpHandler.
 */
class PhpHandler extends StreamHandler
{
    /**
     * @var string
     */
    private $errorMessage;

    /**
     * {@inheritdoc}
     *
     * @author Jordi Boggiano <j.boggiano@seld.be>
     */
    protected function write(array $record)
    {
        //check to see if the resource has anything written to it
        if (!is_resource($this->stream)) {
            if (!$this->url) {
                throw new \LogicException('Missing stream url, the stream can not be opened. This may be caused by a premature call to close().');
            }
            $this->errorMessage = null;
            set_error_handler([$this, 'customErrorHandler']);
            if (!file_exists($this->url)) {
                $this->stream = fopen($this->url, 'a');

                //write php line to it
                fwrite($this->stream, (string) '<?php die("access denied!"); ?>'."\n\n");
            } else {
                $this->stream = fopen($this->url, 'a');
            }

            if ($this->filePermission !== null) {
                @chmod($this->url, $this->filePermission);
            }
            restore_error_handler();
            if (!is_resource($this->stream)) {
                $this->stream = null;
                throw new \UnexpectedValueException(sprintf('The stream or file "%s" could not be opened: '.$this->errorMessage, $this->url));
            }
        }

        parent::write($record);
    }

    /**
     * @author Jordi Boggiano <j.boggiano@seld.be>
     *
     * @param string $code
     * @param string $msg
     */
    private function customErrorHandler($code, $msg)
    {
        $this->errorMessage = preg_replace('{^fopen\(.*?\): }', '', $msg);
    }
}
