<?php

namespace App\Console\Commands;

use App\Support\ProductionConfigurationValidator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProductionPreflightCommand extends Command
{
    protected $signature = 'production:preflight
                            {--database : Validate database connectivity with SELECT 1}';

    protected $description = 'Validate security-critical production configuration without exposing secret values.';

    public function handle(ProductionConfigurationValidator $validator): int
    {
        $violations = $validator->violations();

        if ($violations !== []) {
            foreach ($violations as $violation) {
                $this->error($violation);
            }

            return self::FAILURE;
        }

        $this->info('Production configuration preflight passed.');

        if ($this->option('database')) {
            try {
                DB::select('select 1');
            } catch (Throwable) {
                $this->error('Database connectivity check failed.');

                return self::FAILURE;
            }

            $this->info('Database connectivity check passed.');
        }

        return self::SUCCESS;
    }
}
