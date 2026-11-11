<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Settings;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    public function __construct(private readonly Settings $settings)
    {
    }

    /**
     * Public site configuration consumed by the SPA.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->settings->public(),
        ]);
    }
}
