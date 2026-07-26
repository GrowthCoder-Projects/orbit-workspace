<?php

namespace App\Models;

use App\Concerns\BelongsToUser;
use Database\Factories\KbArticleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class KbArticle extends Model
{
    use BelongsToUser;

    /** @use HasFactory<KbArticleFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'parent_article_id',
        'title',
        'slug',
        'content',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(static function (KbArticle $article) {
            if (empty($article->slug)) {
                $article->slug = Str::slug($article->title);
            }
        });

        static::updating(static function (KbArticle $article) {
            if ($article->isDirty('title') && ! $article->isDirty('slug')) {
                $article->slug = Str::slug($article->title);
            }
        });
    }

    /**
     * Get the parent article.
     *
     * @return BelongsTo<KbArticle, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(KbArticle::class, 'parent_article_id');
    }

    /**
     * Get the child articles.
     *
     * @return HasMany<KbArticle, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(KbArticle::class, 'parent_article_id');
    }

    /**
     * Get the categories this article belongs to.
     *
     * @return BelongsToMany<KbCategory, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(KbCategory::class, 'kb_category_article');
    }
}
