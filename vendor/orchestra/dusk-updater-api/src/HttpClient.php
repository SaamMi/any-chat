<?php

namespace Orchestra\DuskUpdaterApi;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Utils;

class HttpClient
{
    /**
     * HTTP Client implementation.
     *
     * @var \GuzzleHttp\Client|null
     */
    public static $instance;

    /**
     * HTTP Proxy configuration.
     */
    public static ?string $proxy = null;

    /**
     * SSL Verification configuration.
     */
    public static bool $verifySsl = true;

    /**
     * Download from URL.
     *
     * @throws \Exception
     */
    public static function download(string $url, string $destination): void
    {
        $client = static::$instance ?? new Client;

        $resource = Utils::tryFopen($destination, 'w');

        $response = $client->get($url, array_merge([
            'sink' => $resource,
            'verify' => static::$verifySsl,
        ], array_filter([
            'proxy' => static::$proxy,
        ])));

        if ($response->getStatusCode() < 200 || $response->getStatusCode() > 299) {
            throw new Exception(\sprintf('Unable to download from [%s]', $url));
        }
    }

    /**
     * Get contents from URL.
     *
     * @throws \Exception
     */
    public static function fetch(string $url): string
    {
        $client = static::$instance ?? new Client;

        $response = $client->get($url, array_merge([
            'verify' => static::$verifySsl,
        ], array_filter([
            'proxy' => static::$proxy,
        ])));

        if ($response->getStatusCode() < 200 || $response->getStatusCode() > 299) {
            throw new Exception(\sprintf('Unable to fetch contents from [%s]', $url));
        }

        return (string) $response->getBody();
    }

    /**
     * Flush current state to default.
     */
    public static function flushState(): void
    {
        static::$instance = null;
        static::$proxy = null;
        static::$verifySsl = true;
    }
}
