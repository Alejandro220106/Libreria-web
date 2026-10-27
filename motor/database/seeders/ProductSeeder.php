<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $now = now()->utc();

        // sku, name, type, price (colones), stock, category, author
        $rows = [
            ['CONTRATO-001', 'Cuentos de mi tía Panchita', 'simple', 5200, 12, 'Cuento', 'Carmen Lyra'],
            ['LIB-MAM-01', 'Mamita Yunai', 'simple', 7800, 8, 'Novela', 'Carlos Luis Fallas'],
            ['LIB-GAL-01', 'Gallinas y gallos', 'simple', 4900, 20, 'Cuento', 'Fabián Dobles'],
            ['LIB-LAT-01', 'La ruta de su evasión', 'simple', 6100, 0, 'Novela', 'Yolanda Oreamuno'],
            ['LIB-CRP-01', 'El moto', 'simple', 5600, 9, 'Novela', 'Joaquín Gutiérrez'],
            ['LIB-CEN-01', 'Cenizas', 'simple', 5100, 15, 'Novela', 'Tatiana Lobo'],
            ['LIB-CIE-01', 'Cien años de soledad', 'simple', 11500, 6, 'Novela', 'Gabriel García Márquez'],
            ['LIB-QUI-01', 'El Quijote de la Mancha', 'simple', 14900, 4, 'Clásicos', 'Miguel de Cervantes'],
            ['LIB-PRI-01', 'El principito', 'simple', 6500, 20, 'Infantil', 'Antoine de Saint-Exupéry'],
            ['LIB-ZOO-01', 'Cuentos para dormir', 'simple', 8900, 5, 'Infantil', 'Varios autores'],
            ['LIB-POE-01', 'Antología de poesía costarricense', 'simple', 7200, 3, 'Poesía', 'Varios autores'],
            ['LIB-ENS-01', 'Ensayos selectos', 'simple', 9400, 7, 'Ensayo', 'Varios autores'],
            ['LIB-AGE-VAR', 'Agenda anual', 'variable', 4500, null, 'Papelería', null],
            ['LIB-KIT-01', 'Kit de lectura', 'agrupado', 15000, null, 'Papelería', null],
        ];

        $products = [];
        foreach ($rows as $i => [$sku, $name, $type, $price, $stock, $category, $author]) {
            $products[] = [
                'woo_id' => 40 + $i + 1,
                'sku' => $sku,
                'name' => $name,
                'type' => $type,
                'price' => $price,
                'stock' => $stock,
                'categories' => json_encode([$category], JSON_UNESCAPED_UNICODE),
                'author' => $author,
                'source_updated_at' => $now,
                'synced_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // upsert keeps the seeder safe to run twice.
        DB::table('products')->upsert(
            $products,
            ['woo_id'],
            ['sku', 'name', 'type', 'price', 'stock', 'categories', 'author', 'source_updated_at', 'synced_at', 'updated_at'],
        );
    }
}
