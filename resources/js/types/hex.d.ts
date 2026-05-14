export type Tile = {
    q: number;
    r: number;
    ls: 1 | 2 | 3;
    type: "deposit" | "combat" | "ability" | "sociality" | "rest";
    hasKey: boolean;
    hasBox: boolean;
    generated: boolean;
    resolved: boolean;
    corridors: number[]
};
