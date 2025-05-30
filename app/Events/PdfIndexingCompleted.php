<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PdfIndexingCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $sessionId,
        public readonly array $extractedKeywords,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("pdf-indexing.{$this->sessionId}");
    }

    public function broadcastAs(): string
    {
        return 'completed';
    }

    public function broadcastWith(): array
    {
        return [
            'keywords' => $this->extractedKeywords,
            'keyword_count' => count($this->extractedKeywords),
        ];
    }
}
