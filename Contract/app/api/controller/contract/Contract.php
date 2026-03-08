<?php
namespace addon\Contract\app\api\controller\contract;

use core\base\BaseApiController;
use addon\Contract\app\service\api\contract\ContractService;
use think\Response;

/**
 * Contract Controller for API
 * @package addon\Contract\app\api\controller\contract
 */
class Contract extends BaseApiController
{
    /**
     * Get My Contract List
     * @return Response
     */
    public function lists()
    {
        $data = $this->request->params([
            ['status', '']
        ]);
        $where = [];
        if ($data['status'] !== '') {
            $where[] = ['status', '=', $data['status']];
        }
        
        $service = new ContractService();
        $res = $service->getPage($where);
        return success($res);
    }

    /**
     * Get Contract Info
     * @param int $id
     * @return Response
     */
    public function info(int $id)
    {
        $service = new ContractService();
        $res = $service->getInfo($id);
        if (empty($res)) {
            return fail('CONTRACT_NOT_EXIST');
        }
        return success($res);
    }



    /**
     * Confirm Sign
     * @param int $id
     * @return Response
     */
    public function confirmSign(int $id)
    {
        $data = $this->request->params([
            ['code', '']
        ]);
        if (empty($data['code'])) {
            return fail('CODE_REQUIRED');
        }
        
        $service = new ContractService();
        try {
            $res = $service->confirmSign($id, $data['code']);
            return success($res);
        } catch (\Exception $e) {
            return fail($e->getMessage());
        }
    }
}
