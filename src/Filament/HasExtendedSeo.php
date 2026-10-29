<?php

namespace Dkvadratu\FilamentSeoExtended\Filament;

use Closure;
use Dkvadratu\FilamentSeoExtended\Schema;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

trait HasExtendedSeo
{
    protected static function imagePathToDiskPath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (! Str::startsWith($path, '/')) {
            return $path;
        }

        $diskPathPrefix = parse_url(Storage::disk(config('filament-seo-extended.image_disk', 'public'))->url(''), PHP_URL_PATH) ?: '';
        $diskPathPrefix = rtrim($diskPathPrefix, '/');

        if ($diskPathPrefix && Str::startsWith($path, $diskPathPrefix.'/')) {
            return ltrim(Str::after($path, $diskPathPrefix), '/');
        }

        return ltrim($path, '/');
    }

    protected static function imagePathToPublicPath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, '/')) {
            return $path;
        }

        return parse_url(Storage::disk(config('filament-seo-extended.image_disk', 'public'))->url($path), PHP_URL_PATH) ?: '/'.$path;
    }

    public static function extendedSeo(array $only = ['title', 'author', 'description', 'robots', 'canonical_url', 'image', 'schema_json']): Group
    {
        return Group::make(
            Arr::only([
                'title' => TextInput::make('title')
                    ->translateLabel()
                    ->label('Title')
                    ->helperText(function (?string $state): string {
                        return (string) Str::of(mb_strlen($state ?? ''))
                            ->append(' / ')
                            ->append(60 .' ')
                            ->append(Str::of('Characters')->lower());
                    })
                    ->reactive()
                    ->columnSpan(2),
                'author' => TextInput::make('author')
                    ->translateLabel()
                    ->label('Author')
                    ->columnSpan(2),
                'description' => Textarea::make('description')
                    ->translateLabel()
                    ->label('Description')
                    ->helperText(function (?string $state): string {
                        return (string) Str::of(mb_strlen($state ?? ''))
                            ->append(' / ')
                            ->append(160 .' ')
                            ->append(Str::of('Characters')->lower());
                    })
                    ->reactive()
                    ->columnSpan(2),
                'robots' => Select::make('robots')
                    ->label('Meta Tag Robots')
                    ->options([
                        'index, follow' => 'Index, Follow',
                        'index, nofollow' => 'Index, Nofollow',
                        'noindex, follow' => 'Noindex, Follow',
                        'noindex, nofollow' => 'Noindex, Nofollow',
                    ]),
                'canonical_url' => TextInput::make('canonical_url')
                    ->label('Canonical URL')
                    ->url()
                    ->columnSpan(2),
                'image' => FileUpload::make('image')
                    ->label('Image')
                    ->disk(config('filament-seo-extended.image_disk', 'public'))
                    ->directory(config('filament-seo-extended.image_directory', 'seo'))
                    ->visibility('public')
                    ->multiple(false)
                    ->downloadable()
                    ->getUploadedFileNameForStorageUsing(
                        function (TemporaryUploadedFile $file): string {
                            return str(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'-'.(Str::random(5).'.'.$file->getClientOriginalExtension());
                        }
                    )
                    ->deleteUploadedFileUsing(function ($file) {
                        if ($file) {
                            Storage::disk(config('filament-seo-extended.image_disk', 'public'))->delete($file);
                        }
                    })
                    ->deletable()
                    ->image()
                    ->columnSpan(2),
                'schema_json' => Textarea::make('schema_json')
                    ->label('Custom JSON-LD')
                    ->helperText('A JSON object or a list of objects. Leave empty to use automatic schema.')
                    ->rows(10)
                    ->rule(function (): Closure {
                        return function (string $attribute, mixed $value, Closure $fail): void {
                            if (filled($value) && (! is_string($value) || Schema::decode($value) === null)) {
                                $fail('Enter a valid JSON object or a list of JSON objects.');
                            }
                        };
                    })
                    ->columnSpan(2),
            ], $only)
        )
            ->afterStateHydrated(function (Group $component, ?Model $record) use ($only): void {
                $seoState = $record?->seo?->only($only) ?: [];

                if (! empty($seoState['image'])) {
                    $seoState['image'] = static::imagePathToDiskPath($seoState['image']);
                }

                $component->getChildSchema()->fill($seoState);
            })
            ->statePath('seo')
            ->dehydrated(false)
            ->saveRelationshipsUsing(function (Model $record, array $state) use ($only): void {
                $state = collect($state)->only($only)->map(fn ($value) => $value ?: null)->all();

                if (! empty($state['image'])) {
                    if (is_array($state['image'])) {
                        $state['image'] = $state['image'][array_key_first($state['image'])] ?? null;
                    }

                    $state['image'] = static::imagePathToPublicPath($state['image']);
                }

                if ($record->seo && $record->seo->exists) {
                    $record->seo->update($state);
                } else {
                    $record->seo()->create($state);
                }
            });
    }
}
