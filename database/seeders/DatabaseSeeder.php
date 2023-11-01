<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        DB::table('sites')->insert([
            'domain_name' => 'site1.dev',
            'title' => 'site1',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        DB::table('sites')->insert([
            'domain_name' => 'site2.dev',
            'title' => 'site2',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        DB::table('sites')->insert([
            'domain_name' => 'site3.dev',
            'title' => 'site3',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        DB::table('posts')->insert([
            'site' => 'site1.dev',
            'title' => 'This is a post for site1',
            'content' => 'This is test text. Test text. Testing...',
            'human_readable_url' => 'this-is-a-post-for-site1.html',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        \App\Models\Site::factory(3)->create();
        \App\Models\Post::factory(3)->create();
    }
}
