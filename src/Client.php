<?php

namespace CodeGreenCreative\Freshworks;

use CodeGreenCreative\Freshworks\Exceptions\FreshworksException;
use Exception;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;

class Client
{
    /** @var GuzzleClient */
    protected $client;
    /** @var Response */
    private $response;

    /**
     * Set up Guzzle client
     */
    public function __construct($type = 'api')
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if ($type === 'api') {
            $headers['Authorization'] = "Token token=" . config('freshworks.api_key');
        }

        $this->client = new GuzzleClient([
            'base_uri' => sprintf('https://%s.myfreshworks.com/crm/sales/%s/', config('freshworks.domain'), $type),
            'headers' => $headers,
        ]);
    }

    /**
     * Go perform the request
     *
     * @param string $method
     * @param string $uri
     * @param array  $options
     *
     * @return \CodeGreenCreative\Freshworks\Client
     */
    public function go(string $method, string $uri = '', array $options = []): object
    {
        try {
            $this->response = $this->client->request($method, $uri, $options);
        } catch (RequestException $e) {
            throw FreshworksException::fromGuzzleException($e);
        } catch (Exception $e) {
            throw new Exceptions\FreshworksException($e->getMessage(), $e->getCode());
        }

        return $this->toObject();
    }

    public function downloadFile(string $method, string $uri = '', array $options = [], string $filePath): void
    {
        try {
            $this->response = $this->client->request($method, $uri, $options);

            if ($this->response->getStatusCode() === 200) {
                $fileStream = fopen($filePath, 'w');
                if (!$fileStream) {
                    throw new \RuntimeException("Unable to open file at path: $filePath");
                }

                // Write response body to the file
                $responseString = $this->response->getBody()->getContents();
                fwrite($fileStream, $responseString);
                fclose($fileStream);
            } else {
                throw new \RuntimeException("Unexpected response status: " . $this->response->getStatusCode());
            }
        } catch (RequestException $e) {
            throw FreshworksException::fromGuzzleException($e);
        } catch (Exception $e) {
            throw new Exceptions\FreshworksException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Return the JSON response as an object
     * @return Object
     */
    public function toObject(): Object
    {
        return json_decode($this->response->getBody());
    }

    /**
     * Return the JSON response as an array
     * @return Array
     */
    public function toArray(): Array
    {
        return json_decode($this->response->getBody(), true);
    }
}
