<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Unit\Twig;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Regression test for https://github.com/mautic/mautic/issues/17320.
 *
 * The "Email sent" and "Email read" timeline entries are rendered by the same
 * template (@MauticEmail/SubscribedEvents/Timeline/index.html.twig) against the
 * same email_stats row. The template must only show the "was first read"/"not
 * read yet" text for the 'read' entry, and the interval it displays must always
 * be the diff between dateSent and dateRead, not the entry's own timestamp.
 */
final class TimelineEventTemplateTest extends TestCase
{
    private const TEMPLATE = 'index.html.twig';

    private Environment $twig;

    protected function setUp(): void
    {
        $templateDir = dirname(__DIR__, 3).'/Resources/views/SubscribedEvents/Timeline';
        $this->twig  = new Environment(new FilesystemLoader($templateDir));

        // Minimal stand-ins for the real Twig extensions Mautic wires into the container.
        $this->twig->addFunction(new TwigFunction('dateToFull', static fn ($datetime): string => $datetime instanceof \DateTimeInterface ? $datetime->format('F j, Y g:i a') : (string) $datetime));

        $this->twig->addFunction(new TwigFunction('dateFormatRange', static function (\DateInterval $interval): string {
            $units    = ['y' => 'year', 'm' => 'month', 'd' => 'day', 'h' => 'hour', 'i' => 'minute', 's' => 'second'];
            $formated = [];
            foreach ($units as $property => $label) {
                if ($interval->$property) {
                    $formated[] = $interval->$property.' '.$label.(1 === $interval->$property ? '' : 's');
                }
            }

            return [] === $formated ? 'Less than 1 second' : implode(' ', $formated);
        }));

        $this->twig->addFunction(new TwigFunction('dateToText', static fn ($datetime): string => (string) $datetime));

        $this->twig->addFunction(new TwigFunction('path', static fn (string $name, array $params = []): string => $name));

        $this->twig->addFilter(new TwigFilter('purify', static fn (?string $html): string => (string) $html));

        // Fake translator: returns the translation key followed by the interpolated
        // parameter values, so assertions can check both which key was chosen and
        // the interpolated values (real messages.ini strings aren't loaded here).
        $this->twig->addFilter(new TwigFilter('trans', static fn (string $key, array $params = []): string => $key.([] === $params ? '' : ' '.implode(' ', $params))));
    }

    public function testSentEntryDoesNotShowAnyReadStatusText(): void
    {
        $dateSent = new \DateTime('2026-09-08 10:00:00');
        $dateRead = (clone $dateSent)->modify('+22 hours +6 minutes +5 seconds');

        // Mirrors LeadSubscriber::addEmailEvents(): for the 'sent' state, timestamp is dateSent itself.
        $html = $this->render('sent', $dateSent, $dateRead, $dateSent);

        $this->assertStringNotContainsString('mautic.email.timeline.event.read', $html);
        $this->assertStringNotContainsString('mautic.email.timeline.event.not.read', $html);
        $this->assertStringNotContainsString('Less than 1 second', $html);
    }

    public function testSentEntryShowsNothingEvenWhenEmailWasNotYetRead(): void
    {
        $dateSent = new \DateTime('2026-09-08 10:00:00');

        $html = $this->render('sent', $dateSent, null, $dateSent);

        $this->assertStringNotContainsString('mautic.email.timeline.event.not.read', $html);
    }

    public function testReadEntryShowsTheRealIntervalBetweenSentAndRead(): void
    {
        $dateSent = new \DateTime('2026-09-08 10:00:00');
        $dateRead = (clone $dateSent)->modify('+22 hours +6 minutes +5 seconds');

        // Mirrors LeadSubscriber::addEmailEvents(): for the 'read' state, timestamp is dateRead.
        $html = $this->render('read', $dateSent, $dateRead, $dateRead);

        $this->assertStringContainsString('mautic.email.timeline.event.read', $html);
        $this->assertStringContainsString('22 hours 6 minutes 5 seconds', $html);
        $this->assertStringNotContainsString('Less than 1 second', $html);
    }

    public function testReadEntryIntervalIgnoresAMismatchedEventTimestamp(): void
    {
        $dateSent = new \DateTime('2026-09-08 10:00:00');
        $dateRead = (clone $dateSent)->modify('+22 hours +6 minutes +5 seconds');

        // Even if the event's own timestamp diverged from item.dateRead, the interval
        // must be computed from item.dateSent/item.dateRead, never from event.timestamp.
        $html = $this->render('read', $dateSent, $dateRead, $dateSent);

        $this->assertStringContainsString('22 hours 6 minutes 5 seconds', $html);
    }

    public function testReadEntryShowsNotReadYetWhenDateReadIsMissing(): void
    {
        $dateSent = new \DateTime('2026-09-08 10:00:00');

        $html = $this->render('read', $dateSent, null, $dateSent);

        $this->assertStringContainsString('mautic.email.timeline.event.not.read', $html);
    }

    public function testFailedEntryIsUnaffectedByTheTypeCheck(): void
    {
        $dateSent = new \DateTime('2026-09-08 10:00:00');

        $html = $this->render('failed', $dateSent, null, $dateSent, isFailed: true);

        $this->assertStringContainsString('mautic.email.timeline.event.failed', $html);
        $this->assertStringNotContainsString('mautic.email.timeline.event.not.read', $html);
    }

    private function render(string $type, \DateTime $dateSent, ?\DateTime $dateRead, \DateTime $timestamp, bool $isFailed = false): string
    {
        $template = $this->twig->load(self::TEMPLATE);

        return $template->render([
            'event' => [
                'extra' => [
                    'type' => $type,
                    'stat' => [
                        'dateSent' => $dateSent,
                        'dateRead' => $dateRead,
                        'isFailed' => $isFailed,
                    ],
                ],
                'timestamp' => $timestamp,
            ],
        ]);
    }
}
