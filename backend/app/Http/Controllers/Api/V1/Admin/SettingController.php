<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Services\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function __construct(
        private readonly Settings $settings,
        private readonly ActivityLogger $logger,
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->settings->all(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable', 'string', 'max:1000'],
        ]);

        $allowed = array_keys(Settings::DEFAULTS);

        foreach ($data['settings'] as $key => $value) {
            if (in_array($key, $allowed, true)) {
                $this->settings->set($key, $value === null ? '' : (string) $value);
            }
        }

        $this->logger->log('settings.updated', 'Updated site settings');

        return response()->json([
            'data' => $this->settings->all(),
            'message' => 'Settings saved successfully.',
        ]);
    }
}
