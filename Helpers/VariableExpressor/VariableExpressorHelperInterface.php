<?php

namespace MauticPlugin\MauticIntegrationsBundle\Helpers\VariableExpressor;

use MauticPlugin\MauticIntegrationsBundle\DAO\VariableEncodeDAO;

/**
 * Interface VariableExpressorHelperInterface
 * @package MauticPlugin\MauticIntegrationsBundle\Helpers
 */
interface VariableExpressorHelperInterface
{
    /**
     * @param VariableEncodeDAO $variableEncodeDAO
     *
     * @return mixed
     */
    public function decodeVariable(VariableEncodeDAO $variableEncodeDAO);

    /**
     * @param mixed $var
     * @return VariableEncodeDAO
     */
    public function encodeVariable($var);
}
