<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            EventCategorySeeder::class,
            EventSeeder::class,
            AttendeeSeeder::class,
            AttendanceSeeder::class,
            EventAdminSeeder::class,
            QRCodeSeeder::class,
            QRCodeLogSeeder::class,
            // EventDetailSeeder::class,
            // EventPhotoSeeder::class,
            // EventRegistrationLinkSeeder::class,
            UserLogSeeder::class,
        ]);
    }

}
