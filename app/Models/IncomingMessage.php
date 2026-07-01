<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class IncomingMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'device_id',
        'sender',
        'body',
        'received_at',
        'outbound_message_id',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (IncomingMessage $message) {
            $message->public_id ??= (string) Str::uuid();
        });
    }

    /**
     * @return BelongsTo<Device, $this>
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * @return BelongsTo<Message, $this>
     */
    public function outboundMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'outbound_message_id');
    }
}
