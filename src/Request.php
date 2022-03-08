<?php

namespace JupiterAPI;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class Request
{

    private static ?HttpClient $client = NULL;

    /**
     * @param $uri
     * @param array $data
     * @param string $method
     * @return PromiseInterface
     */
    public static function fetchAsync($uri, array $data = [], string $method = 'POST'): PromiseInterface
    {
        return static::getClient()->requestAsync($method, $uri, static::formData($data))->then(
            onFulfilled: fn (ResponseInterface $response) => static::jsonDecode($response->getBody()->getContents()),
            onRejected: fn (RequestException $exception) => static::jsonDecode($exception->getResponse()->getBody()->getContents()),
        );
    }

    /**
     * @param $uri
     * @param array $data
     * @param string $method
     * @return mixed
     */
    public static function fetch($uri, array $data = [], string $method = 'POST'): mixed
    {
        return static::fetchAsync($uri, $data, method: $method)
            ->wait();
    }

    /**
     * @throws Throwable
     */
    public static function unwrap(array $promises = []): array
    {
        return Utils::unwrap($promises);
    }

    /**
     * @return HttpClient
     */
    private static function getClient(): HttpClient
    {
        return static::$client = static::$client ?: new HttpClient([
            'base_uri' => 'https://en.ephoto360.com/',
            'cookies' => new FileCookieJar(__DIR__ .'/../storage/cookies.json'),
            'headers' => [
                'Content-Type' => 'multipart/form-data',
            ]
        ]);
    }

    /**
     * @param array $params
     * @return array
     */
    public static function formData(array $params): array
    {
        $data = [
            'multipart' => [],
            'form_params' => [],
        ];

        foreach ($params as $index => $item)
            is_string($item) && is_file($item) && filesize($item) > 0
                ? $data['multipart'][$index] = [
                    'name' => $index,
                    'contents' => \GuzzleHttp\Psr7\Utils::tryFopen($item, 'r'),
                    'filename' => basename($item)
                ] : $data['form_params'][$index] = $item;

        return array_filter($data);
    }

    /**
     * @param $condition
     * @param array $array
     * @param callable $cb
     * @return array
     */
    private static function mergeIf($condition, array $array, callable $cb): array
    {
        return $condition ? array_merge($array, (array) $cb()) : $array;
    }

    /**
     * @param $contents
     * @return mixed
     */
    private static function jsonDecode($contents): mixed
    {
        return (is_string($contents) && json_decode($contents) && json_last_error() === 0)
            ? json_decode($contents, true)
            : $contents;
    }
}