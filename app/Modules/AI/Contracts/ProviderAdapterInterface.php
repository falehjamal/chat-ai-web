<?php

namespace App\Modules\AI\Contracts;

interface ProviderAdapterInterface
{
    public function stream(array $modeConfig, array $messages, callable $onChunk, callable $onComplete);
}
