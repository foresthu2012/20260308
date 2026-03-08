import request from '@/utils/request'

export function getContractList(params: Record<string, any>) {
  return request.get('Contract/contract', params)
}

export function getContractInfo(id: number) {
  return request.get(`Contract/contract/${id}`)
}

export function confirmSign(id: number, data: { code: string }) {
  return request.post(`Contract/contract/confirm_sign/${id}`, data)
}
