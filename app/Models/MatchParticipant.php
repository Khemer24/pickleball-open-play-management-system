<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchParticipant extends Model
{
    public $timestamps = false;

    protected $fillable = ['match_id', 'session_player_id', 'team'];

    public function match(): BelongsTo
    {
        return $this->belongsTo(MatchGame::class, 'match_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(SessionPlayer::class, 'session_player_id');
    }
}
