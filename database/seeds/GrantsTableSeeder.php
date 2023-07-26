<?php

use Illuminate\Database\Seeder;

class GrantsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //ced can view/edit all
        DB::table('grants')->insert([
            'role_name' => "DIPENDENTE",
            'department_name' => "SVILUPPO SOFTWARE",
            'grant' => 'VIEW_ALL'
        ]);
    }
}
