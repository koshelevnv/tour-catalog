<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Admin\TourGenerationController;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    private const SETTING_KEYS = [
        'yandex_maps_key',
        'anthropic_api_key',
        'llm_provider',
        'openrouter_api_key',
        'openrouter_model',
        'llm_system_prompt',
        'meta_title',
        'meta_description',
        'og_image',
        'home_per_page',
        'home_load_mode',
        'catalog_per_page',
        'catalog_load_mode',
        'search_per_page',
        'search_load_mode',
    ];

    public function publicIndex(): JsonResponse
    {
        $keys = ['yandex_maps_key', 'home_per_page', 'home_load_mode', 'catalog_per_page', 'catalog_load_mode', 'search_per_page', 'search_load_mode', 'meta_title', 'meta_description', 'og_image'];
        $settings = Setting::whereIn('key', $keys)->get()->pluck('value', 'key');

        return response()->json([
            'yandex_maps_key'   => $settings['yandex_maps_key'] ?? '',
            'home_per_page'     => (int) ($settings['home_per_page'] ?? 12),
            'home_load_mode'    => $settings['home_load_mode'] ?? 'infinite',
            'catalog_per_page'  => (int) ($settings['catalog_per_page'] ?? 12),
            'catalog_load_mode' => $settings['catalog_load_mode'] ?? 'pagination',
            'search_per_page'   => (int) ($settings['search_per_page'] ?? 12),
            'search_load_mode'  => $settings['search_load_mode'] ?? 'pagination',
            'meta_title'        => $settings['meta_title'] ?? '',
            'meta_description'  => $settings['meta_description'] ?? '',
            'og_image'          => $settings['og_image'] ?? '',
        ]);
    }

    public function publicTranslations(): JsonResponse
    {
        $overrides = Setting::where('key', 'like', 'lang.%')->get()
            ->mapWithKeys(fn ($s) => [substr($s->key, 5) => $s->value])
            ->toArray();

        return response()->json($overrides);
    }

    public function updateTranslations(Request $request): JsonResponse
    {
        $data = $request->validate([
            'translations'   => 'required|array',
            'translations.*' => 'nullable|string|max:1000',
        ]);

        foreach ($data['translations'] as $rawKey => $value) {
            $settingKey = 'lang.' . preg_replace('/[^a-zA-Z0-9._]/', '', $rawKey);
            if ($value === null || $value === '') {
                Setting::where('key', $settingKey)->delete();
            } else {
                Setting::updateOrCreate(['key' => $settingKey], ['value' => $value]);
            }
        }

        return $this->publicTranslations();
    }

    public function index(): JsonResponse
    {
        $settings = Setting::all()->pluck('value', 'key')->toArray();
        $user = User::first();
        $settings['account_email'] = $user?->email ?? '';

        if (empty($settings['anthropic_api_key'])) {
            $settings['anthropic_api_key'] = config('services.anthropic.key') ?? '';
        }
        if (empty($settings['llm_system_prompt'])) {
            $settings['llm_system_prompt'] = TourGenerationController::DEFAULT_SYSTEM_PROMPT;
        }

        return response()->json($settings);
    }

    public function uploadOgImage(Request $request): JsonResponse
    {
        $request->validate(['image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120']);

        $old = Setting::where('key', 'og_image')->value('value');
        if ($old && str_starts_with($old, url('storage/og/'))) {
            $relativePath = 'og/' . basename($old);
            Storage::disk('public')->delete($relativePath);
        }

        $path = $request->file('image')->store('og', 'public');
        $url  = asset('storage/' . $path);

        Setting::updateOrCreate(['key' => 'og_image'], ['value' => $url]);

        return response()->json(['url' => $url]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'yandex_maps_key'  => 'nullable|string|max:255',
            'anthropic_api_key'=> 'nullable|string|max:255',
            'llm_provider'     => 'nullable|string|in:anthropic,openrouter',
            'openrouter_api_key'=> 'nullable|string|max:255',
            'openrouter_model'   => 'nullable|string|max:255',
            'llm_system_prompt' => 'nullable|string|max:5000',
            'meta_title'         => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'og_image'         => 'nullable|string|max:500',
            'account_email'    => 'nullable|email|max:255',
            'account_password' => 'nullable|string|min:8',
            'home_per_page'    => 'nullable|integer|min:1|max:100',
            'home_load_mode'   => 'nullable|string|in:infinite,load_more,pagination',
            'catalog_per_page' => 'nullable|integer|min:1|max:100',
            'catalog_load_mode'=> 'nullable|string|in:infinite,load_more,pagination',
            'search_per_page'  => 'nullable|integer|min:1|max:100',
            'search_load_mode' => 'nullable|string|in:infinite,load_more,pagination',
        ]);

        if (! empty($data['account_email']) || ! empty($data['account_password'])) {
            $user = User::first();
            if ($user) {
                if (! empty($data['account_email'])) {
                    $user->email = $data['account_email'];
                }
                if (! empty($data['account_password'])) {
                    $user->password = Hash::make($data['account_password']);
                }
                $user->save();
            }
        }

        foreach (self::SETTING_KEYS as $key) {
            if (array_key_exists($key, $data)) {
                Setting::updateOrCreate(['key' => $key], ['value' => $data[$key] ?? '']);
            }
        }

        return $this->index();
    }
}
