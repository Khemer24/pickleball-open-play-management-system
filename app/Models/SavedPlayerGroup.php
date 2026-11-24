<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SavedPlayerGroup extends Model
{
    protected $fillable = ['manager_id', 'name'];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'saved_player_group_members');
    }
}
