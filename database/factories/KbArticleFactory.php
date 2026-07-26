<?php

namespace Database\Factories;

use App\Models\KbArticle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<KbArticle>
 */
class KbArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->sentence();

        return [
            'user_id' => User::factory(),
            'parent_article_id' => null,
            'title' => $title,
            'slug' => Str::slug($title),
            'content' => '<h1>'.$title.'</h1><p>'.$this->faker->paragraph().'</p><pre><code class="language-javascript">console.log("Hello, World!");</code></pre>',
        ];
    }
}
