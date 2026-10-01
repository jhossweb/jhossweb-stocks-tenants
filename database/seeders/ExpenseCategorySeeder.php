<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // Fixed expenses
            ['exc_name' => 'Alquiler',          'exc_type' => 'fijo',     'exc_description' => 'Alquiler del local/taller'],
            ['exc_name' => 'Servicios Públicos', 'exc_type' => 'fijo',    'exc_description' => 'Agua, luz, gas'],
            ['exc_name' => 'Internet / Teléfono', 'exc_type' => 'fijo',   'exc_description' => 'Plan de internet y telefonía'],
            ['exc_name' => 'Seguros',            'exc_type' => 'fijo',     'exc_description' => 'Seguros del taller y equipos'],

            // Variable expenses
            ['exc_name' => 'Vidrio Crudo',       'exc_type' => 'variable', 'exc_description' => 'Compra de vidrio en planchas'],
            ['exc_name' => 'Perfiles de Aluminio', 'exc_type' => 'variable', 'exc_description' => 'Perfiles de aluminio para marcos'],
            ['exc_name' => 'Herrajes / Manijas',  'exc_type' => 'variable', 'exc_description' => 'Manijas, bisagras, cerraduras'],
            ['exc_name' => 'Herramientas',        'exc_type' => 'variable', 'exc_description' => 'Compra y reposición de herramientas'],
            ['exc_name' => 'Mantenimiento Maquinaria', 'exc_type' => 'variable', 'exc_description' => 'Mantenimiento de cortadoras, pulidoras, etc.'],
            ['exc_name' => 'Transporte / Fletes', 'exc_type' => 'variable', 'exc_description' => 'Transporte de materiales y entregas'],
            ['exc_name' => 'Empaques',            'exc_type' => 'variable', 'exc_description' => 'Material de empaque para productos'],
            ['exc_name' => 'Insumos de Limpieza', 'exc_type' => 'variable', 'exc_description' => 'Productos de limpieza del taller'],
            ['exc_name' => 'Papelería / Oficina', 'exc_type' => 'variable', 'exc_description' => 'Útiles de oficina y papelería'],
            ['exc_name' => 'Publicidad',          'exc_type' => 'variable', 'exc_description' => 'Publicidad y marketing'],
            ['exc_name' => 'Combustible',         'exc_type' => 'variable', 'exc_description' => 'Combustible para vehículos del taller'],
        ];

        foreach ($categories as $category) {
            ExpenseCategory::firstOrCreate(
                ['exc_name' => $category['exc_name']],
                $category,
            );
        }
    }
}
