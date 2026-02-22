<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Closure;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SseStream
{
    /** @var list<Closure> */
    private array $generators = [];

    /**
     * Register a generator callback that yields SSE events.
     */
    public function through(Closure $callback): static
    {
        $this->generators[] = $callback;

        return $this;
    }

    /**
     * Convert to a StreamedResponse with SSE headers.
     */
    public function toResponse(): StreamedResponse
    {
        return new StreamedResponse(function (): void {
            @ini_set('output_buffering', 'off');
            @ini_set('zlib.output_compression', false);

            if (function_exists('apache_setenv')) {
                @apache_setenv('no-gzip', '1');
            }

            set_time_limit(0);
            ignore_user_abort(false);

            foreach ($this->generators as $generator) {
                foreach ($generator() as $data) {
                    if (connection_aborted()) {
                        return;
                    }

                    $this->send($data);
                }
            }

            $this->close();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Write a single SSE event and flush.
     *
     * @param  array<string, mixed>  $data
     */
    public static function send(array $data): void
    {
        echo 'data: '.json_encode($data)."\n\n";

        if (ob_get_level() > 0) {
            ob_flush();
        }

        flush();
    }

    /**
     * Send the final "done" event.
     */
    public static function close(): void
    {
        static::send(['type' => 'done']);
    }
}
