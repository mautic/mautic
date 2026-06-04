<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\ApiPlatform\EventListener;

use ApiPlatform\Metadata\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\Util\RequestAttributesExtractor;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class MauticDenyAccessListener
{
    public function __construct(
        private CorePermissions $security,
        private ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory)
    {
    }

    public function onSecurity(RequestEvent $event): void
    {
        $this->checkSecurity($event->getRequest());
    }

    /**
     * @throws ResourceClassNotFoundException
     */
    private function checkSecurity(Request $request): void
    {
        if (!$attributes = RequestAttributesExtractor::extractAttributes($request)) {
            return;
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($attributes['resource_class']);
        $operation        = $resourceMetadata->getOperation($attributes['operation_name']);
        $isGranted        = $operation->getSecurity() ?? null;

        if (null === $isGranted) {
            return;
        }

        // Extract object path to getCreatedBy - () parenthesis at the end
        preg_match('#\((.*?)\)#', $isGranted, $match);
        $objectProperty = null;
        if (count($match) > 1) {
            $objectProperty = $match[1];
        }
        if ($startParenthesis = strpos($isGranted, '(')) {
            $isGranted = substr($isGranted, 0, $startParenthesis);
        }

        // Extract id from object - [] parenthesis in the text
        preg_match('#\[(.*?)\]#', $isGranted, $match);
        $objectIdProperty = null;
        if (count($match) > 1) {
            $objectIdProperty = $match[1];
        }
        if (str_contains($isGranted, '[') && str_contains($isGranted, ']')) {
            $startParenthesis = strpos($isGranted, '[');
            $stopParenthesis  = strpos($isGranted, ']');
            if ($request->getContent()
                && ($contentArray = json_decode($request->getContent(), true))
                && is_array($contentArray)
                && array_key_exists($objectIdProperty, $contentArray)
            ) {
                $url      = $contentArray[$objectIdProperty];
                $objectId = substr($url, strrpos($url, '/') + 1);
            } else {
                $requestObject = $request->attributes->get('data');
                $property      = 'get'.$objectIdProperty;
                $objectId      = $requestObject->$property()->getId();
            }
            $isGranted = substr($isGranted, 0, $startParenthesis).$objectId.substr($isGranted, $stopParenthesis + 1);
        }

        // Get the object to check the security
        $requestObject = $request->attributes->get('data');
        if (null !== $objectProperty) {
            $objectPropertyList = explode('.', $objectProperty);
            foreach ($objectPropertyList as $property) {
                $requestObject = $requestObject->$property();
            }
        }

        // Extract isGranted and action
        $isGranted     = str_replace('"', '', $isGranted);
        $isGranted     = str_replace("'", '', $isGranted);
        $isGrantedList = explode(':', $isGranted);
        $action        = array_pop($isGrantedList);

        if (in_array($action, ['view', 'edit', 'delete'])) {
            if (!$this->security->hasEntityAccess($isGranted.'own', $isGranted.'other', $requestObject->getCreatedBy())) {
                throw new AccessDeniedException();
            }
        } else {
            if (!$this->security->isGranted($isGranted)) {
                throw new AccessDeniedException();
            }
        }
    }
}
