<?php

namespace AcMarche\Theme\Lib;

use Symfony\Component\HttpClient\DecoratorTrait;
use Symfony\Component\HttpClient\HttpClient;

class Ai
{
    use DecoratorTrait;

    public function __construct()
    {

    }

    private function connect():void
    {
        $this->client = HttpClient::createForBaseUri($_ENV['OPENAI_API_URL']);
    }

    /**
     * curl https://api.openai.com/v1/audio/speech \
     * -H "Authorization: Bearer $OPENAI_API_KEY" \
     * -H "Content-Type: application/json" \
     * -d '{
     * "model": "gpt-4o-mini-tts",
     * "input": "Today is a wonderful day to build something people love!",
     * "voice": "coral",
     * "instructions": "Speak in a cheerful and positive tone."
     * }' \
     * --output speech.mp3
     * @return void
     */
    public function textToSpeech()
    {

    }

}