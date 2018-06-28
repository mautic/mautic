<?php

namespace MauticPlugin\MauticIntegrationsBundle\Services;
use MauticPlugin\MauticIntegrationsBundle\DAO\VariableEncodeDAO;

/**
 * Interface VariableExpressorHelperInterface
 * @package MauticPlugin\MauticIntegrationsBundle\Services
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
