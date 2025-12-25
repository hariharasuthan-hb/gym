<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EntityRejected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $user,
        public string $entityType,
        public mixed $entity,
        public ?string $reason = null
    ) {
    }
}

