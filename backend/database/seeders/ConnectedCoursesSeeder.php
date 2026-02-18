<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\CourseNode;
use App\Models\CourseNodeOption;
use App\Models\Entrepreneur;

class ConnectedCoursesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the existing user/entrepreneur (Juan Perez)
        $user = Entrepreneur::where('email', 'juan@example.com')->first();

        if (!$user) {
            $this->command->error('User not found. Please run DatabaseSeeder first.');
            return;
        }

        // Get the first course
        $firstCourse = Course::where('slug', 'bienvenidos-al-exito')->first();

        if (!$firstCourse) {
            $this->command->error('First course not found.');
            return;
        }

        // Create a Second Course
        $secondCourse = Course::firstOrCreate(
            ['slug' => 'tecnicas-de-ventas-avanzadas'],
            [
                'user_id' => $user->id,
                'title' => 'Técnicas de Ventas Avanzadas',
                'description' => 'Aprende a cerrar ventas como un profesional.',
                'is_active' => true,
            ]
        );

        // Link First Course -> Second Course
        $firstCourse->update(['next_course_id' => $secondCourse->id]);

        $this->command->info('Connected: "' . $firstCourse->title . '" -> "' . $secondCourse->title . '"');

        // Add Nodes to Second Course
        $s2_node1 = CourseNode::create([
            'course_id' => $secondCourse->id,
            'type' => 'video',
            'title' => 'Introducción a las Ventas',
            'slug' => 'introduccion-ventas',
            'video_url' => 'https://example.com/ventas1.mp4',
            'pos_x' => -100,
            'pos_y' => 0,
            'is_start' => true,
        ]);

        $s2_node2 = CourseNode::create([
            'course_id' => $secondCourse->id,
            'type' => 'html',
            'title' => 'El Cierre',
            'content' => ['body' => '<h1>El Cierre</h1><p>Técnicas de cierre...</p>'],
            'pos_x' => 100,
            'pos_y' => 0,
            'is_end' => true,
        ]);
        // Create 3 More Courses
        // Create 3 More Courses
        $course3 = Course::firstOrCreate(
            ['slug' => 'marketing-digital-mlm'],
            [
                'user_id' => $user->id,
                'title' => 'Marketing Digital para Redes de Mercadeo',
                'description' => 'Domina las redes sociales para crecer tu negocio.',
                'is_active' => true,
            ]
        );

        $course4 = Course::firstOrCreate(
            ['slug' => 'liderazgo-equipos'],
            [
                'user_id' => $user->id,
                'title' => 'Liderazgo y Gestión de Equipos',
                'description' => 'Técnicas para liderar equipos de alto rendimiento.',
                'is_active' => true,
            ]
        );

        $course5 = Course::firstOrCreate(
            ['slug' => 'finanzas-personales'],
            [
                'user_id' => $user->id,
                'title' => 'Finanzas Personales para Emprendedores',
                'description' => 'Administra tus ingresos y maximiza tus ganancias.',
                'is_active' => true,
            ]
        );

        // Link Course 2 -> Course 3 (Extending the chain)
        $secondCourse->update(['next_course_id' => $course3->id]);

        $this->command->info('Created 3 more courses.');
        $this->command->info('Connected: "' . $secondCourse->title . '" -> "' . $course3->title . '"');

        // Link Nodes
        CourseNodeOption::create([
            'course_node_id' => $s2_node1->id,
            'label' => 'Ir al Cierre',
            'next_node_id' => $s2_node2->id,
        ]);

        // Create an Access Code for the second course (optional, but good for testing)
        \App\Models\AccessCode::firstOrCreate(
            ['code' => 'SALES2026'],
            [
                'user_id' => $user->id,
                'course_id' => $secondCourse->id,
                'is_active' => true,
            ]
        );

        $this->command->info('Second course created and linked successfully.');
    }
}
