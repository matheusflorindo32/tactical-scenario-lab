<?php

namespace App\Console\Commands;

use App\Support\ProductionConfigurationValidator;
use Illuminate\Console\Command;

class ProductionPreflightCommand extends Command
{
    protected $signature = 'production:preflight';

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

        return self::SUCCESS;
    }
}
