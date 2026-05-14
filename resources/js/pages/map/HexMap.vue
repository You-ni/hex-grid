<script setup lang="ts">
import HexTile from '@/components/map/HexTile.vue';
import { Tile } from '@/types/hex';
import { computed } from 'vue';

const props = defineProps<{
    tiles: Tile[];
}>();

function axialToPixel(q, r, size) {
    return {
        x: size * 1.5 * q,
        y: size * Math.sqrt(3) * (r + q / 2)
    }
}

const bounds = computed(() => {
    const positions = props.tiles.map(tile =>
        axialToPixel(tile.q, tile.r, hexSize)
    )

    const xs = positions.map(p => p.x)
    const ys = positions.map(p => p.y)

    return {
        minX: Math.min(...xs),
        maxX: Math.max(...xs),
        minY: Math.min(...ys),
        maxY: Math.max(...ys),
    }
})

const mapCenter = computed(() => ({
    x: (bounds.value.minX + bounds.value.maxX) / 2,
    y: (bounds.value.minY + bounds.value.maxY) / 2,
}))

const transform = computed(() => {
    const offsetX = width / 2 - mapCenter.value.x
    const offsetY = height / 2 - mapCenter.value.y

    return `translate(${offsetX}, ${offsetY})`
})

const width = 800;
const height = 800;
const hexSize = 60;

</script>

<template>
    <div class="w-full min-h-screen flex justify-center items-center bg-white">
        <svg :width="width" :height="height" style="background: white;">
            <g :transform="transform">
                <HexTile
                    v-for="tile in tiles"
                    :key="`${tile.q},${tile.r}`"
                    :tile="tile"
                    :size="hexSize"
                />
            </g>
        </svg>
    </div>
</template>
