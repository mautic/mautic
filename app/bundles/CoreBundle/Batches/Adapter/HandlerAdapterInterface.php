<?php

namespace Mautic\CoreBundle\Batches\Adapter;

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
     * @param object $object
     */
    public function update($object);

    /**
     * Persist every object
     *
     * @param object[] $objects
     */
    public function store(array $objects);
}