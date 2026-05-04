<?php

declare(strict_types=1);

namespace InfilePhp\WordPress\Http;

use Nyholm\Psr7\Response;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class WpRemoteHttpClient implements ClientInterface
{
    /**
     * @throws ClientExceptionInterface
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $url = (string) $request->getUri();
        $method = $request->getMethod();
        
        $headers = [];
        foreach ($request->getHeaders() as $name => $values) {
            $headers[$name] = implode(', ', $values);
        }

        $body = (string) $request->getBody();

        $args = [
            'method'      => $method,
            'headers'     => $headers,
            'body'        => $body !== '' ? $body : null,
            'timeout'     => 30,
            'redirection' => 5,
            'blocking'    => true,
            'sslverify'   => true,
        ];

        // Ensure we pass multipart boundary correctly if needed
        if (isset($headers['Content-Type']) && strpos($headers['Content-Type'], 'multipart/form-data') !== false) {
            // WordPress wp_remote_post doesn't automatically format multipart/form-data
            // However, the SDK's CuiClient manually builds the multipart payload,
            // so we just pass the body as string and WordPress handles it perfectly.
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            throw new WpClientException($response->get_error_message());
        }

        $statusCode = (int) wp_remote_retrieve_response_code($response);
        $responseBody = wp_remote_retrieve_body($response);
        $responseHeaders = wp_remote_retrieve_headers($response);

        // Convert Requests_Utility_CaseInsensitiveDictionary to array
        $formattedHeaders = [];
        if (is_array($responseHeaders) || is_object($responseHeaders) || $responseHeaders instanceof \ArrayAccess) {
            foreach ($responseHeaders as $key => $value) {
                // Values could be array or string
                $formattedHeaders[$key] = is_array($value) ? $value : [$value];
            }
        }

        return new Response($statusCode, $formattedHeaders, $responseBody);
    }
}

class WpClientException extends \RuntimeException implements ClientExceptionInterface
{
}
