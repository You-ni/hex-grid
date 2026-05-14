<?php

namespace App\Http\Controllers;

use App\Models\Adjacency;
use App\Models\Hex;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use InvalidArgumentException;

class TestController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $tiles = Hex::query()
            ->where('visible', true)
            ->get();

        $tiles = $this->process($tiles);

        $tiles = $tiles->map(function(Hex $hex){
                ['q' => $q, 'r' => $r, 's' => $_] = $hex->coordinates;

                $corridors = $hex->corridors()
                    ->get()
                    ->map(function(Hex $linked) use($hex){
                        $coordinates = Hex::subtract($linked->coordinates, $hex->coordinates);
                        foreach(Hex::direction(null) as $index => $value){
                            if($value === $coordinates){
                                return $index;
                            }
                        }
                    });

                return [
                    'q' => $q,
                    'r' => $r,
                    'ls' => $hex->security_level,
                    'type' => $hex->type,
                    'hasKey' => $hex->has_key,
                    'hasBox' => $hex->has_box,
                    'generated' => $hex->generated,
                    'resolved' => $hex->resolved,
                    'corridors' => $corridors
                ];
            });

        return Inertia::render('map/HexMap', ['tiles' => $tiles]);
    }

    private function process(Collection $tiles): Collection
    {
        DB::table('adjacencies')->update(['has_corridor' => false]);
        $tiles->each(function(Hex $hex){
                $hex->fill([
                    'security_level' => null,
                    'type' => null,
                    'generated' => false,
                    'resolved' => false,
                    'has_key' => false,
                    'has_box' => false
                ]);
                $hex->save();
            });

        $deposit = $tiles->firstWhere('type', 'deposit');
        if(is_null($deposit)){
            $deposit = $tiles
                ->filter(function(Hex $hex){
                    ['q' => $q, 'r' => $r, 's' => $s] = $hex->coordinates;

                    return !(abs($q) == 3 || abs($r) == 3 || abs($s) == 3);
                })->random();
            $deposit->fill([
                'type' => 'deposit',
                'generated' => true,
                'resolved' => true
            ]);
            $deposit->save();
        }

        $startingHexes = $tiles->filter(function(Hex $hex){
                ['q' => $q, 'r' => $r, 's' => $s] = $hex->coordinates;

                return (abs($q) == 3 || abs($r) == 3 || abs($s) == 3);
            })
            ->random(4)
            ->each(function(Hex $hex){
                $hex->fill([
                    'type' => collect(['combat', 'ability', 'sociality'])->random(),
                    'generated' => true,
                    'resolved' => true
                ]);
                $hex->save();
            });

        foreach($startingHexes as $startingHex){

            $currentHex = $startingHex;
            $pathSteps = fake()->numberBetween(1, 6);

            for($step = 0; $step < $pathSteps; $step++){
                try {
                    $adjacent = $tiles->filter(function(Hex $hex) use($currentHex){
                        return Hex::distance($currentHex->coordinates, $hex->coordinates) == 1 && !$hex->generated;
                    })->random();
                } catch(InvalidArgumentException $e){
                    continue;
                }

                $rests = Hex::query()->where('type', 'rest')->count();
                $ls1 = Hex::query()->where('security_level', 1)->count();
                $ls2 = Hex::query()->where('security_level', 2)->count();
                $ls3 = Hex::query()->where('security_level', 3)->count();

                $ls = collect([$ls1 < 18 ? 1 : null, $ls2 < 12 ? 2 : null, $ls3 < 6 ? 3 : null])->filter()->random();
                $type = collect(['combat', 'ability', 'sociality', $rests < 6 ? 'rest' : null])->filter()->random();
                $resolved = $type === 'rest' || $step < ($pathSteps - 1);

                $adjacent->fill([
                    'security_level' => $ls,
                    'type' => $type,
                    'generated' => true,
                    'resolved' => $resolved,
                    'has_key' => !$resolved && $type !== 'rest' && fake()->boolean(20),
                    'has_box' => !$resolved && $type !== 'rest' && fake()->boolean(80)
                ]);
                $adjacent->save();

                DB::table('adjacencies')
                    ->where('hex_id', $currentHex->getKey())
                    ->where('adjacent_hex_id', $adjacent->getKey())
                    ->update(['has_corridor' => true]);

                DB::table('adjacencies')
                    ->where('hex_id', $adjacent->getKey())
                    ->where('adjacent_hex_id', $currentHex->getKey())
                    ->update(['has_corridor' => true]);

                $corridors = $tiles->filter(function(Hex $hex) use($adjacent){
                        return Hex::distance($adjacent->coordinates, $hex->coordinates) == 1 && (!$hex->generated || $hex->type === 'deposit');
                    });

                $corridors->random(min(fake()->numberBetween(0, 3), $corridors->count()))
                    ->each(function(Hex $corridor) use($adjacent){
                        DB::table('adjacencies')
                            ->where('hex_id', $adjacent->getKey())
                            ->where('adjacent_hex_id', $corridor->getKey())
                            ->update(['has_corridor' => true]);

                        DB::table('adjacencies')
                            ->where('hex_id', $corridor->getKey())
                            ->where('adjacent_hex_id', $adjacent->getKey())
                            ->update(['has_corridor' => true]);
                    });

                $currentHex = $adjacent;
            }

        }

        return $tiles;
    }
}
