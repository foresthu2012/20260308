<template>
  <div class="main-container">
    <el-card class="box-card !border-none" shadow="never">
      <div class="flex justify-between items-center mb-[20px]">
        <span class="text-page-title">合同管理</span>
        <el-button type="primary" @click="addEvent">添加合同</el-button>
      </div>

      <el-card class="box-card !border-none my-[10px] table-search-wrap" shadow="never">
        <el-form :inline="true" :model="searchParam" ref="searchFormRef">
          <el-form-item label="合同标题" prop="title">
            <el-input v-model="searchParam.title" placeholder="请输入合同标题" />
          </el-form-item>
          <el-form-item label="会员信息" prop="member_keyword">
            <el-input v-model="searchParam.member_keyword" placeholder="昵称/手机号" />
          </el-form-item>
          <el-form-item label="状态" prop="status">
             <el-select v-model="searchParam.status" placeholder="请选择状态" clearable>
                <el-option label="待签署" :value="0" />
                <el-option label="已签署" :value="1" />
             </el-select>
          </el-form-item>
          <el-form-item>
            <el-button type="primary" @click="loadList()">搜索</el-button>
            <el-button @click="resetSearch()">重置</el-button>
          </el-form-item>
        </el-form>
      </el-card>

      <el-table :data="tableData.data" style="width: 100%" v-loading="tableData.loading">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="title" label="合同标题" />
        <el-table-column label="会员" min-width="150">
            <template #default="scope">
                <div class="flex items-center" v-if="scope.row.member">
                    <img v-if="scope.row.member.headimg" :src="scope.row.member.headimg" class="w-[30px] h-[30px] rounded-full mr-2" />
                    <div class="flex flex-col">
                        <span class="text-sm">{{ scope.row.member.nickname }}</span>
                        <span class="text-xs text-gray-400">{{ scope.row.member.mobile }}</span>
                    </div>
                </div>
                <span v-else>ID: {{ scope.row.member_id }}</span>
            </template>
        </el-table-column>
        <el-table-column prop="status" label="状态">
            <template #default="scope">
                <el-tag v-if="scope.row.status == 0" type="info">待签署</el-tag>
                <el-tag v-else type="success">已签署</el-tag>
            </template>
        </el-table-column>
        <el-table-column prop="create_time" label="创建时间">
            <template #default="scope">
                {{ scope.row.create_time }}
            </template>
        </el-table-column>
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="scope">
            <el-button type="primary" link @click="editEvent(scope.row)" v-if="scope.row.status == 0">编辑</el-button>
            <el-button type="primary" link @click="viewEvent(scope.row)">查看</el-button>
            <el-button type="danger" link @click="deleteEvent(scope.row.id)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="mt-[16px] flex justify-end">
        <el-pagination v-model:current-page="searchParam.page" v-model:page-size="searchParam.limit"
          layout="total, sizes, prev, pager, next, jumper" :total="tableData.total" @size-change="loadList()"
          @current-change="loadList" />
      </div>
    </el-card>

    <contract-edit ref="editContractRef" @complete="loadList" />
    <FilePreviewDialog v-model="filePreviewVisible" :file-url="currentPreviewFile" :file-name="previewFileName" @close="handlePreviewClose" />
  </div>
</template>

<script lang="ts" setup>
import { ref, reactive } from 'vue'
import { getContractList, deleteContract } from '@/addon/Contract/api/contract'
import ContractEdit from './edit.vue'
import FilePreviewDialog from './FilePreviewDialog.vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { img } from '@/utils/common'

const searchParam = reactive({
  page: 1,
  limit: 10,
  title: '',
  member_keyword: '',
  status: ''
})

const tableData = reactive({
  loading: false,
  data: [],
  total: 0
})

// 文件预览相关
const filePreviewVisible = ref(false)
const currentPreviewFile = ref('')
const previewFileName = ref('')

const loadList = async () => {
  tableData.loading = true
  try {
    const res = await getContractList(searchParam)
    tableData.data = res.data.data
    tableData.total = res.data.total
  } catch (error) {
    tableData.data = []
    tableData.total = 0
  }
  tableData.loading = false
}

loadList()

const resetSearch = () => {
  searchParam.title = ''
  searchParam.member_keyword = ''
  searchParam.status = ''
  loadList()
}

const editContractRef = ref()

const addEvent = () => {
  editContractRef.value.setFormData()
}

const editEvent = (row: any) => {
  editContractRef.value.setFormData(row)
}

const viewEvent = (row: any) => {
  // 已签署状态显示电子签章合成后的合同,待签署状态显示原始合同文件
  const fileToShow = row.status == 1 && row.sign_image ? row.sign_image : row.file_path
  
  if (!fileToShow) {
    ElMessage.warning('没有可预览的合同文件')
    return
  }
  
  previewFileName.value = row.title ? (row.status == 1 ? `${row.title}（已签署）` : row.title) : '合同文件'
  currentPreviewFile.value = img(fileToShow)
  filePreviewVisible.value = true
}

const handlePreviewClose = () => {
  currentPreviewFile.value = ''
  previewFileName.value = ''
}

const deleteEvent = (id: number) => {
  ElMessageBox.confirm('确定要删除吗？', '提示', {
    confirmButtonText: '确定',
    cancelButtonText: '取消',
    type: 'warning'
  }).then(async () => {
    await deleteContract(id)
    loadList()
    ElMessage.success('删除成功')
  })
}
</script>

<style lang="scss" scoped>
</style>
