<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TrainerStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $trainer,
        public string $status,
        public ?string $reason = null
    ) {
    }
}

