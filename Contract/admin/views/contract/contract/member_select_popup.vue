<template>
    <el-dialog v-model="showDialog" title="选择会员" width="900px" append-to-body>
        <el-form :inline="true" :model="searchParam" ref="searchFormRef" class="mb-[10px]">
            <el-form-item label="会员信息" prop="keyword">
                <el-input v-model="searchParam.keyword" placeholder="请输入会员昵称/手机号" clearable @keyup.enter="loadList" />
            </el-form-item>
            <el-form-item>
                <el-button type="primary" @click="loadList()">搜索</el-button>
                <el-button @click="resetSearch()">重置</el-button>
            </el-form-item>
        </el-form>

        <el-table :data="tableData.data" style="width: 100%" v-loading="tableData.loading" height="400" @current-change="handleCurrentChange" highlight-current-row>
            <el-table-column label="选择" width="60" align="center">
                <template #default="scope">
                    <el-radio v-model="selectedId" :label="scope.row.member_id">&nbsp;</el-radio>
                </template>
            </el-table-column>
            <el-table-column prop="member_id" label="ID" width="80" />
            <el-table-column label="头像" width="80">
                <template #default="scope">
                    <img v-if="scope.row.headimg" :src="scope.row.headimg" class="w-[40px] h-[40px] rounded-full" />
                </template>
            </el-table-column>
            <el-table-column prop="nickname" label="昵称" />
            <el-table-column prop="mobile" label="手机号" />
            <el-table-column prop="create_time" label="注册时间" width="180" />
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
import { getMemberList } from '@/addon/Contract/api/member'
import { ElMessage } from 'element-plus'

const showDialog = ref(false)
const selectedId = ref<number | string>('')
const selectedRow = ref<any>(null)

const searchParam = reactive({
    page: 1,
    limit: 10,
    keyword: ''
})

const tableData = reactive({
    loading: false,
    data: [],
    total: 0
})

const loadList = async () => {
    tableData.loading = true
    try {
        const res = await getMemberList(searchParam)
        tableData.data = res.data.data
        tableData.total = res.data.total
    } catch (error) {
        tableData.data = []
        tableData.total = 0
    }
    tableData.loading = false
}

const resetSearch = () => {
    searchParam.keyword = ''
    loadList()
}

const handleCurrentChange = (val: any) => {
    if (val) {
        selectedRow.value = val
        selectedId.value = val.member_id
    }
}

const emit = defineEmits(['select'])

const open = () => {
    showDialog.value = true
    loadList()
}

const confirm = () => {
    if (!selectedRow.value) {
        ElMessage.warning('请选择会员')
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
