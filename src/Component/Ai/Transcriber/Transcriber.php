<?php

namespace App\Component\Ai\Transcriber;

use OpenAI\Client;

final readonly class Transcriber
{
    public function __construct(
        private Client $openai,
        private string $model,
    ) {
    }

    public function transcribe(string $url): string
    {
        $response = $this->openai->audio()->transcribe([
            'model' => $this->model,
            'url' => $url,
            'response_format' => 'json',
            'temperature' => 0,
        ]);

        return $response->text;
    }
}
