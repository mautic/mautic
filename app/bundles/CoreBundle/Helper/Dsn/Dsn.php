<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper\Dsn;

final class Dsn implements \Stringable
{
    private const ALLOWED_DSN_ARRAY = [
        'sync://' => ['sync', ''],
    ];

    /**
     * Create a new DSN.
     *
     * @param string                $scheme   The DSN scheme (e.g. sync://)
     * @param string                $host     The DSN host (e.g. localhost)
     * @param string|null           $user     The DSN user (e.g. root)
     * @param string|null           $password The DSN password (e.g. root)
     * @param int|null              $port     The DSN port (e.g. 3306)
     * @param string|null           $path     The DSN path (e.g. bucket/name/two)
     * @param array<string, string> $options  The DSN options (e.g. ['charset' => 'utf8'])
     */
    public function __construct(
        private string $scheme,
        private string $host,
        private ?string $user = null,
        private ?string $password = null,
        private ?int $port = null,
        private ?string $path = null,
        private array $options = [],
    ) {
    }

    /**
     * Convert from a DSN string to a DSN object.
     *
     * @param string $dsn The DSN string
     *
     * @return self The DSN object
     */
    public static function fromString(string $dsn): self
    {
        if (array_key_exists($dsn, self::ALLOWED_DSN_ARRAY)) {
            return new self(...self::ALLOWED_DSN_ARRAY[$dsn]);
        }

        if (false === $parsedDsn = parse_url($dsn)) {
            throw new \InvalidArgumentException(sprintf('The "%s" DSN is invalid.', $dsn));
        }

        if (!isset($parsedDsn['scheme'])) {
            throw new \InvalidArgumentException(sprintf('The "%s" DSN must contain a scheme.', $dsn));
        }

        if (!isset($parsedDsn['host'])) {
            throw new \InvalidArgumentException(sprintf('The "%s" DSN must contain a host (use "default" by default).', $dsn));
        }

        $host     = urldecode($parsedDsn['host']);
        $user     = '' !== ($parsedDsn['user'] ?? '') ? urldecode($parsedDsn['user']) : null;
        $password = '' !== ($parsedDsn['pass'] ?? '') ? urldecode($parsedDsn['pass']) : null;
        $port     = isset($parsedDsn['port']) ? (int) $parsedDsn['port'] : null;
        $path     = isset($parsedDsn['path']) ? ltrim(urldecode($parsedDsn['path']), '/') : null;
        parse_str($parsedDsn['query'] ?? '', $query);

        return new self($parsedDsn['scheme'], $host, $user, $password, $port, $path, $query);
    }

    public function __toString(): string
    {
        $dsn = $this->scheme.'://';

        if ($this->user) {
            $dsn .= urlencode($this->user);
        }

        if ($this->password) {
            $dsn .= ':'.urlencode($this->password);
        }

        if ($this->user || $this->password) {
            $dsn .= '@';
        }

        $dsn .= urlencode($this->host);

        if ($this->port) {
            $dsn .= ':'.$this->port;
        }

        if ($this->path) {
            $dsn .= '/'.urlencode($this->path);
        }

        $query = http_build_query($this->options);

        if ($query) {
            $dsn .= '?'.$query;
        }

        return $dsn;
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function setScheme(string $scheme): Dsn
    {
        $dsn         = clone $this;
        $dsn->scheme = $scheme;

        return $dsn;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function setHost(string $host): Dsn
    {
        $dsn       = clone $this;
        $dsn->host = $host;

        return $dsn;
    }

    public function getUser(): ?string
    {
        return $this->user;
    }

    public function setUser(?string $user): Dsn
    {
        $dsn       = clone $this;
        $dsn->user = $user;

        return $dsn;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $dsn           = clone $this;
        $dsn->password = $password;

        return $dsn;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function setPort(?int $port): Dsn
    {
        $dsn       = clone $this;
        $dsn->port = $port;

        return $dsn;
    }

    public function getOption(string $key): ?string
    {
        return $this->options[$key] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array<string, string> $options
     */
    public function setOptions(array $options): Dsn
    {
        $dsn          = clone $this;
        $dsn->options = $options;

        return $dsn;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): Dsn
    {
        $dsn       = clone $this;
        $dsn->path = $path;

        return $dsn;
    }
}
