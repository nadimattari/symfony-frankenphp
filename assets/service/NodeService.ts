export interface TreeNodeData {
    key?: string;
    label?: string;
    data?: Record<string, unknown>;
    icon?: string;
    children?: TreeNodeData[];
    [key: string]: unknown;
}

export default class NodeService {
    getTreeTableNodes(): Promise<TreeNodeData[]> {
        return fetch('demo/data/treetablenodes.json')
            .then((res) => res.json())
            .then((d) => d.root as TreeNodeData[]);
    }

    getTreeNodes(): Promise<TreeNodeData[]> {
        return fetch('demo/data/treenodes.json')
            .then((res) => res.json())
            .then((d) => d.root as TreeNodeData[]);
    }
}
