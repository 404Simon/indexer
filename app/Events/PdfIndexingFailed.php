<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PdfIndexingFailed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $sessionId,
        public readonly string $errorMessage,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("pdf-indexing.{$this->sessionId}");
    }

    public function broadcastAs(): string
    {
        return 'failed';
    }

    public function broadcastWith(): array
    {
        return [
            'error_message' => $this->errorMessage,
        ];
    }
}
