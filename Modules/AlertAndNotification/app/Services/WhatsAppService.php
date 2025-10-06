<?php

namespace Modules\AlertAndNotification\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class WhatsAppService
{
    protected string $apiKey;
    protected string $apiUrl;
    protected Client $client;

    public function __construct()
    {
        $this->apiKey = env('AISENSY_API_KEY') ?? "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6IjY3OGI2ZDI3MGM4MmNiMGJmY2JiZDcwZCIsIm5hbWUiOiJOT0JMRSBQT1dFUiBTT0xVVElPTlMiLCJhcHBOYW1lIjoiQWlTZW5zeSIsImNsaWVudElkIjoiNjc4YjZkMjcwYzgyY2IwYmZjYmJkNzA4IiwiYWN0aXZlUGxhbiI6IkZSRUVfRk9SRVZFUiIsImlhdCI6MTczNzE5MDY5NX0.JC1wNwV51gNySK43d08XmLi7r020I2UO8VV2M6gXmRY";
        $this->apiUrl = 'https://backend.aisensy.com/campaign/t1/api/v2';
        $this->client = new Client();
    }

    public function sendMediaMessage(string $userName, string $phone, string $message, string $fileUrl = "", string $fileCaption = "", string $extension = ""): object
    {
        try {
            $cleanMessage = trim(preg_replace('/\s+/', ' ', $message));
            $headers = ['Content-Type' => 'application/json'];

            # Choose campaign name based on media type
            $campaignName = env('AISENSY_CAMPAIGN_NAME', 'default_campaign');
            
            if (!empty($fileUrl)) {
                if ($extension === 'Document') {
                    $campaignName = env('AISENSY_DOCUMENT_CAMPAIGN_NAME', 'document_campaign');
                } elseif ($extension === 'Image') {
                    $campaignName = env('AISENSY_IMAGE_CAMPAIGN_NAME', 'image_campaign');
                }
            }

            $payload = [
                'apiKey' => $this->apiKey,
                'campaignName' => $campaignName,
                'destination' => trim($phone),
                'userName' => $userName ?: 'No Name',
                'source' => 'Laravel-System',
                'templateParams' => [$cleanMessage],
                'tags' => ['notification'],
                'attributes' => [
                    'attribute_name' => $cleanMessage,
                ],
            ];

            # Add media if required
            if (!empty($fileUrl)) {
                $payload['media'] = [
                    'url' => $fileUrl,
                    'filename' => basename($fileUrl),
                ];
                if (!empty($fileCaption)) {
                    $payload['media']['caption'] = $fileCaption;
                }
            }

            $response = $this->client->post($this->apiUrl, [
                'headers' => $headers,
                'json' => $payload,
            ]);

            return (object)[
                'status' => true,
                'response' => json_decode($response->getBody(), true),
            ];
        } catch (ClientException $e) {
            $responseBody = $e->getResponse()->getBody()->getContents();
            $decoded = json_decode($responseBody, true);
            return (object)[
                'status' => false,
                'message' => $decoded['errorMessage'] ?? $decoded['message'] ?? 'Something went wrong',
                'full_error' => $decoded,
            ];
        } catch (\Exception $e) {
            return (object)[
                'status' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
