<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\EventListener;

use FM\ElfinderBundle\Event\ElFinderPostExecutionEvent;
use FM\ElfinderBundle\Event\ElFinderPreExecutionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ElFinderSanitizeSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ElFinderPreExecutionEvent::class  => ['onPreExecute', 10],
            ElFinderPostExecutionEvent::class => ['onPostExecute', 0],
        ];
    }

    public function onPreExecute(ElFinderPreExecutionEvent $event): void
    {
        $request = $event->getRequest();
        $request->query->replace(
            $this->sanitizeArray($request->query->all())
        );
    }

    public function onPostExecute(ElFinderPostExecutionEvent $event): void
    {
        $result = $event->getResult();

        if (isset($result['error']) && is_array($result['error'])) {
            $result['error'] = $this->sanitizeArray($result['error']);
            $event->setResult($result);
        }
    }

    /**
     * Strip potentially malicious characters from an array.
     *
     * @param array<string, int|string|null> $params
     *
     * @return array<string, int|string|null>
     */
    private function sanitizeArray(array $params): array
    {
        $clean = [];

        foreach ($params as $key => $value) {
            $clean[$key] = is_string($value) ? $this->sanitizeValue($value) : $value;
        }

        return $clean;
    }

    /**
     * Strip potentially malicious characters from a string.
     */
    private function sanitizeValue(string $value): string
    {
        // Step 1: Normalize
        $clean = preg_replace('/\0+/', '', $value);
        if (empty($clean)) {
            return '';
        }
        $clean = mb_convert_encoding($clean, 'UTF-8', 'UTF-8');

        // Step 2: Decode encoded payloads - without dropping content
        $clean = rawurldecode($clean);
        $clean = rawurldecode($clean); // catch double-encoded attacks

        // Step 3: Remove only the dangerous parts, not the whole string
        $clean = preg_replace(
            [
                '#<\s*(script|iframe|object|embed|svg|style|meta|link)[^>]*?>.*?<\s*/\s*\1>#is',
                '#<\s*(script|iframe|object|embed|svg|style|meta|link)[^>]*?>#is',
                '#on\w+\s*=\s*([\'"]).*?\1#is',   // event handlers only
                '#javascript\s*:#is',
                '#data\s*:\s*text/html#is',
            ],
            '',
            $clean
        );

        // Step 4: Encode final safe output (keeps text, removes execution)
        return htmlspecialchars(
            $clean,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }
}
