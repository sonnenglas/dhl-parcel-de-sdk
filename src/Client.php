<?php

declare(strict_types=1);

namespace Sonnenglas\DhlParcelDe;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;

class Client
{
    protected const URI_PRODUCTION = 'https://api-eu.dhl.com/parcel/de/shipping/v2/';

    protected const URI_SANDBOX = 'https://api-sandbox.dhl.com/parcel/de/shipping/v2/';

    protected string $baseUri;

    protected string $lastMessageReference;


    public function __construct(
        protected string $username,
        protected string $password,
        protected string $apiKey,
        protected bool $productionMode,
        ?string $baseUriOverride = null,
        protected float $timeout = 30.0,
        protected float $connectTimeout = 10.0
    ) {
        $this->baseUri = $baseUriOverride
            ?? ($this->productionMode ? static::URI_PRODUCTION : static::URI_SANDBOX);
    }

    public function getBaseUri(): string
    {
        return $this->baseUri;
    }

    /**
     * @throws GuzzleException
     */
    public function get(string $uri, array $query): array
    {
        $httpClient = new GuzzleClient();

        $options = $this->getRequestOptions('GET', $query);

        $response = $httpClient->request('GET', $uri, $options);

        return json_decode((string) $response->getBody(), true);
    }


    /**
     * @throws GuzzleException
     */
    public function post(string $uri, array $query, array $headers = []): array
    {
        $httpClient = new GuzzleClient();

        $options = $this->getRequestOptions('POST', $query, $headers);

        $response = $httpClient->request('POST', $uri, $options);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * @throws GuzzleException
     */
    public function delete(string $uri, array $query): array
    {
        $httpClient = new GuzzleClient();

        $options = $this->getRequestOptions('DELETE', $query);

        $response = $httpClient->request('DELETE', $uri, $options);

        return json_decode((string) $response->getBody(), true);
    }

    protected function getRequestOptions(string $queryType, array $query, array $headers = []): array
    {
        $headers['dhl-api-key'] = $this->apiKey;
        $headers['Content-Type'] = 'application/json';

        $requestOptions = [
            'base_uri' => $this->baseUri,
            'auth' => [$this->username, $this->password],
            'headers' => $headers,
            'timeout' => $this->timeout,
            'connect_timeout' => $this->connectTimeout,
        ];

        if ($queryType === "GET" || $queryType === "DELETE") {
            $requestOptions['query'] = $query;
        } else {
            $requestOptions['json'] = $query;
        }

        return $requestOptions;
    }
}
