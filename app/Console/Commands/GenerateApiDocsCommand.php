<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Command to generate API documentation using Scramble
 */
class GenerateApiDocsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'docs:generate {--force : Force regeneration even if file exists}';

    /**
     * The console command description.
     */
    protected $description = 'Generate API documentation using Scramble';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Generating API documentation...');

        try {
            // Run the Scramble export command
            Artisan::call('scramble:export');

            // Move the generated file to the correct location
            $sourcePath = base_path('api.json');
            $targetPath = storage_path('app/scramble/api.json');

            if (file_exists($sourcePath)) {
                // Ensure the target directory exists
                if (!is_dir(dirname($targetPath))) {
                    mkdir(dirname($targetPath), 0755, true);
                }

                // Move the file
                rename($sourcePath, $targetPath);

                $this->info('✅ API documentation generated successfully!');
                $this->info("📁 Location: {$targetPath}");
                $this->info('🌐 View at: /docs/api');
                $this->info('📄 JSON spec at: /docs/api.json');

                return Command::SUCCESS;
            } else {
                $this->error('❌ Failed to generate API documentation');
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('❌ Error generating API documentation: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
