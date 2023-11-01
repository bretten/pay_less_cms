<?php

namespace Database\Factories;

use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

class SiteFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Site::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $domain = $this->faker->unique()->domainName();
        return [
            'domain_name' => $domain,
            'title' => $domain,
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}
