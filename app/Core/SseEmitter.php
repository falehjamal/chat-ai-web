<?php

namespace App\Core;

class SseEmitter
{
    public function start()
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        ob_implicit_flush(true);
    }

    public function send($data, $event = 'message')
    {
        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($encoded === false) {
            $encoded = '{"error":"Respons tidak bisa dikirim."}';
        }

        echo 'event: ' . $event . "\n";
        echo 'data: ' . $encoded . "\n\n";
        flush();
    }
}
