<?php

namespace Mautic\CoreBundle\Batches\Adapter;

use Mautic\CoreBundle\Batches\Exception\BatchActionFailException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Via this interface you are able to define how to work with source.
 *
 * @author David Vurbs <david.vurbs@mautic.com>
 */
interface HandlerAdapterInterface
{
    /**
     * Startup method with container. It is called right after a batch action is in run (before any other method on this object will be called).
     *
     * @param ContainerInterface $container
     */
    public function startup(ContainerInterface $container);

    /**
     * Load parameters from request.
     *
     * @param Request $request
     */
    public function loadSettings(Request $request);

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