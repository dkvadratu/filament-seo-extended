<?php

namespace Dkvadratu\FilamentSeoExtended;

use Illuminate\Database\Eloquent\Model;
use JsonException;

class Schema
{
    /** @return list<array<string, mixed>>|null */
    public static function decode(?string $json): ?array
    {
        if (! filled($json)) {
            return null;
        }

        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (! is_array($data) || $data === []) {
            return null;
        }

        if (array_is_list($data)) {
            foreach ($data as $item) {
                if (! is_array($item) || $item === [] || array_is_list($item)) {
                    return null;
                }
            }

            return $data;
        }

        return [$data];
    }

    public static function hasManual(?Model $model): bool
    {
        if (! $model || ! method_exists($model, 'seo')) {
            return false;
        }

        return self::decode($model->seo?->getAttributes()['schema_json'] ?? null) !== null;
    }

    /** @return array<string, mixed> */
    public static function organization(): array
    {
        $settings = config('filament-seo-extended.organization', []);
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $settings['name'] ?? config('app.name'),
            'url' => $settings['url'] ?? config('app.url'),
            'logo' => $settings['logo'] ?? null,
            'description' => $settings['description'] ?? null,
            'telephone' => $settings['telephone'] ?? null,
            'email' => $settings['email'] ?? null,
            'sameAs' => $settings['same_as'] ?? [],
        ];

        return array_filter($data, fn ($value): bool => filled($value));
    }
}
