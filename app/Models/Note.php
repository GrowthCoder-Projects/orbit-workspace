<?php

namespace App\Models;

use App\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Note extends Model
{
    use BelongsToUser;
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'folder_id',
        'title',
        'content',
        'is_favorite',
        'is_archived',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_favorite' => 'boolean',
            'is_archived' => 'boolean',
        ];
    }

    /**
     * Get the folder that contains this note.
     *
     * @return BelongsTo<Folder, $this>
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    /**
     * Notes that link to this note.
     *
     * @return BelongsToMany<Note, $this>
     */
    public function backlinks(): BelongsToMany
    {
        return $this->belongsToMany(
            Note::class,
            'note_backlinks',
            'target_note_id',
            'source_note_id'
        )->withTimestamps();
    }

    /**
     * Notes that this note links to.
     *
     * @return BelongsToMany<Note, $this>
     */
    public function outgoingLinks(): BelongsToMany
    {
        return $this->belongsToMany(
            Note::class,
            'note_backlinks',
            'source_note_id',
            'target_note_id'
        )->withTimestamps();
    }
}
