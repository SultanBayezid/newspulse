<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class Settings
{
    public const DEFAULTS = [
        'site_name' => 'NewsPulse',
        'site_tagline' => 'Fast, fair and fearless journalism',
        'site_description' => 'NewsPulse is an independent newsroom covering politics, business, technology, sports and culture from around the world.',
        'contact_email' => 'hello@newspulse.test',
        'footer_text' => 'Independent journalism, every single day.',
        'social_facebook' => '',
        'social_twitter' => '',
        'social_youtube' => '',
        'social_instagram' => '',
        'social_linkedin' => '',
        'articles_per_page' => '12',
        'comments_enabled' => '1',
        'comments_moderation' => '1',
        'registration_enabled' => '1',
        'default_language' => 'en',
    ];

    public const PUBLIC_KEYS = [
        'site_name', 'site_tagline', 'site_description', 'contact_email', 'footer_text',
        'social_facebook', 'social_twitter', 'social_youtube', 'social_instagram', 'social_linkedin',
        'articles_per_page', 'comments_enabled', 'registration_enabled', 'default_language',
    ];

    public function get(string $key, ?string $default = null): ?string
    {
        $map = Setting::allAsMap();

        return $map[$key] ?? static::DEFAULTS[$key] ?? $default;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->get($key);

        return $value === null ? $default : in_array($value, ['1', 'true', 'on', 'yes'], true);
    }

    public function int(string $key, int $default = 0): int
    {
        return (int) ($this->get($key) ?? $default);
    }

    public function all(): array
    {
        return array_merge(
            static::DEFAULTS,
            Setting::allAsMap()
        );
    }

    public function public(): array
    {
        $all = $this->all();

        return collect(static::PUBLIC_KEYS)
            ->mapWithKeys(fn (string $key) => [$key => $all[$key] ?? null])
            ->all();
    }

    public function set(string $key, ?string $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
