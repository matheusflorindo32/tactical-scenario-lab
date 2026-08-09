<?php

namespace App\Console\Commands;

use App\Support\ProductionConfigurationValidator;
use Illuminate\Console\Command;
use LogicException;

class ProductionPreflightCommand extends Command
{
    protected $signature = 'production:preflight';

    protected $description = 'Validate security-critical production configuration without exposing secret values.';

    public function handle(ProductionConfigurationValidator $validator): int
    {
        try {
            $validator->assertSafe();
        } catch (LogicException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Production configuration preflight passed.');

        return self::SUCCESS;
    }
}
