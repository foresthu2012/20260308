<template>
    <el-dialog v-model="showDialog" title="选择订单" width="900px" append-to-body>
        <el-form :inline="true" :model="searchParam" ref="searchFormRef" class="mb-[10px]">
            <el-form-item label="订单号" prop="order_no">
                <el-input v-model="searchParam.order_no" placeholder="请输入订单号" clearable @keyup.enter="loadList" />
            </el-form-item>
            <el-form-item>
                <el-button type="primary" @click="loadList()">搜索</el-button>
                <el-button @click="resetSearch()">重置</el-button>
            </el-form-item>
        </el-form>

        <el-table :data="tableData.data" style="width: 100%" v-loading="tableData.loading" height="400" @current-change="handleCurrentChange" highlight-current-row>
            <el-table-column label="选择" width="60" align="center">
                <template #default="scope">
                    <el-radio v-model="selectedId" :label="scope.row.order_id">&nbsp;</el-radio>
                </template>
            </el-table-column>
            <el-table-column prop="order_no" label="订单号" width="200" />
            <el-table-column prop="order_money" label="订单金额" />
            <el-table-column prop="create_time" label="下单时间" width="180" />
            <el-table-column prop="order_status_name" label="状态" />
        </el-table>

        <div class="mt-[16px] flex justify-end">
            <el-pagination v-model:current-page="searchParam.page" v-model:page-size="searchParam.limit"
                layout="total, sizes, prev, pager, next, jumper" :total="tableData.total" @size-change="loadList()"
                @current-change="loadList" />
        </div>

        <template #footer>
            <span class="dialog-footer">
                <el-button @click="showDialog = false">取消</el-button>
                <el-button type="primary" @click="confirm">确定</el-button>
            </span>
        </template>
    </el-dialog>
</template>

<script lang="ts" setup>
import { ref, reactive } from 'vue'
import { getOrderList } from '@/addon/Contract/api/order'
import { ElMessage } from 'element-plus'

const showDialog = ref(false)
const selectedId = ref<number | string>('')
const selectedRow = ref<any>(null)

const searchParam = reactive({
    page: 1,
    limit: 10,
    order_no: '',
    member_id: ''
})

const tableData = reactive({
    loading: false,
    data: [],
    total: 0
})

const loadList = async () => {
    // 强制检查 member_id
    if (!searchParam.member_id) {
        tableData.data = []
        tableData.total = 0
        return
    }

    tableData.loading = true
    try {
        const res = await getOrderList(searchParam)
        tableData.data = res.data.data
        tableData.total = res.data.total
    } catch (error) {
        tableData.data = []
        tableData.total = 0
    }
    tableData.loading = false
}

const resetSearch = () => {
    searchParam.order_no = ''
    loadList()
}

const handleCurrentChange = (val: any) => {
    if (val) {
        selectedRow.value = val
        selectedId.value = val.order_id
    }
}

const emit = defineEmits(['select'])

const open = (memberId: string | number = '') => {
    showDialog.value = true
    if (memberId) {
        searchParam.member_id = memberId
    } else {
        searchParam.member_id = ''
    }
    loadList()
}

const confirm = () => {
    if (!selectedRow.value) {
        ElMessage.warning('请选择订单')
        return
    }
    emit('select', selectedRow.value)
    showDialog.value = false
}

defineExpose({
    open
})
</script>

<style lang="scss" scoped>
</style>
