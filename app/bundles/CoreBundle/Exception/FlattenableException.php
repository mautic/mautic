<?php

namespace Mautic\CoreBundle\Exception;

abstract class FlattenableException extends \Exception
{
    /**
     * @return array{'message': string, 'file': string, 'line': int, 'trace': string}
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'file'    => $this->getFile(),
            'line'    => $this->getLine(),
            'trace'   => $this->getTraceAsString(),
        ];
    }
}
