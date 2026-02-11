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
    }
}
