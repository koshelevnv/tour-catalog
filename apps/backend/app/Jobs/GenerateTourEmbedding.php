<?php

namespace App\Jobs;

use App\Models\Tour;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenerateTourEmbedding implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private Tour $tour) {}

    public function handle(): void
    {
        $text = trim($this->tour->title . ' ' . $this->tour->description);
        $url = config('services.embeddings.url');

        $response = Http::timeout(30)->post("{$url}/embed", ['text' => $text]);

        if ($response->failed()) {
            Log::warning("Embedding failed for tour {$this->tour->id}: " . $response->status());
            return;
        }

        $embedding = $response->json('embedding');
        $vectorStr = '[' . implode(',', $embedding) . ']';

        DB::statement(
            'UPDATE tours SET embedding = ?::vector WHERE id = ?',
            [$vectorStr, $this->tour->id]
        );
    }
}
