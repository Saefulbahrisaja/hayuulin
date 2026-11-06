<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use UnexpectedValueException;

class OpenStreetMapImportService
{
    private const TOURISM_TAGS = 'attraction|viewpoint|museum|artwork|theme_park|zoo|hotel|guest_house|hostel|motel|chalet|resort|camp_site';

    private const MAX_ENDPOINT_ATTEMPTS = 3;

    private const OVERPASS_QUERY_TIMEOUT_SECONDS = 10;

    private const HTTP_CONNECT_TIMEOUT_SECONDS = 3;

    private const HTTP_REQUEST_TIMEOUT_SECONDS = 12;

    public function preview(int $userId, string $region, bool $refresh = false): array
    {
        $region = trim($region);
        $key = $this->previewKey($userId, $region);
        if (! $refresh && Cache::get($this->regionKey($userId)) === $region && Cache::has($key)) {
            return Cache::get($key);
        }

        $places = $this->fetchPlaces($region);
        Cache::put($key, $places, now()->addMinutes(20));
        Cache::put($this->regionKey($userId), $region, now()->addMinutes(20));

        return $places;
    }

    public function cachedPreview(int $userId): array
    {
        $region = $this->cachedRegion($userId);

        return $region === null ? [] : Cache::get($this->previewKey($userId, $region), []);
    }

    public function cachedRegion(int $userId): ?string
    {
        return Cache::get($this->regionKey($userId));
    }

    private function fetchPlaces(string $region): array
    {
        $areaNames = $this->areaNames($region);
        $areaQueries = [];
        $areaSets = [];
        foreach ($areaNames as $index => $areaName) {
            $areaSet = '.region'.$index;
            $areaSets[] = '(area'.$areaSet.')';
            $areaQueries[] = 'area["name"='.$this->overpassString($areaName).']["boundary"="administrative"](area.indonesia)->'.$areaSet.';';
        }
        $areaUnion = implode('', array_map(
            fn (string $areaSet) => 'nwr["tourism"~"^('.self::TOURISM_TAGS.')$"]'.$areaSet.';',
            $areaSets
        ));
        $query = '[out:json][timeout:'.self::OVERPASS_QUERY_TIMEOUT_SECONDS.'];'
            .'area["name"="Indonesia"]["boundary"="administrative"]->.indonesia;'
            .implode('', $areaQueries)
            .'('.$areaUnion.');'
            .'out tags center 120;';

        $lastException = null;
        $endpoints = config('services.openstreetmap.overpass_urls', [
            config('services.openstreetmap.overpass_url'),
        ]);

        foreach (array_slice($endpoints, 0, self::MAX_ENDPOINT_ATTEMPTS) as $endpoint) {
            try {
                $response = Http::asForm()
                    ->connectTimeout(self::HTTP_CONNECT_TIMEOUT_SECONDS)
                    ->timeout(self::HTTP_REQUEST_TIMEOUT_SECONDS)
                    ->acceptJson()
                    ->withUserAgent('SukabumiJelajah/1.0 (admin-managed OpenStreetMap import)')
                    ->post($endpoint, ['data' => $query])
                    ->throw();

                $elements = $response->json('elements');
                if (! is_array($elements)) {
                    throw new UnexpectedValueException('Overpass returned an unreadable response.');
                }

                return collect($elements)
                    ->map(fn ($element) => is_array($element) ? $this->normalize($element, $region) : null)
                    ->filter()
                    ->unique('source_id')
                    ->take(120)
                    ->values()
                    ->all();
            } catch (ConnectionException|RequestException|UnexpectedValueException $exception) {
                report($exception);
                $lastException = $exception;
            }
        }

        throw new OverpassUnavailableException(
            'All configured Overpass services failed to return usable data.',
            previous: $lastException
        );
    }

    private function areaNames(string $region): array
    {
        if (preg_match('/^(kabupaten|kota)\s+/iu', $region) === 1) {
            return [$region];
        }

        return array_values(array_unique([
            $region,
            'Kabupaten '.$region,
            'Kota '.$region,
        ]));
    }

