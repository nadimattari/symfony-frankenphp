<script setup lang="ts">
import { onMounted, ref } from "vue";
import Column from "primevue/column";
import Tree from "primevue/tree";
import TreeTable from "primevue/treetable";
import NodeService, { type TreeNodeData } from "../../service/NodeService";

const treeValue = ref<any[] | undefined>(undefined);
const selectedTreeValue = ref<any>(undefined);
const treeTableValue = ref<any[] | undefined>(undefined);
const selectedTreeTableValue = ref<any>(undefined);
const nodeService = new NodeService();

onMounted(() => {
    nodeService.getTreeNodes().then((data) => (treeValue.value = data));
    nodeService
        .getTreeTableNodes()
        .then((data) => (treeTableValue.value = data));
});
</script>

<template>
    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12">
            <div class="card">
                <h5>Tree</h5>
                <Tree
                    :value="treeValue"
                    selectionMode="checkbox"
                    v-model:selectionKeys="selectedTreeValue"
                ></Tree>
            </div>
        </div>
        <div class="col-span-12">
            <div class="card">
                <h5>TreeTable</h5>
                <TreeTable
                    :value="treeTableValue"
                    selectionMode="checkbox"
                    v-model:selectionKeys="selectedTreeTableValue"
                >
                    <template #header> FileSystem </template>
                    <Column
                        field="name"
                        header="Name"
                        :expander="true"
                    ></Column>
                    <Column field="size" header="Size"></Column>
                    <Column field="type" header="Type"></Column>
                </TreeTable>
            </div>
        </div>
    </div>
</template>
