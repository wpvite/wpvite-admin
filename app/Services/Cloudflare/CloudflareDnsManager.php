<?php

namespace App\Services\Cloudflare;

use App\Services\HttpService;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class CloudflareDnsManager
{
    protected $apiBaseUrl = 'https://api.cloudflare.com/client/v4';
    protected $apiToken;
    protected $httpService;

    public function __construct(HttpService $httpService)
    {
        $this->apiToken = Config::get('services.cloudflare.api_token');
        $this->httpService  = $httpService;
    }

    /**
     * Add an A record to Cloudflare DNS.
     *
     * @param string $domain
     * @param string $ipAddress
     * @return array
     * @throws Exception
     */
    public function addARecord(string $domain, string $ipAddress): array
    {
        // Step 1: Get the Zone ID for the domain
        $zoneId = $this->getZoneId($domain);

        if (!$zoneId) {
            Log::error("Zone ID not found for domain: {$domain}");
            throw new Exception("Zone ID not found for domain: {$domain}");
        }

        // Step 2: Create the DNS record
        $params = [
            'type' => 'A',
            'name' => $domain,
            'content' => $ipAddress,
            // 'ttl' => 3600,
            'proxied' => true,
        ];

        $path    = "/zones/{$zoneId}/dns_records";
        $response   = $this->makeApiRequest('POST', $path, $params);

        if($response['status'] && isset($response['response_data']['result']['id'])) {
            return ['status' => true, 'data' => $response['response_data']['result']];
        }

        return $response;
    }

    /**
     * Get record details.
     *
     * @param string $domain
     * @param string $ipAddress
     * @return array
     * @throws Exception
     */
    public function getRecord(string $domain): array
    {
        // Step 1: Get the Zone ID for the domain
        $zoneId = $this->getZoneId($domain);

        if (!$zoneId) {
            Log::error("Zone ID not found for domain: {$domain}");
            throw new Exception("Zone ID not found for domain: {$domain}");
        }

        // Step 2: Create the DNS record
        $params = [
            'name' => $domain,
            'match' => 'all'
        ];

        $path    = "/zones/{$zoneId}/dns_records?". http_build_query($params);
        $response   = $this->makeApiRequest('GET', $path, []);

        if($response['status'] && isset($response['response_data']['result'])) {
            $records = $response['response_data']['result'];

            $records = array_filter($records, function($record) use ($domain) {
                return $record['name'] == $domain;
            });

            if($records[0] ?? []) {
                return ['status' => true, 'data' => $records[0]];
            } else {
                return ['status' => false, 'message' => 'Record not exists.'];
            }
        }

        return $response;
    }

    /**
     * Get the Zone ID for a domain.
     *
     * @param string $domain
     * @return string|null
     * @throws Exception
     */
    public function getZoneId(string $domain):?string
    {
        $zoneId = config('services.cloudflare.zone_id');
        if($zoneId) {
            return $zoneId;
        }

        $response = $this->makeApiRequest('GET', 'zones', [
            'name' => $this->extractBaseDomain($domain),
        ]);

        $zones = $response['response_data']['result'] ?? [];
        return $zones[0]['id'] ?? null;
    }

    /**
     * Extract the base domain from a full domain name.
     *
     * @param string $domain
     * @return string
     */
    protected function extractBaseDomain(string $domain): string
    {
        $parts = explode('.', $domain);
        $count = count($parts);

        return $count > 2 ? "{$parts[$count - 2]}.{$parts[$count - 1]}" : $domain;
    }

    /**
     * Make an API request to CloudFlare DNS.
     *
     * @param string $command The API endpoint URL.
     * @param array $params Parameters to send with the request.
     * @return array
     * @throws Exception
     */
    protected function makeApiRequest(string $method, string $path, array $params): array
    {
        $url    = $this->apiBaseUrl.'/'.$path;

        $response = $this->httpService->sendRequest($method, $url, ['body' => $params], [
            'Authorization' => "Bearer {$this->apiToken}",
            'Content-Type' => 'application/json',
        ]);

        if (!$response['status']) {
            Log::error("CloudFlare DNS API error: {$response['message']}");
        }

        return $response;
    }
}
