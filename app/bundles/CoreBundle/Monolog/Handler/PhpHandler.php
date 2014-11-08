<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */


namespace Mautic\CoreBundle\Monolog\Handler;

use Monolog\Handler\StreamHandler;

class PhpHandler extends StreamHandler
{
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
            set_error_handler(array($this, 'customErrorHandler'));
            $this->stream = fopen($this->url, 'a');
            if ($this->filePermission !== null) {
                @chmod($this->url, $this->filePermission);
            }
            restore_error_handler();
            if (!is_resource($this->stream)) {
                $this->stream = null;
                throw new \UnexpectedValueException(sprintf('The stream or file "%s" could not be opened: '.$this->errorMessage, $this->url));
            }


            //check to see if the resource has anything written to it
            if (filesize($this->url) === 0) {
                //write php line to it
                fwrite($this->stream, (string) '<?php die("access denied!"); ?>' . "\n\n");
            }
        }

        parent::write($record);

    }

    /**
     * @author Jordi Boggiano <j.boggiano@seld.be>
     *
     * @param $code
     * @param $msg
     */
    private function customErrorHandler($code, $msg)
    {
        $this->errorMessage = preg_replace('{^fopen\(.*?\): }', '', $msg);
    }
}