    private function overpassString(string $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function previewKey(int $userId, string $region): string
    {
        return 'osm-import:'.$userId.':'.hash('sha256', mb_strtolower($region));
    }

    private function regionKey(int $userId): string
    {
        return 'osm-import-region:'.$userId;
    }

    private function normalize(array $element, string $fallbackRegion): ?array
    {
        $tags = $element['tags'] ?? [];
        if (! is_array($tags)) {
            return null;
        }

        $nameTag = $tags['name:id'] ?? $tags['name'] ?? null;
        if (! is_string($nameTag) && ! is_numeric($nameTag)) {
            return null;
        }

        $name = trim((string) $nameTag);
        $latitude = $element['lat'] ?? data_get($element, 'center.lat');
        $longitude = $element['lon'] ?? data_get($element, 'center.lon');
        $type = $tags['tourism'] ?? '';
        if (! is_string($type)) {
            return null;
        }
        $osmType = (string) ($element['type'] ?? '');
        $osmId = (string) ($element['id'] ?? '');

        if (
            $name === ''
            || mb_strlen($name) > 255
            || ! is_numeric($latitude)
            || ! is_numeric($longitude)
            || ! is_finite((float) $latitude)
            || ! is_finite((float) $longitude)
            || (float) $latitude < -90
            || (float) $latitude > 90
            || (float) $longitude < -180
            || (float) $longitude > 180
            || ! in_array($osmType, ['node', 'way', 'relation'], true)
            || ! ctype_digit($osmId)
            || ! in_array($type, [
                'attraction', 'viewpoint', 'museum', 'artwork', 'theme_park',
                'zoo', 'hotel', 'guest_house', 'hostel', 'motel', 'chalet',
                'resort', 'camp_site',
            ], true)
        ) {
            return null;
        }

        $isAccommodation = in_array($type, [
            'hotel', 'guest_house', 'hostel', 'motel', 'chalet', 'resort', 'camp_site',
        ], true);
        $regionTag = $tags['addr:subdistrict']
            ?? $tags['addr:city']
            ?? $tags['addr:district']
            ?? $tags['addr:county']
            ?? $tags['is_in']
            ?? $fallbackRegion;
        $region = is_string($regionTag) || is_numeric($regionTag)
            ? trim((string) $regionTag)
            : $fallbackRegion;
        $region = $region !== '' ? $region : $fallbackRegion;
        $addressParts = array_filter([
            $tags['addr:street'] ?? null,
            $tags['addr:suburb'] ?? null,
            $tags['addr:city'] ?? null,
            $tags['addr:district'] ?? null,
            $tags['addr:province'] ?? 'Jawa Barat',
        ], fn ($part) => is_string($part) || is_numeric($part));
        $address = mb_substr(
            implode(', ', $addressParts) ?: trim((string) $region).', Sukabumi',
            0,
            255
        );

        return [
            'source_id' => $osmType.'/'.$osmId,
            'source_url' => 'https://www.openstreetmap.org/'.$osmType.'/'.$osmId,
            'name' => $name,
            'latitude' => (float) $latitude,
            'longitude' => (float) $longitude,
            'region' => mb_substr($region, 0, 120),
            'address' => $address,
            'tourism_type' => $type,
            'is_accommodation' => $isAccommodation,
            'category_slug' => $this->categorySlug($tags, $type),
            'tags' => collect($tags)->only([
                'tourism', 'natural', 'leisure', 'fee', 'charge', 'opening_hours',
                'website', 'phone', 'addr:street', 'addr:city',
            ])->all(),
        ];
    }

    private function categorySlug(array $tags, string $type): string
    {
        if ($type === 'museum' || $type === 'artwork') {
            return 'budaya';
        }

        if (($tags['natural'] ?? null) === 'beach' || ($tags['leisure'] ?? null) === 'beach_resort') {
            return 'pantai';
        }

        return 'alam';
    }
}
