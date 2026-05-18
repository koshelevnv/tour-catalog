<?php

namespace App\Console\Commands;

use App\Jobs\GenerateTourEmbedding;
use App\Models\Tour;
use Illuminate\Console\Command;

class GenerateTourEmbeddings extends Command
{
    protected $signature = 'tours:generate-embeddings {--all : Regenerate even existing embeddings}';
    protected $description = 'Generate embeddings for tours';

    public function handle(): void
    {
        $query = $this->option('all')
            ? Tour::query()
            : Tour::whereNull('embedding');

        $tours = $query->get();

        if ($tours->isEmpty()) {
            $this->info('All tours already have embeddings.');
            return;
        }

        $this->info("Generating embeddings for {$tours->count()} tours...");
        $bar = $this->output->createProgressBar($tours->count());
        $bar->start();

        foreach ($tours as $tour) {
            GenerateTourEmbedding::dispatchSync($tour);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Done.');
    }
}
