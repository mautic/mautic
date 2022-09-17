<?php

namespace Mautic\CoreBundle\Helper\Dsn;

class Dsn
{
    private string $scheme;

    private string $host;

    private ?string $user;

    private ?string $password;

    private ?int $port;

    /**
     * Query string array to be added to DSN.
     *
     * @var array<string,string>
     */
    private array $options;

    private const ALLOWED_DSN_ARRAY = [
        'sync://' => ['sync', ''],
    ];

    /**
     * Create a new DSN.
     *
     * @param string                $scheme   The DSN scheme (e.g. sync://)
     * @param string                $host     The DSN host (e.g. localhost)
     * @param string                $user     The DSN user (e.g. root)
     * @param string                $password The DSN password (e.g. root)
     * @param int                   $port     The DSN port (e.g. 3306)
     * @param array<string, string> $options  The DSN options (e.g. ['charset' => 'utf8'])
     */
    public function __construct(string $scheme, string $host, string $user = null, string $password = null, int $port = null, array $options = [])
    {
        $this->scheme   = $scheme;
        $this->host     = $host;
        $this->user     = $user;
        $this->password = $password;
        $this->port     = $port;
        $this->options  = $options;
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
            return new self(self::ALLOWED_DSN_ARRAY[$dsn][0], self::ALLOWED_DSN_ARRAY[$dsn][1]);
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

        $options = [];
        if (isset($parsedDsn['path'])) {
            $options['path'] = trim($parsedDsn['path'], '/');
        }

        $user     = '' !== ($parsedDsn['user'] ?? '') ? urldecode($parsedDsn['user']) : null;
        $password = '' !== ($parsedDsn['pass'] ?? '') ? urldecode($parsedDsn['pass']) : null;
        $port     = $parsedDsn['port'] ?? null;
        parse_str($parsedDsn['query'] ?? '', $query);
        if (!empty($query)) {
            $options = array_merge($options, $query);
        }

        return new self($parsedDsn['scheme'], $parsedDsn['host'], $user, $password, $port, $options);
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getUser(): ?string
    {
        return $this->user;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getPort(int $default = null): ?int
    {
        return $this->port ?? $default;
    }

    /**
     * @param mixed $default
     *
     * @return mixed|null
     */
    public function getOption(string $key, $default = null)
    {
        return $this->options[$key] ?? $default;
    }

    /**
     * @return array<string, string>
     */
    public function getOptions()
    {
        return $this->options;
    }
}
