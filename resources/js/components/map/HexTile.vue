<script setup lang="ts">
import { Tile } from '@/types/hex';
import { computed } from 'vue';

const props = defineProps<{
    tile: Tile;
    size: number;
}>();

function hexCorner(cx: number, cy: number, size: number, i: number): { x: number, y: number } {
    const angleDeg = 60 * i - 120
    const angleRad = Math.PI / 180 * angleDeg

    return {
        x: cx + size * Math.cos(angleRad),
        y: cy + size * Math.sin(angleRad)
    }
}

function edgeSegments(a: { x: number, y: number }, b: { x: number, y: number }, hasCorridor: boolean) {
    if (!hasCorridor) {
        return [{
            x1: a.x,
            y1: a.y,
            x2: b.x,
            y2: b.y,
        }];
    }

    const gap = 8;

    const mx = (a.x + b.x) / 2;
    const my = (a.y + b.y) / 2;

    const dx = b.x - a.x;
    const dy = b.y - a.y;

    const len = Math.sqrt(dx * dx + dy * dy);

    const ux = dx / len;
    const uy = dy / len;

    return [
        {
            x1: a.x,
            y1: a.y,
            x2: mx - ux * gap,
            y2: my - uy * gap,
        },
        {
            x1: mx + ux * gap,
            y1: my + uy * gap,
            x2: b.x,
            y2: b.y,
        }
    ];
}

function crossingSegment(a: { x: number, y: number }, b: { x: number, y: number }) {
    const mx = (a.x + b.x) / 2;
    const my = (a.y + b.y) / 2;

    const dx = b.x - a.x;
    const dy = b.y - a.y;

    const len = Math.sqrt(dx * dx + dy * dy);

    const ux = dx / len;
    const uy = dy / len;

    const px = -uy;
    const py = ux;

    const size = 6;

    return {
        x1: mx - px * size,
        y1: my - py * size,
        x2: mx + px * size,
        y2: my + py * size,
    };
}

function iconOffset(index: number) {
    const count = icons.value.length;

    if (count === 1) {
        return { x: 0, y: 0 };
    }

    if (count === 2) {
        const spacing = 24;

        return {
            x: 0,
            y: index === 0
                ? -spacing / 2
                : spacing / 2
        };
    }

    // 3 icons
    if (count === 3) {
        const spacing = 16;
        const positions = [
            { x: 0, y: -spacing },        // top
            { x: -spacing, y: spacing / 2 }, // bottom-left
            { x: spacing, y: spacing / 2 },  // bottom-right
        ];

        return positions[index];
    }

    return { x: 0, y: 0 };
}

function iconTransform(index: number): string {
    const offset = iconOffset(index);

    return `
        translate(
            ${center.value.x + offset.x},
            ${center.value.y + offset.y}
        )
        scale(0.7)
        translate(-24, -24)
    `;
}

const center = computed<{ x: number, y: number }>(() => {
    const q = props.tile.q
    const r = props.tile.r
    const size = props.size

    return {
        x: size * 1.5 * q,
        y: size * Math.sqrt(3) * (r + q / 2)
    }
})

const corners = computed(() => {
    const pts = [];
    for (let i = 0; i < 6; i++) {
        pts.push(hexCorner(center.value.x, center.value.y, props.size, i));
    }

    return pts;
});

const outerPolygon = computed(() => {
    return corners.value.map(p => `${p.x},${p.y}`).join(' ')
});

const edgeLines = computed(() => {
    const result = [];

    for (let i = 0; i < 6; i++) {
        const a = corners.value[i];
        const b = corners.value[(i + 1) % 6];
        const hasCorridor = props.tile.corridors.includes(i);

        result.push(...edgeSegments(a, b, hasCorridor));
    }

    return result;
});

const edges = computed(() => {
    const result = [];

    for (let i = 0; i < 6; i++) {
        const a = corners.value[i];
        const b = corners.value[(i + 1) % 6];
        const hasCorridor = props.tile.corridors.includes(i);

        result.push({ a, b, hasCorridor });
    }

    return result;
});

