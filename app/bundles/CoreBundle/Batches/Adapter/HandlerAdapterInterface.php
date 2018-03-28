<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Batches\Adapter;

use Mautic\CoreBundle\Batches\Exception\BatchActionFailException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Via this interface you are able to define how to work with source.
 */
interface HandlerAdapterInterface
{
    /**
     * Load needed parameters.
     *
     * @param array $parameters
     */
    public function loadSettings(array $parameters);

    /**
     * Get parameters from request
     *
     * @param Request $request
     *
     * @return array
     */
    public function getParameters(Request $request);

    /**
     * Update objects by loaded settings.
     *
     * Parameter object's class depends on source. Keep logic of update in another private method. Inside this method should be only a if checking instance and call of these private methods.
     * In case of not implemented source, throw an exception documented bellow.
     *
     * @param object $object
     *
     * @throws BatchActionFailException
     */
    public function update($object);

    /**
     * Persist every object
     *
     * @param object[] $objects
     */
    public function store(array $objects);
}