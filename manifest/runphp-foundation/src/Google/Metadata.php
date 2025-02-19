<?php

declare(strict_types=1);

namespace ThinkFluent\RunPHP\Google;

/**
 * Google Cloud Metadata Client
 *
 * @link https://cloud.google.com/run/docs/reference/container-contract
 *
 * You can access this data from the metadata server using simple HTTP requests to the
 * http://metadata.google.internal/ endpoint with the 'Metadata-Flavor: Google' header
 *
 * @package ThinkFluent\RunPHP\Google
 */
class Metadata
{

    private const int TIMEOUT_SECS = 2;

    private const string METADATA_HOST = 'metadata.google.internal';

    /**
     * @var bool
     */
    private bool $bol_success = false;

    /**
     * @var string
     */
    private string $str_response;

    /**
     * Fetch the metadata
     *
     * Early out if we cannot resolve the metadata host
     *
     * @return self
     */
    public function fetch(): self
    {
        $this->bol_success = false;
        if (!$this->canResolve()) {
            return $this;
        }
        $this->str_response = (string) file_get_contents(
            'http://' . self::METADATA_HOST . '/?recursive=true',
            false,
            stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => "Content-type: application/json\r\nMetadata-Flavor: Google",
                    'ignore_errors' => true,
                    'follow_location' => false,
                    'timeout' => self::TIMEOUT_SECS,
                ],
            ])
        );
        if (isset($http_response_header)
            && !empty($http_response_header)
            && false !== strpos($http_response_header[0], '200 OK')
        ) {
            $this->bol_success = true;
        }
        return $this;
    }

    /**
     * Are we able to resolve the metadata host? Either IPv4 or IPv6
     *
     * @return bool
     */
    public function canResolve(): bool
    {
        return !(
            empty(dns_get_record(self::METADATA_HOST, DNS_A)) &&
            empty(dns_get_record(self::METADATA_HOST, DNS_AAAA))
        );
    }

    /**
     * Do we have data?
     *
     * @return bool
     */
    public function hasData(): bool
    {
        return $this->bol_success;
    }

    /**
     * Get the metadata response
     *
     * @return string
     */
    public function getData(): string
    {
        return $this->str_response;
    }
}
