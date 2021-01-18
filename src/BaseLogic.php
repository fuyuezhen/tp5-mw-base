<?php
namespace fuyuezhen\base;

/**
 * 逻辑层基类
 */
class BaseLogic
{
    // 绑定实例
    protected $instances = [];

    /**
     * 错误提示
     */
    protected $errMsg = '';

    protected $table_field = "";
    protected $table_alias = "";
    protected $model_where = "";
    protected $join_array = [];

    protected $is_to_array = true;
    /**
     * 连表设置
     *
     * @param string $table
     * @param [type] $condition
     * @param string $type
     * @return void
     */
    public function join($table = "", $condition = null, $type = 'INNER')
    {
        $this->join_array[] = [
            'join_table' => $table,
            'join_condition' => $condition,
            'join_type' => $type,
        ];
        return $this;
    }

    /**
     * 结果转成数组
     * @param string $is_to_array
     * @return void
     */
    public function isToArray($is_to_array = true)
    {

        $this->is_to_array =  $is_to_array;
        return $this;
    }
    /**
     * 自定义条件
     * @param string $where
     * @return void
     */
    public function where($where = "")
    {

        $this->model_where =  $where;
        return $this;
    }
    /**
     * 设置字段
     * @param string $field
     * @return void
     */
    public function field($field = "")
    {

        $this->table_field =  $field;
        return $this;
    }
    /**
     * 别名
     * @param string $alias
     * @return void
     */
    public function alias($alias = "")
    {

        $this->table_alias =  $alias;
        return $this;
    }

    /**
     * 获取错误信息
     */
    public function getError()
    {
        return $this->errMsg;
    }

    /**
     * 返回信息
     * @param [type] $code
     * @param string $msg
     * @return void
     */
    public function setReturnMsg($code, $msg = '')
    {
        
        $this->errMsg = $msg;

        return [
            'code' => $code,
            'msg' => $msg,
        ];
    }

    /**
     * 对于分页数据返回处理
     * @param array $info      需要处理的数据
     * @param int   $count     总数据量
     * @param int   $page_size 每页显示条数
     */
    protected function setReturnList($info, $count, $page_size, $page_index = 1)
    {
        if ($page_size == 0) {
            $page_count = 1;
        } else {
            if ($count % $page_size == 0) { //
                $page_count = $count / $page_size; // 5 3
            } else {
                $page_count = (int) ($count / $page_size) + 1; // 没有除整
            }
        }
        return [
            'data'        => $info,       // 返回的数据
            'total_count' => $count,      // 查询的数据量
            'page_index'    => $page_index, // 当前的页面
            'page_size'   => $page_size,  // 每页的数据
            'page_count'  => $page_count  // 共几页
        ];
    }

    /**
     * 获取列表，分页
     * @param string $where 查询条件
     * @param string $with 关联查询
     * @param string $order 排序
     * @param boolean $field 查询自动
     * @param array $page_param 分页请求参数
     * @return array 
     * [
     *      data: []
     *      total_count: 0
     *      page_index: "1"
     *      page_size: "25"
     *      page_count: 1
     * ]
     */
    public function getPageList($where = '1 = 1', $with = '', $order = '', $field = true, $page_param = [])
    {
        // 当前模型
        $model = $this->loadModel();

        // 设置别名
        if(!empty($this->table_alias)){
            $model = $model->alias($this->table_alias);
        }

        // 自定义条件
        if(!empty($this->model_where)){
            $model = $model->where($this->model_where);
        }

        // 设置连表
        if(!empty($this->join_array)){
            foreach($this->join_array as $join){
                $model = $model->join($join['join_table'], $join['join_condition'], $join['join_type']);
            }
        }

        // 设置查询字段
        if(!empty($this->table_field)){
            $field = $this->table_field;
        }


        if ($page_param === false) {
            // 执行SQL链式
            $data = $model->where($where)->with($with)->order($order)->field($field)->select();
            
            if($this->is_to_array)
            {
                $data = $data->toArray();
            }

            // 返回格式
            return $data;
        } elseif (is_int($page_param)) {
            // 执行SQL链式
            $data = $model->where($where)->with($with)->order($order)->field($field)->limit($page_param)->select();

            if($this->is_to_array)
            {
                $data = $data->toArray();
            }

            // 返回格式
            return $data;
        } else {
            // 总数
            $total_count = $model->where($where)->with($with)->order($order)->field($field)->count();
            
            // 当前页，如果是0，或者没有传参，则默认第一页
            $page_index = (!isset($page_param['page_index']) || empty($page_param['page_index'])) ? request()->param('page_index', 0) : $page_param['page_index'];
            // 每页几条，如果是0则查询所有
            $page_size  = (!isset($page_param['page_size']) || empty($page_param['page_size'])) ? request()->param('page_size', 0) : $page_param['page_size'];
            // 执行SQL链式
            $data = $model->page($page_index, $page_size)->where($where)->with($with)->order($order)->field($field)->select();

            if($this->is_to_array)
            {
                $data = $data->toArray();
            }

            
            // 返回格式
            return $this->setReturnList((array) $data, (int) $total_count, (int) $page_size, (int) $page_index);
        }
    }

