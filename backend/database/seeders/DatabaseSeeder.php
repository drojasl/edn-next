<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Course;
use App\Models\CourseNode;
use App\Models\CourseNodeOption;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create an Entrepreneur (was User)
        $user = \App\Models\Entrepreneur::create([
            'name' => 'Juan',
            'last_name' => 'Pérez',
            'email' => 'juan@example.com',
            'password' => Hash::make('password123'),
            'codigo_amway' => '12345',
            'is_account_holder' => true,
            'is_active' => true,
            'slug' => 'juan-perez',
        ]);

        // 1.1 Create Social Links for the entrepreneur (EAV Pattern)
        $socialLinks = [
            ['platform' => 'whatsapp', 'value' => '573001234567'],
            ['platform' => 'instagram', 'value' => 'juanperez_oficial'],
            ['platform' => 'facebook', 'value' => 'juanperezfb'],
            ['platform' => 'email_contact', 'value' => 'contacto@juanperez.com'],
            ['platform' => 'website', 'value' => 'https://juanperez.com'],
        ];

        foreach ($socialLinks as $link) {
            \App\Models\UserSocialLink::create([
                'user_id' => $user->id,
                'platform' => $link['platform'],
                'value' => $link['value'],
            ]);
        }

        // 2. Create a Course for this user
        $course = Course::create([
            'user_id' => $user->id,
            'title' => 'Bienvenidos al Éxito',
            'slug' => 'bienvenidos-al-exito',
            'description' => 'Un curso introductorio para nuevos empresarios.',
            'is_active' => true,
        ]);

        // 3. Create Nodes for the course
        $node1 = CourseNode::create([
            'course_id' => $course->id,
            'type' => 'video',
            'title' => 'Video 1: Bienvenidos',
            'slug' => 'bienvenido-al-curso',
            'video_url' => 'https://example.com/video1.mp4',
            'position' => 1,
            'is_start' => true,
        ]);

        $node2 = CourseNode::create([
            'course_id' => $course->id,
            'type' => 'video',
            'title' => 'Video 2: Plan de Negocios',
            'slug' => 'plan-de-negocios',
            'video_url' => 'https://example.com/video2.mp4',
            'position' => 2,
        ]);

        $node3 = CourseNode::create([
            'course_id' => $course->id,
            'type' => 'html',
            'title' => 'Felicidades',
            'slug' => 'felicidades-finalizado',
            'content' => ['body' => 'Has completado la introducción.'],
            'position' => 3,
            'is_end' => true,
        ]);

        // 4. Create Navigation Options (Linear flow)
        CourseNodeOption::create([
            'course_node_id' => $node1->id,
            'label' => 'Siguiente',
            'next_node_id' => $node2->id,
        ]);

        CourseNodeOption::create([
            'course_node_id' => $node2->id,
            'label' => 'Siguiente',
            'next_node_id' => $node3->id,
        ]);

        // 5. Create an Access Code for this course
        \App\Models\AccessCode::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'code' => 'ABC123',
            'is_active' => true,
        ]);
    }
}
