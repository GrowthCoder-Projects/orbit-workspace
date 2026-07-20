<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class NoteBacklink extends Pivot
{
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'note_backlinks';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'source_note_id',
        'target_note_id',
    ];
}
