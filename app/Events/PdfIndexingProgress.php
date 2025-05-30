<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PdfIndexingProgress implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $sessionId,
        public readonly int $currentPage,
        public readonly int $totalPages,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("pdf-indexing.{$this->sessionId}");
    }

    public function broadcastAs(): string
    {
        return 'progress';
    }

    public function broadcastWith(): array
    {
        return [
            'current_page' => $this->currentPage,
            'total_pages' => $this->totalPages,
            'percentage' => round(($this->currentPage / $this->totalPages) * 100),
        ];
    }
}
