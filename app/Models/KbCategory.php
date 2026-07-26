<?php

namespace App\Models;

use App\Concerns\BelongsToUser;
use Database\Factories\KbCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class KbCategory extends Model
{
    use BelongsToUser;

    /** @use HasFactory<KbCategoryFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'slug',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(static function (KbCategory $category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });

        static::updating(static function (KbCategory $category) {
            if ($category->isDirty('name') && ! $category->isDirty('slug')) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    /**
     * Get the articles belonging to this category.
     *
     * @return BelongsToMany<KbArticle, $this>
     */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(KbArticle::class, 'kb_category_article');
    }
}
