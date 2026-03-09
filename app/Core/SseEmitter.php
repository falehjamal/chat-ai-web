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
        echo 'event: ' . $event . "\n";
        echo 'data: ' . json_encode($data) . "\n\n";
        flush();
    }
}
