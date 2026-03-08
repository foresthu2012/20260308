<template>
    <el-dialog v-model="showDialog" :title="formData.id ? '编辑合同' : '添加合同'" width="600px" destroy-on-close>
        <el-form :model="formData" label-width="100px" ref="formRef" :rules="formRules" class="page-form">
            <el-form-item label="合同标题" prop="title">
                <el-input v-model="formData.title" placeholder="请输入合同标题" class="input-width" />
            </el-form-item>
            <el-form-item label="会员" prop="member_id">
                <div class="flex items-center w-full">
                    <span v-if="selectedMemberName" class="mr-2">{{ selectedMemberName }} (ID: {{ formData.member_id }})</span>
                    <el-button type="primary" @click="showMemberSelect">选择会员</el-button>
                </div>
                <div class="form-tip">请选择要签署合同的会员</div>
            </el-form-item>
            <el-form-item label="关联订单" prop="order_id">
                 <div class="flex items-center w-full">
                     <span v-if="selectedOrderNo" class="mr-2">订单号：{{ selectedOrderNo }} (ID: {{ formData.order_id }})</span>
                     <el-button type="primary" @click="showOrderSelect">选择订单</el-button>
                     <el-button v-if="formData.order_id" link type="info" @click="clearOrder" class="ml-2">清除</el-button>
                 </div>
                 <div class="form-tip">可选：关联商城订单（仅显示该会员的订单）</div>
            </el-form-item>
            <el-form-item label="合同文件" prop="file_path">
                 <upload-file v-model="formData.file_path" :limit="1" accept=".pdf,.jpg,.jpeg,.png" />
                 <div class="form-tip">请上传合同文件（支持 PDF、JPG、PNG 格式）</div>
            </el-form-item>
             <el-form-item label="签署状态">
                <el-tag v-if="formData.status == 0" type="info">待签署</el-tag>
                <el-tag v-else type="success">已签署</el-tag>
                <span class="ml-2 text-sm text-gray-500">
                    <template v-if="formData.status == 1 && formData.sign_time">
                        签署时间: {{ formData.sign_time }}
                    </template>
                    <template v-else>
                        签署方式: 微信授权签署(电子签章)
                    </template>
                </span>
            </el-form-item>
            <el-form-item label="已签署合同" v-if="formData.status == 1 && formData.sign_image">
                 <div class="flex items-start">
                     <div class="flex flex-col items-center mr-4">
                         <el-image :src="formData.sign_image" style="width: 100px; height: 100px" fit="contain" />
                         <el-button type="primary" link @click="previewContract" class="mt-2">点击查看大图</el-button>
                     </div>
                     <div class="text-sm text-gray-600">
                         <p class="mb-2">电子签章合成合同</p>
                         <p>合同已通过微信授权签署,系统自动生成电子签章并合成到合同文件中。</p>
                     </div>
                 </div>
            </el-form-item>
            <el-form-item label="预览合同" v-if="formData.file_path && formData.status == 0">
                <el-button type="primary" link @click="previewContract">预览合同文件</el-button>
                <div class="form-tip">
                    预览已上传的合同文件,会员确认后将使用微信授权签署方式完成签署
                </div>
            </el-form-item>
        </el-form>
        <template #footer>
            <span class="dialog-footer">
                <el-button @click="showDialog = false">取消</el-button>
                <el-button type="primary" @click="confirm(formRef)" v-if="formData.status == 0">确定</el-button>
            </span>
        </template>
        <member-select-popup ref="memberSelectRef" @select="handleMemberSelect" />
        <order-select-popup ref="orderSelectRef" @select="handleOrderSelect" />
        <FilePreviewDialog v-model="filePreviewVisible" :file-url="currentPreviewFile" file-name="合同文件" @close="handlePreviewClose" />
    </el-dialog>
</template>

