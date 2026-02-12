<?php

namespace Database\Seeders;

use App\Models\Faculty;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FacultySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $rows = [
            ['id' => 1,  'name' => 'Facultad de Agronomía', 'abrev' => 'FAG'],
            ['id' => 2,  'name' => 'Facultad de Ciencias Biológicas', 'abrev' => 'FCCBB'],
            ['id' => 3,  'name' => 'Facultad de Ciencias Económicas, Administrativas y Contables', 'abrev' => 'FACEAC'],
            ['id' => 4,  'name' => 'Facultad de Ciencias Físicas y Matemáticas', 'abrev' => 'FACFYM'],
            ['id' => 5,  'name' => 'Facultad de Ciencias Histórico Sociales y Educación', 'abrev' => 'FACHSE'],
            ['id' => 6,  'name' => 'Facultad de Derecho y Ciencias Políticas', 'abrev' => 'FDCCPP'],
            ['id' => 7,  'name' => 'Facultad de Enfermería', 'abrev' => 'FE'],
            ['id' => 8,  'name' => 'Facultad de Ingeniería Agrícola', 'abrev' => 'FIA'],
            ['id' => 9,  'name' => 'Facultad de Ingeniería Civil, de Sistemas y de Arquitectura', 'abrev' => 'FICSA'],
            ['id' => 10, 'name' => 'Facultad de Ingeniería Mecánica y Eléctrica', 'abrev' => 'FIME'],
            ['id' => 11, 'name' => 'Facultad de Medicina Humana', 'abrev' => 'FMH'],
            ['id' => 12, 'name' => 'Facultad de Medicina Veterinaria', 'abrev' => 'FMV'],
            ['id' => 13, 'name' => 'Facultad de Ingeniería Química e Industrias Alimentarias', 'abrev' => 'FIQIA'],
            ['id' => 14, 'name' => 'Facultad de Zootecnia', 'abrev' => 'FIZ'],
        ];

        $rowChildren = [
            ['id' => 1, 'faculty_id' => 1, 'name' => "Escuela Profesional de Agronomía"],
            ['id' => 2, 'faculty_id' => 2, 'name' => "Escuela Profesional de Ciencias Biológicas"],
            ['id' => 3, 'faculty_id' => 3, 'name' => "Escuela Profesional de Administración"],
            ['id' => 4, 'faculty_id' => 3, 'name' => "Escuela Profesional de Comercio y Negocios Internacionales"],
            ['id' => 5, 'faculty_id' => 3, 'name' => "Escuela Profesional de Contabilidad"],
            ['id' => 6, 'faculty_id' => 3, 'name' => "Escuela Profesional de Economía"],
            ['id' => 7, 'faculty_id' => 4, 'name' => "Escuela Profesional de Estadística"],
            ['id' => 8, 'faculty_id' => 4, 'name' => "Escuela Profesional de Física"],
            ['id' => 9, 'faculty_id' => 4, 'name' => "Escuela Profesional de Ingeniería en Computación e Informática"],
            ['id' => 10, 'faculty_id' => 4, 'name' => "Escuela Profesional de Ingeniería Electrónica"],
            ['id' => 11, 'faculty_id' => 4, 'name' => "Escuela Profesional de Matemáticas"],
            ['id' => 12, 'faculty_id' => 5, 'name' => "Escuela Profesional de Arqueología"],
            ['id' => 13, 'faculty_id' => 5, 'name' => "Escuela Profesional de Arte"],
            ['id' => 14, 'faculty_id' => 5, 'name' => "Escuela Profesional de Ciencias de la Comunicación"],
            ['id' => 15, 'faculty_id' => 5, 'name' => "Escuela Profesional de Educación"],
            ['id' => 16, 'faculty_id' => 5, 'name' => "Escuela Profesional de Psicología"],
            ['id' => 17, 'faculty_id' => 5, 'name' => "Escuela Profesional de Sociología"],
            ['id' => 18, 'faculty_id' => 6, 'name' => "Escuela Profesional de Ciencia Política"],
            ['id' => 19, 'faculty_id' => 6, 'name' => "Escuela Profesional de Derecho"],
            ['id' => 20, 'faculty_id' => 7, 'name' => "Escuela Profesional de Enfermería"],
            ['id' => 21, 'faculty_id' => 8, 'name' => "Escuela Profesional de Ingeniería Agrícola"],
            ['id' => 22, 'faculty_id' => 9, 'name' => "Escuela Profesional de Ingeniería Arquitectura"],
            ['id' => 23, 'faculty_id' => 9, 'name' => "Escuela Profesional de Ingeniería Ingeniería Civil"],
            ['id' => 24, 'faculty_id' => 9, 'name' => "Escuela Profesional de Ingeniería Ingeniería de Sistemas"],
            ['id' => 25, 'faculty_id' => 10, 'name' => "Escuela Profesional de Ingeniería Mecánica y Eléctrica"],
            ['id' => 26, 'faculty_id' => 11, 'name' => "Escuela Profesional de Medicina Humana"],
            ['id' => 27, 'faculty_id' => 12, 'name' => "Escuela Profesional de Medicina Veterinaria"],
            ['id' => 28, 'faculty_id' => 13, 'name' => "Escuela Profesional de Ingeniería de Industrias Alimentarias"],
            ['id' => 29, 'faculty_id' => 13, 'name' => "Escuela Profesional de Ingeniería Química"],
            ['id' => 30, 'faculty_id' => 14, 'name' => "Escuela Profesional de Ingeniería Zootecnia"],
        ];

        //“idempotente” (no duplica si lo ejecutas varias veces):
        foreach ($rows as $row) {
            DB::table('faculties')->updateOrInsert(
                ['id' => $row['id']],
                [
                    'name' => $row['name'],
                    'abrev' => $row['abrev'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        foreach ($rowChildren as $row) {
            DB::table('programs')->updateOrInsert(
                ['id' => $row['id']],
                [
                    'name' => $row['name'],
                    'faculty_id' => $row['faculty_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
