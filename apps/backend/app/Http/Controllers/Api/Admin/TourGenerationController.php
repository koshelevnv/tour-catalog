<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\TourType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TourGenerationController extends Controller
{
    public function generate(Request $request)
    {
        $request->validate(['prompt' => 'required|string|max:500']);

        $provider = Setting::where('key', 'llm_provider')->value('value') ?: 'anthropic';

        return match ($provider) {
            'openrouter' => $this->generateViaOpenRouter($request->prompt),
            default      => $this->generateViaAnthropic($request->prompt),
        };
    }

    public const DEFAULT_SYSTEM_PROMPT = <<<'TEXT'
Ты — генератор данных для каталога туров. На основе запроса пользователя возвращай ТОЛЬКО JSON-объект без markdown, без пояснений.

Формат ответа (строго соблюдай):
{
  "title": "Название тура",
  "description": "<p>Подробное описание тура на русском языке.</p><p>Детали маршрута, программа, особенности.</p>",
  "duration_days": 7,
  "type_id": 1,
  "waypoints": [
    {"lat": 51.9581, "lng": 85.9603, "label": "Горно-Алтайск"},
    {"lat": 51.4761, "lng": 86.0847, "label": "Чемал"}
  ],
  "variants": [
    {"date": "YYYY-MM-DD", "price": 45000},
    {"date": "YYYY-MM-DD", "price": 48000},
    {"date": "YYYY-MM-DD", "price": 50000}
  ]
}

Правила:
- Если пользователь указал конкретные значения (адреса, координаты, даты, цены, название и т.п.) — используй их точно, не заменяй своими
- Если значение не указано — генерируй самостоятельно, исходя из контекста запроса
- Координаты — реальные географические точки (точность до 4 знаков); если пользователь дал адрес или название места — используй его координаты
- 4-6 точек маршрута в логичном порядке
- type_id выбрать из списка доступных типов (передаётся отдельно), наиболее подходящий к запросу
- Все текстовые поля на русском языке
- Цены реалистичные для данного типа тура; если указаны пользователем — использовать точно
- description обязательно в HTML: абзацы в <p>, выделения в <strong> или <em>, списки в <ul><li>; минимум 3–4 абзаца
- variants — 3 варианта с реальными ближайшими датами (даты передаются отдельно); если пользователь задал конкретные даты — использовать их
TEXT;

    private function buildSystemPrompt(): string
    {
        $tourTypes = TourType::all(['id', 'name', 'slug']);
        $typesJson = $tourTypes->map(fn($t) => ['id' => $t->id, 'name' => $t->name, 'slug' => $t->slug])->toJson(JSON_UNESCAPED_UNICODE);

        $d1 = now()->addDays(30)->toDateString();
        $d2 = now()->addDays(60)->toDateString();
        $d3 = now()->addDays(90)->toDateString();

        $instructions = trim(Setting::where('key', 'llm_system_prompt')->value('value') ?? '');
        if (!$instructions) {
            $instructions = self::DEFAULT_SYSTEM_PROMPT;
        }

        return $instructions . "\n\nДоступные типы туров: {$typesJson}\n\nДля поля date в variants используй ближайшие реальные даты: {$d1}, {$d2}, {$d3}";
    }

    private function parseContent(string $content): mixed
    {
        $content = preg_replace('/^```(?:json)?\s*/m', '', $content);
        $content = preg_replace('/\s*```$/m', '', $content);
        return json_decode(trim($content), true);
    }

    private function generateViaAnthropic(string $prompt)
    {
        $apiKey = Setting::where('key', 'anthropic_api_key')->value('value')
            ?: config('services.anthropic.key');

        if (!$apiKey) {
            return response()->json(['error' => 'ANTHROPIC_API_KEY не настроен'], 503);
        }

        try {
            $response = Http::withHeaders([
                'x-api-key'         => $apiKey,
                'anthropic-version' => config('services.anthropic.version'),
                'content-type'      => 'application/json',
            ])->timeout(30)->post(config('services.anthropic.url'), [
                'model'      => config('services.anthropic.model'),
                'max_tokens' => config('services.anthropic.max_tokens'),
                'system'     => $this->buildSystemPrompt(),
                'messages'   => [['role' => 'user', 'content' => $prompt]],
            ]);

            if (!$response->successful()) {
                Log::error('Anthropic API error', ['status' => $response->status(), 'body' => $response->body()]);
                return response()->json(['error' => 'Ошибка Anthropic API: ' . $response->status()], 502);
            }

            $rawContent = $response->json('content.0.text');
            $data = $this->parseContent($rawContent ?? '');
            if (!$data || !isset($data['title'], $data['duration_days'])) {
                Log::error('Invalid Anthropic response', ['body' => $response->body()]);
                return response()->json(['error' => 'Некорректный ответ от LLM'], 502);
            }

            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('Anthropic generation failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Ошибка генерации: ' . $e->getMessage()], 500);
        }
    }

    private function generateViaOpenRouter(string $prompt)
    {
        $apiKey = Setting::where('key', 'openrouter_api_key')->value('value');
        if (!$apiKey) {
            return response()->json(['error' => 'OPENROUTER_API_KEY не настроен'], 503);
        }

        $model = Setting::where('key', 'openrouter_model')->value('value')
            ?: config('services.openrouter.default_model');

        try {
            $response = Http::asJson()->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'HTTP-Referer'  => config('app.url'),
                'X-Title'       => config('app.name'),
            ])->timeout(90)->post(config('services.openrouter.url'), [
                'model'      => $model,
                'max_tokens' => config('services.openrouter.max_tokens'),
                'messages'   => [
                    ['role' => 'system', 'content' => $this->buildSystemPrompt()],
                    ['role' => 'user',   'content' => $prompt],
                ],
            ]);

            if (!$response->successful()) {
                $body    = $response->json();
                $detail  = $body['error']['message'] ?? $body['message'] ?? $response->body();
                Log::error('OpenRouter API error', ['status' => $response->status(), 'body' => $response->body()]);
                return response()->json(['error' => "OpenRouter {$response->status()}: {$detail}"], 502);
            }

            $rawContent = $response->json('choices.0.message.content');
            $content = is_array($rawContent)
                ? collect($rawContent)->where('type', 'text')->pluck('text')->join('')
                : ($rawContent ?? '');

            // Reasoning-only models (e.g. deepseek:free) put output in reasoning, content=null
            if ($content === '') {
                $content = $response->json('choices.0.message.reasoning') ?? '';
            }

            $data = $this->parseContent($content);
            if (!$data || !isset($data['title'], $data['duration_days'])) {
                $actualModel = $response->json('model', $model);
                $isReasoningOnly = $response->json('choices.0.message.content') === null;
                if ($isReasoningOnly) {
                    $hint = "Модель «{$actualModel}» не возвращает текстовый ответ (reasoning-режим). Выберите другую модель, например openai/gpt-4o-mini или anthropic/claude-3-haiku";
                } elseif ($actualModel !== $model) {
                    $hint = "OpenRouter подменил модель на «{$actualModel}» — укажите корректный ID модели";
                } else {
                    $hint = "Модель «{$actualModel}» вернула некорректный JSON — попробуйте другую модель";
                }
                Log::error('Invalid OpenRouter response', ['model' => $actualModel, 'body' => $response->body()]);
                return response()->json(['error' => $hint], 502);
            }

            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('OpenRouter generation failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Ошибка генерации: ' . $e->getMessage()], 500);
        }
    }
}