<script lang="ts" setup>
import { ref, reactive } from 'vue'
import { addContract, editContract, getContractInfo } from '@/addon/Contract/api/contract'
import { ElMessage, FormInstance } from 'element-plus'
import MemberSelectPopup from './member_select_popup.vue'
import OrderSelectPopup from './order_select_popup.vue'
import FilePreviewDialog from './FilePreviewDialog.vue'
import { img } from '@/utils/common'

const showDialog = ref(false)
const loading = ref(false)
const formRef = ref<FormInstance>()
const memberSelectRef = ref()
const orderSelectRef = ref()
const selectedMemberName = ref('')
const selectedOrderNo = ref('')

// 文件预览相关
const filePreviewVisible = ref(false)
const currentPreviewFile = ref('')

const formData = reactive({
    id: 0,
    title: '',
    member_id: '',
    order_id: 0,
    file_path: '',
    status: 0,
    sign_image: ''
})

const formRules = reactive({
    title: [{ required: true, message: '请输入合同标题', trigger: 'blur' }],
    member_id: [{ required: true, message: '请选择会员', trigger: 'change' }],
    file_path: [
        { 
            validator: (rule: any, value: any, callback: any) => {
                if (!value) {
                    callback(new Error('请上传合同文件'))
                } else {
                    callback()
                }
            }, 
            trigger: ['blur', 'change'] 
        }
    ]
})

const emit = defineEmits(['complete'])

const setFormData = async (row: any = null) => {
    loading.value = true
    Object.assign(formData, {
        id: 0,
        title: '',
        member_id: '',
        order_id: 0,
        file_path: '',
        status: 0,
        sign_image: ''
    })
    selectedMemberName.value = ''
    selectedOrderNo.value = ''
    
    if (row) {
        const res = await getContractInfo(row.id)
        if (res.data) {
            Object.assign(formData, res.data)
            
            // 回显会员名称
            if (res.data.member) {
                selectedMemberName.value = res.data.member.nickname || res.data.member.username || res.data.member.mobile
            }
            
            if (res.data.order) {
                selectedOrderNo.value = res.data.order.order_no
            }
        }
    }
    showDialog.value = true
    loading.value = false
}

const showMemberSelect = () => {
    memberSelectRef.value.open()
}

const handleMemberSelect = (member: any) => {
    // 核心逻辑：会员变更时，强制清空已选订单，确保数据归属正确
    if (formData.member_id != member.member_id) {
        clearOrder()
    }
    formData.member_id = member.member_id
    selectedMemberName.value = member.nickname || member.username || member.mobile
}

const showOrderSelect = () => {
    if (!formData.member_id) {
        ElMessage.warning('请先选择会员')
        return
    }
    // 强制传递 member_id 到订单选择组件
    orderSelectRef.value.open(formData.member_id)
}

const handleOrderSelect = (order: any) => {
    formData.order_id = order.order_id
    selectedOrderNo.value = order.order_no
}

const clearOrder = () => {
    formData.order_id = 0
    selectedOrderNo.value = ''
}

const confirm = async (formEl: FormInstance | undefined) => {
    if (!formEl) return
    await formEl.validate(async (valid) => {
        if (valid) {
            loading.value = true
            try {
                if (formData.id) {
                    await editContract(formData.id, formData)
                } else {
                    await addContract(formData)
                }
                ElMessage.success('操作成功')
                showDialog.value = false
                emit('complete')
            } catch (e) {
            }
            loading.value = false
        }
    })
}

// 预览合同文件
const previewContract = () => {
    // 优先显示签名图片（已签署状态），否则显示合同文件
    const fileToShow = formData.sign_image || formData.file_path
    
    if (!fileToShow) {
        ElMessage.warning('没有可预览的合同文件')
        return
    }
    
    currentPreviewFile.value = img(fileToShow)
    filePreviewVisible.value = true
}

const handlePreviewClose = () => {
    currentPreviewFile.value = ''
}

defineExpose({
    setFormData
})
</script>

<style lang="scss" scoped></style>
