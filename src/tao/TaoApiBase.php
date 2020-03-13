<?php
/**
 * Created by AnQin on 2017-01-06.
 * Email:an-qin@qq.com
 */

namespace an\tao;


abstract class TaoApiBase {
    protected string $api = '';
    protected array $apiParas = [];

    public function getApiMethodName(): string {
        return strtolower($this->api);
    }

    public function getApiParas(): array {
        return $this->apiParas;
    }

    public function putOtherTextParam($key, $value): TaoApiBase {
        $this->apiParas[$key] = $value;

        return $this;
    }
}