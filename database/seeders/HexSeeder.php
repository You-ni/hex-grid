<?php

namespace Database\Seeders;

use App\Models\Adjacency;
use App\Models\Hex;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HexSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $center = [0, 0, 0];

        $hexes = [$center];
        for($radius = 1; $radius <= 4; $radius++){
            $hexes = [...$hexes, ...Hex::ring($center, $radius)];
        }

        foreach($hexes as $index => $hex){
            [$q, $r, $s] = $hex;

            $hexes[$index] = Hex::firstOrCreate([
                'coordinates' => ['q' => $q, 'r' => $r, 's' => $s],
                'visible' => !(abs($q) == ($radius -1 ) || abs($r) == ($radius -1 ) || abs($s) == ($radius -1 ))
            ]);
        }

        for($i = 0; $i < count($hexes); $i++){
            for($j = 0; $j < count($hexes); $j++){
                $pair = [$hexes[$i], $hexes[$j]];
                if(Hex::distance($pair[0]->coordinates, $pair[1]->coordinates) == 1){
                    Adjacency::firstOrCreate([
                        'hex_id' => $hexes[$i]->getKey(),
                        'adjacent_hex_id' => $hexes[$j]->getKey(),
                    ]);
                }
            }
        }
    }
}