const innerPolygons = computed<string[]>(() => {
    const polygons: string[] = [];

    if (!props.tile.generated || ['deposit', 'rest'].includes(props.tile.type)) {
        return polygons;
    }

    for (let ls = 1; ls < props.tile.ls + 1; ls++) {
        const pts = []

        for (let i = 0; i < 6; i++) {
            const p = hexCorner(center.value.x, center.value.y, props.size - (ls * (props.size / 10)), i)
            pts.push(`${p.x},${p.y}`)
        }

        polygons.push(pts.join(' '));
    }

    return polygons;
})

const icons = computed(() => {
    if (!props.tile.generated) return [];

    return [
        props.tile.type == 'deposit' ? 'square' : null,
        props.tile.type == 'rest' ? 'circle' : null,
        props.tile.hasKey ? 'key' : null,
        props.tile.hasBox ? 'box' : null,
        !['deposit', 'rest'].includes(props.tile.type) ? 'dump' : null
    ].filter(el => el !== null);
});

const colorMap = {
    deposit: '#ED6AFF',
    combat: '#FF6467',
    ability: '#9AE630',
    sociality: '#21BCFF',
    rest: '#53EAFD',
} as const;

const fillColor = computed(() => {
    if (!props.tile.generated) {
        return 'white'
    }

    return colorMap[props.tile.type]
})

</script>

<template>
    <g>
        <polygon
            :points="outerPolygon"
            :fill="fillColor"
            stroke="none"
        />

        <!-- with crossing -->
        <!-- <template
            v-for="(edge, index) in edges"
            :key="index"
        >
            <line
                :x1="edge.a.x"
                :y1="edge.a.y"
                :x2="edge.b.x"
                :y2="edge.b.y"
                stroke="black"
                stroke-width="2"
            />

            <line
                v-if="edge.hasCorridor"
                v-bind="crossingSegment(edge.a, edge.b)"
                stroke="black"
                stroke-width="2"
            />
        </template> -->

        <!-- with gaps -->
        <line
            v-for="(line, index) in edgeLines"
            :key="index"
            :x1="line.x1"
            :y1="line.y1"
            :x2="line.x2"
            :y2="line.y2"
            stroke="black"
            stroke-width="2.5"
        />

        <polygon
            v-for="(points, index) in innerPolygons"
            :key="index"
            :points="points"
            :fill="tile.resolved && index == innerPolygons.length - 1 && !['deposit', 'rest'].includes(tile.type) ? 'white' : fillColor"
            stroke="black"
            stroke-width="1"
        />

        <g
            v-for="(icon, index) in icons"
            :key="index"
            :transform="iconTransform(index)"
            :fill="fillColor"
            stroke="black"
            :stroke-width="1"
        >
            <template v-if="icon === 'key'">
                <g transform="translate(12,12)">
                    <path
                        d="M2.586 17.414A2 2 0 0 0 2 18.828V21a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h1a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h.172a2 2 0 0 0 1.414-.586l.814-.814a6.5 6.5 0 1 0-4-4z"
                    />

                    <circle
                        cx="16.5"
                        cy="7.5"
                        r=".5"
                    />
                </g>
            </template>

            <template v-if="icon === 'box'">
                <g transform="translate(12,12)">
                    <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>

                    <path d="m3.3 7 8.7 5 8.7-5"/>

                    <path d="M12 22V12"/>
                </g>
            </template>

            <template v-if="icon === 'square'">
                <rect width="40" height="40" x="4" y="4" rx="4"/>
            </template>

            <template v-if="icon === 'circle'">
                <circle cx="24" cy="24" r="20"/>
            </template>

            <template v-if="icon === 'dump'">
                <text
                    x="24"
                    y="24"
                    text-anchor="middle"
                    dominant-baseline="middle"
                    font-size="24"
                    fill="black"
                    stroke="none"
                    font-weight="bold"
                >
                    {{ tile.dump }}
                </text>
            </template>
        </g>
    </g>
</template>
