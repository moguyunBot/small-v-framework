<?php
namespace plugin\pay\app\admin\controller;

use app\admin\controller\Base;
use plugin\pay\app\model\Order as OrderModel;

class Order extends Base
{
    public function index()
    {
        $page    = (int)($this->get['page'] ?? 1);
        $keyword = trim($this->get['keyword'] ?? '');
        $status  = $this->get['status'] ?? '';
        $payType = $this->get['pay_type'] ?? '';

        $query = OrderModel::order('id desc');
        if ($keyword)  $query->where('order_no|subject', 'like', "%{$keyword}%");
        if ($status !== '') $query->where('status', (int)$status);
        if ($payType)  $query->where('pay_type', $payType);

        $list = $query->paginate(['list_rows' => 20, 'page' => $page]);
        return $this->view(['list' => $list, 'keyword' => $keyword, 'status' => $status, 'pay_type' => $payType]);
    }
}
