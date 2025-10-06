<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class ManifestController extends Controller
{
    public function manifest(): JsonResponse
    {
        $settings = getSettingInfo();

        $manifest = [
            "name" => $settings['company_name'] ?? "Modular CRM",
            "short_name" => $settings['short_name'] ?? "CRM",
            "start_url" => "/",
            "display" => "standalone",
            "background_color" => "#ffffff",
            "theme_color" => "#1976d2",
            "description" => $settings['description'] ?? '',
            "icons" => [
                [
                    "src" => $settings['pwa_logo_192'] ?? "/images/logo/Hsm-icon-192x192.png",
                    "sizes" => "192x192",
                    "type" => "image/png",
                    "purpose" => "any maskable"
                ],
                [
                    "src" => $settings['pwa_logo_512'] ?? "/images/logo/logo.png",
                    "sizes" => "512x512",
                    "type" => "image/png",
                    "purpose" => "any maskable"
                ]
            ]
        ];

        return response()->json($manifest)
            ->header('Content-Type', 'application/manifest+json');
    }
}