    /**
     * 获取列表，全部
     * @param string $where 查询条件
     * @param string $with 关联查询
     * @param string $order 排序
     * @param boolean $field 查询自动
     * @return array 
     */
    public function getList($where = '1 = 1', $with = '', $order = '', $field = true)
    {
        return $this->getPageList($where, $with, $order, $field, false);
    }

    /**
     * 查询单条数据
     *
     * @param array $id 查询条件或者id
     * @param string $with 关联查询
     * @return code
     */
    public function getInfo($id = [], $with = '', $order = '', $field = true)
    {
        $model = $this->loadModel();
        if (is_int($id)) {
            $where = [$model->getPk() => $id];
        } else {
            $where = $id;
        }
        $info = $model->where($where)->with($with)->order($order)->field($field)->find();
        if ($info) {
            return $info->toArray();
        }
        return $info;
    }
    /**
     * 查询某个字段
     *
     * @param array $id 查询条件或者id
     * @param string $field 查询字段
     * @return code
     */
    public function getValue($id = [], $field = '')
    {
        $model = $this->loadModel();
        
        if (is_int($id)) {
            $where = [$model->getPk() => $id];
        } else {
            $where = $id;
        }
        if (empty($field)) {
            $field = $model->getPk();
        }
        $info = $model->where($where)->value($field);
        return $info;
    }

    /**
     * 获取键值对
     *
     * @param string $value
     * @param string $key
     * @param array $where
     * @return void
     */
    public function getColumns($value = '', $where = [], $order = [])
    {
        $model = $this->loadModel();
        if (empty($value)) {
            return false;
        }
        $result = $model->where($where)->order($order)->column($value);
        return $result;
    }

    /**
     * 设置某项的值
     * @param int   $id 主键ID 或者 where 条件
     * @param array $data ['字段' => '值']
     * @return code
     */
    public function setValue($id, $data)
    {
        $model = $this->loadModel();
        if (is_int($id)) {
            $where = [$model->getPk() => $id];
        } else {
            $where = $id;
        }

        $result = $model->allowField(true)->save($data, $where);
        if ($result) {
            return SUCCESS;
        }
        return SET_VALUE_ERROR;
    }

    /**
     * 保存记录，包含主键ID为新增
     * @param [array] $data 要写入数据表的数组，
     * @param [array] $where 更新的条件
     * @return code 主键 或者 false
     */
    public function saveInfo($data, $where = [], $insert = false)
    {
        $model = $this->loadModel();
        // 主键
        $pk = $model->getPk();
        if (isset($data[$pk])) {
            $where[] = [$pk, '=', $data[$pk]];
        }
        if ($insert === true || empty($where)) {
            // 新增，返回的是当前模型的对象实例
            $result = $model->allowField(true)->create($data);
            if($result){
                return $result->$pk;
            }
        } else {
            // 更新，方法返回影响的记录数
            $result = $model->allowField(true)->save($data, $where);
            if($result){
                return $this->loadModel()->$pk;
            }
        }

        return false;
    }

    /**
     * 自增或自减
     * @param [string] $field 字段名
     * @param int $stepLength 步长
     * @param [array|string] $where  条件
     * @param string $method 自增方法 否则就是 自减
     * @return code
     */
    public function setIncOrDec($field, $stepLength, $where = [], $method = 'setInc')
    {
        $model = $this->loadModel();

        if ($method == 'setInc') {
            $result = $model->where($where)->setInc($field, $stepLength);
        } else {
            $result = $model->where($where)->setDec($field, $stepLength);
        }

        return ($result) ? SUCCESS : SAVE_INFO_ERROR;
    }


    /**
     * 删除
     * @param int   $id 主键ID 或者 where 条件
     * @param boolean $is_del true 为物理删除
     * @return code
     */
    public function deleteInfo($id, $is_del = false)
    {
        $model = $this->loadModel();
        if (is_int($id)) {
            $where = [$model->getPk() => $id];
        } else {
            $where = $id;
        }
        // 软删除：先查询查询，在删除才生效，如果直接执行delete()方式无效（将不会执行任何操作）。
        $result = $model->destroy($where, $is_del);

        return ($result) ? SUCCESS : DELETE_INFO_ERROR;
    }

    /**
     * 获取对应逻辑的模型类
     * @param string $className 类名 格式：User | user\UserRole | \core\common\model\User;
     * @return void
     */
    public function loadModel($className = null)
    {
        $nameArray = explode('\\', get_called_class());
        // 自动读取或者传入
        if($className == null){
            $class     = \str_replace('logic', 'model', get_called_class());
            $class     = '\\' . \str_replace('loadLogic', 'loadModel', $class);
        }else{
            $class = $className;
        }
        // 获取platform或者biz
        if(strpos($class, '\\') === 0){
            $nameArray = explode('\\', $class);
        }
        if (!isset($this->instances[$class])) {
             // 绝对命名空间
            if(strpos($class, '\\') === 0){
            }else{
                $class     = '\\model\\' . $class;
            }
            $this->instances[$class] = new $class;
        }
        return $this->instances[$class];
    }
}
