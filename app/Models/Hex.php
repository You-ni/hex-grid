<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Override;

class Hex extends Model
{
    /** @use HasFactory<\Database\Factories\HexFactory> */
    use HasFactory;

    #[Override]
    public function casts()
    {
        return [
            ...parent::casts(),
            'coordinates' => 'array'
        ];
    }

    #[Override]
    public function getGuarded()
    {
        return [];
    }

    public function corridors(){
        return $this->belongsToMany(Hex::class, 'adjacencies', 'hex_id', 'adjacent_hex_id')
            ->wherePivot('has_corridor', true);
    }

    public function axialToPixel(int $size = 1): array
    {
        ['q' => $q, 'r' => $r, 's' => $_] = $this->coordinates;

        return [
            'x' => $size * 1.5 * $q,
            'y' => $size * sqrt(3) * ($r + ($q / 2))
        ];
    }

    public function polygon(int $size = 1): string
    {
        ['x' => $x, 'y' => $y] = $this->axialToPixel($size);

        $points = [];
        for($i = 0; $i < 6; $i++){
            $points[] = implode(
                ',',
                [
                    $x + ($size * cos(pi() / 180 * (60 * $i))),
                    $y + ($size * sin(pi() / 180 * (60 * $i))),
                ]
            );
        }

        return implode(" ", $points);
    }

    public static function add(array $a, array $b): array
    {
        return array_map(
            fn (...$arrays) => array_sum($arrays),
            $a, $b
        );
    }

    public static function subtract(array $a, array $b): array
    {
        return array_map(
            fn (...$arrays) => array_sum($arrays),
            $a, array_map(fn (int $coordinate) => $coordinate * (-1), $b)
        );
    }

    public static function distance(array $a, array $b): int
    {
        $subtraction = self::subtract($a, $b);
        return array_reduce($subtraction, fn (int $carry, int $coordinate) => $carry + abs($coordinate), 0) / 2;
    }

    public static function direction(?int $direction): array
    {
        if(is_null($direction)){
            return [[0, -1, +1], [+1, -1, 0], [+1, 0, -1], [0, +1, -1], [-1, +1, 0], [-1, 0, +1]];
        }

        return [[0, -1, +1], [+1, -1, 0], [+1, 0, -1], [0, +1, -1], [-1, +1, 0], [-1, 0, +1]][(6 + ($direction % 6)) % 6];
    }

    public static function neighbor(array $coordinates, int $direction): array
    {
        return self::add($coordinates, self::direction($direction));
    }

    public static function scale(array $coordinates, int $factor): array
    {
        return array_map(fn (int $coordinate) => $coordinate * $factor, $coordinates);
    }

    public static function ring(array $center, int $radius): array
    {
        $hexes = [];

        $hex = self::add($center, self::scale(self::direction(0), $radius));
        for($i = 0; $i < 6; $i++){
            for($j = 0; $j < $radius; $j++){
                $hexes[] = $hex;
                $hex = self::neighbor($hex, $i+2);
            }
        }

        return $hexes;
    }
}
