<?php

namespace Database\Factories;

use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Post::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $domain = $this->faker->unique()->domainName();
        $title = $this->faker->sentence();
        return [
            'site' => $domain,
            'title' => $title,
            'content' => implode("\n\n\n", $this->faker->paragraphs()),
            'human_readable_url' => strtolower(str_replace(" ", "-", $title)) . 'html',
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}
