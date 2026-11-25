import type { FetchClient } from "../utils/fetch";
import { ApiService, apiClient } from "./api";

export interface TreeNodeData {
  key?: string;
  label?: string;
  data?: Record<string, unknown>;
  icon?: string;
  children?: TreeNodeData[];
}

/** Demo-only fixture endpoints served by DemoDataController. */
export default class NodeService extends ApiService<
  TreeNodeData & { id?: string }
> {
  constructor(client: FetchClient = apiClient) {
    super(client, "/demo/tree-nodes");
  }

  getTreeNodes(): Promise<TreeNodeData[]> {
    return this.listFrom("/demo/tree-nodes");
  }

  getTreeTableNodes(): Promise<TreeNodeData[]> {
    return this.listFrom("/demo/tree-table-nodes");
  }
}
