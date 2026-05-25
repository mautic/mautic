<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Command;

use Mautic\CoreBundle\Exception\MessageOfTheDayException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'mautic:motd',
    description: 'Displays the Message of the Day'
)]
final class MessageOfTheDayCommand extends Command
{
    private const CHANNEL = 'cli';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CoreParametersHelper $coreParametersHelper,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $json            = $this->fetchMotdJson($output);
            $messages        = $this->getMessages($json);
            $selectedMessage = $this->selectMessage($messages);

            if (null === $selectedMessage) {
                return Command::SUCCESS;
            }

            $this->renderMessage($output, $selectedMessage);
        } catch (MessageOfTheDayException $e) {
            $output->writeln('<error>Failed to load MOTD: '.$e->getMessage().'</error>');

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function fetchMotdJson(OutputInterface $output): string
    {
        $url = $this->coreParametersHelper->get('motd_url');

        if (empty($url)) {
            throw new MessageOfTheDayException('MOTD URL is not configured');
        }

        if (false === filter_var($url, FILTER_VALIDATE_URL)) {
            throw new MessageOfTheDayException('MOTD URL is not valid');
        }

        $cachePath = $this->coreParametersHelper->get('motd_cache_path');
        $cacheTtl  = (int) $this->coreParametersHelper->get('motd_cache_ttl');

        if (is_file($cachePath) && time() - filemtime($cachePath) < $cacheTtl) {
            $cached = file_get_contents($cachePath);

            if (false !== $cached) {
                return $cached;
            }
        }

        try {
            $response = $this->httpClient->request('GET', $url, [
                'timeout' => 3,
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            $json = $response->getContent();
        } catch (ExceptionInterface) {
            throw new MessageOfTheDayException('Could not fetch motd.json');
        }

        if ('' === $json) {
            throw new MessageOfTheDayException('MOTD response was empty');
        }

        $written = file_put_contents($cachePath, $json);

        if (false === $written && $output->isVerbose()) {
            $output->writeln('<error>Could not write MOTD cache to '.$cachePath.'</error>');
        }

        return $json;
    }

    /**
     * @return array{
     *     timed: list<array{category: array{label: string}, lines: list<string>}>,
     *     timeless: list<array{category: array{label: string}, lines: list<string>}>
     * }
     */
    private function getMessages(string $json): array
    {
        try {
            /** @var array{
             *     categories?: array<string, array{label: string}>,
             *     messages?: array<int, array{
             *         content: array<string, list<string>>,
             *         category: string,
             *         start: string|null,
             *         end: string|null
             *     }>
             * } $data
             */
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new MessageOfTheDayException('Could not decode MOTD JSON');
        }

        $messages = ['timed' => [], 'timeless' => []];

        if (empty($data['messages']) || !is_array($data['messages'])) {
            return $messages;
        }

        if (empty($data['categories']) || !is_array($data['categories'])) {
            return $messages;
        }

        $utc = new \DateTimeZone('UTC');
        $now = new \DateTimeImmutable('now', $utc);

        foreach ($data['messages'] as $message) {
            if (empty($message['content']) || !is_array($message['content'])) {
                continue;
            }

            if (empty($message['content'][self::CHANNEL]) || !is_array($message['content'][self::CHANNEL])) {
                continue;
            }

            if (empty($message['category'])) {
                continue;
            }

            if (!isset($data['categories'][$message['category']])) {
                continue;
            }

            try {
                $start = !empty($message['start']) ? new \DateTimeImmutable($message['start'], $utc) : null;
                $end   = !empty($message['end']) ? new \DateTimeImmutable($message['end'], $utc) : null;
            } catch (\Exception) {
                continue;
            }

            if (null !== $start && $now < $start) {
                continue;
            }

            if (null !== $end && $now > $end) {
                continue;
            }

            $pool = (null !== $start && null !== $end) ? 'timed' : 'timeless';

            $messages[$pool][] = [
                'category' => $data['categories'][$message['category']],
                'lines'    => $message['content'][self::CHANNEL],
            ];
        }

        return $messages;
    }

    /**
     * @param array{
     *     timed: list<array{category: array{label: string}, lines: list<string>}>,
     *     timeless: list<array{category: array{label: string}, lines: list<string>}>
     * } $messages
     *
     * @return array{category: array{label: string}, lines: list<string>}|null
     */
    private function selectMessage(array $messages): ?array
    {
        ['timed' => $timed, 'timeless' => $timeless] = $messages;

        if ([] === $timed && [] === $timeless) {
            return null;
        }

        if ([] === $timed) {
            return $timeless[array_rand($timeless)];
        }

        if ([] === $timeless) {
            return $timed[array_rand($timed)];
        }

        // When both sets are non-empty, pick timed message 75% of the time
        $pool = (random_int(1, 100) <= 75) ? $timed : $timeless;

        return $pool[array_rand($pool)];
    }

    /**
     * @param array{category: array{label: string}, lines: list<string>} $message
     */
    private function renderMessage(OutputInterface $output, array $message): void
    {
        $label = $message['category']['label'];
        $lines = $message['lines'];

        if ([] === $lines) {
            return;
        }

        $horizontalPadding = 2;
        $contentIndent     = 3;

        $labelWidth       = Helper::width($label);
        $longestLineWidth = max(array_map(
            static fn (string $line): int => Helper::width($line),
            $lines
        ));

        $longest = max(
            $longestLineWidth + $contentIndent,
            $labelWidth
        );

        $padding = str_repeat(' ', $longest + $horizontalPadding * 2);

        $output->writeln('');
        $output->writeln('<fg=white;bg=blue>'.$padding.'</>');
        $output->writeln(
            '<fg=white;bg=blue>'.
            str_repeat(' ', $horizontalPadding).
            $label.
            str_repeat(' ', $longest - $labelWidth + $horizontalPadding).
            '</>'
        );
        $output->writeln('<fg=white;bg=blue>'.$padding.'</>');

        foreach ($lines as $line) {
            $lineWidth = Helper::width($line);
            $output->writeln(
                '<fg=white;bg=blue>'.
                str_repeat(' ', $horizontalPadding + $contentIndent).
                $line.
                str_repeat(' ', $longest - $lineWidth - $contentIndent + $horizontalPadding).
                '</>'
            );
        }

        $output->writeln('<fg=white;bg=blue>'.$padding.'</>');
        $output->writeln('');
    }
}
