<?php

namespace App\Modules\AI\Application;

use App\Modules\AI\Infrastructure\OpenAICompatibleProvider;
use InvalidArgumentException;

class ProviderRegistry
{
    public function resolve($driver)
    {
        switch ($driver) {
            case 'openai_compatible':
                return new OpenAICompatibleProvider();
            default:
                throw new InvalidArgumentException('Driver provider tidak didukung: ' . $driver);
        }
    }
}
