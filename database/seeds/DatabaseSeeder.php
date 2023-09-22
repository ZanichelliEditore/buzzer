<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            GrantsTableSeeder::class
        ]);

        DB::table('subscribers')->insert([
            'id' => 1,
            'name' => 'subscriber1',
            'host' => 'http://10.100.0.7/',
            'created_at' => '2019-09-12 15:40:06',
            'updated_at' => '2019-09-12 15:40:06'
        ]);

        DB::table('publishers')->insert([
            'id' => 1,
            'name' => 'test',
            'username' => 'test',
            'password' => Hash::make('pwd'),
            'host' => 'http://test.it',
            'created_at' => '2019-09-12 15:40:06',
            'updated_at' => '2019-09-12 15:40:06'
        ]);
        DB::table('channels')->insert([
            'id' => 1,
            'name' => 'me',
            'created_at' => '2019-09-12 15:40:06',
            'updated_at' => '2019-09-12 15:40:06'
        ]);
        DB::table('channel_publish')->insert([
            'id' => 1,
            'channel_id' => 1,
            'publisher_id' => 1,
            'created_at' => '2019-09-12 15:40:06',
            'updated_at' => '2019-09-12 15:40:06'
        ]);

        DB::table('channel_subscribe')->insert([
            'id' => 1,
            'channel_id' => 1,
            'subscriber_id' => 1,
            'endpoint' => 'api/none',
            'authentication' => 'NONE',
            'created_at' => '2019-09-12 15:40:06',
            'updated_at' => '2019-09-12 15:40:06'
        ]);
        DB::table('channel_subscribe')->insert([
            'id' => 2,
            'channel_id' => 1,
            'subscriber_id' => 1,
            'endpoint' => 'api/basic',
            'authentication' => 'BASIC',
            'username' => 'user',
            'password' => encrypt('pwd'),
            'created_at' => '2019-09-12 15:40:06',
            'updated_at' => '2019-09-12 15:40:06'
        ]);
        DB::table('channel_subscribe')->insert([
            'id' => 3,
            'channel_id' => 1,
            'subscriber_id' => 1,
            'endpoint' => 'api/oauth',
            'authentication' => 'OAUTH2',
            'username' => 1,
            'password' => openssl_encrypt('secretOAuth2Example', 'AES256', env('APP_KEY'), $options = 0, env('CRYPT_KEY')),
            'created_at' => '2019-09-12 15:40:06',
            'updated_at' => '2019-09-12 15:40:06'
        ]);
        DB::table('oauth_clients')->insert([
            'id' => 1,
            'secret' => 'secretOAuth2Example',
            'name' => 'test',
            'redirect' => 'http://test.example',
            'personal_access_client' => 0,
            'password_client' => 0,
            'revoked' => 0
        ]);
    }
}
