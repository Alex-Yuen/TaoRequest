<?php
/**
 * Created by AnQin on 2018-08-03.
 * Email:an-qin@qq.com
 */

namespace an\tao\request;


use an\tao\TaoApiBase;

class TbkScOrderDetailsGet extends TaoApiBase {
    protected string $api = 'taobao.tbk.sc.order.details.get';
    protected array $apiParas = [
        'query_type' => 1,
        'page_no'    => 1,
        'page_size'  => 100
    ];

    public function setQueryType(int $value) {
        return $this->putOtherTextParam('query_type', $value);
    }

    public function setPositionIndex($value) {
        return $this->putOtherTextParam('position_index', $value);
    }

    public function setMemberType(int $value) {
        return $this->putOtherTextParam('member_type', $value);
    }

    public function setJumpType(int $value) {
        return $this->putOtherTextParam('jump_type', $value);
    }

    public function setStartTime($value) {
        return $this->putOtherTextParam('start_time', $value);
    }

    public function setEndTime($value) {
        return $this->putOtherTextParam('end_time', $value);
    }

    public function setPageNo(int $value) {
        return $this->putOtherTextParam('page_no', $value);
    }

    public function setPageSize(int $value) {
        return $this->putOtherTextParam('page_size', $value);
    }

    public function setTkStatus(int $value) {
        return $this->putOtherTextParam('tk_status', $value);
    }

    public function setOrderScene(int $value) {
        return $this->putOtherTextParam('order_scene', $value);
    }
}