<?php 
namespace fuyuezhen\base;
use think\Model;
use think\model\concern\SoftDelete; // 软删除
/**
 * 数据层，基类
 */
class BaseModel extends Model
{
    // 设置软删除
    use softDelete;
    protected $deleteTime        = "delete_time";  // 删除字段
    protected $defaultSoftDelete = null; // 默认值

    // 是否需要自动写入时间戳 如果设置为字符串 则表示时间字段的类型
    protected $autoWriteTimestamp = 'datetime';
    // 创建时间字段
    protected $createTime = 'create_time';
    // 更新时间字段
    protected $updateTime = 'update_time';

    // 写入自动完成
    protected $insert = ['listorder'];

    /**
     * 新增排序时间，时间戳
     */
    protected function setListorderAttr($value)
    {
        return empty($value)?time():strtotime($value);
    }

    /**
     * 格式化排序时间
     */
    protected function getListorderAttr($value)
    {
        return !empty($value) ? date('Y-m-d H:i:s', $value) : '';
    }

}