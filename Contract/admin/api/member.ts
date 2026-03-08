import request from '@/utils/request'

export function getMemberList(params: Record<string, any>) {
    return request.get('member/member', { params })
}
