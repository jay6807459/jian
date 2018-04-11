<?php

namespace app\index\controller;

//use PHPExcel;
use PHPExcel_IOFactory;
use think\Controller;
use think\Db;
use think\Model;
use think\Validate;
use app\index\model\StandardComponent as SC;

class Standard extends Controller
{

    public function index(){
        $string = 'Dp';
        $match = preg_match('/^DP$/i',$string);
        var_dump($match);
    }


    /**
     * 上传excel,导入标准件
     */
    public function import()
    {
        Db::startTrans();
        try{
            //上传excel
            $file_path = upload_resource('image');
            //导入标准库
            $phpexcel = PHPExcel_IOFactory::createReader('Excel2007');
            $obj_phpexcel = $phpexcel->load(ROOT_PATH . 'public' . DS . 'uploads' . DS . $file_path, $encode = 'utf-8');  //加载文件内容,编码utf-8
            $excel_sheets = $obj_phpexcel->getAllSheets();
            foreach ($excel_sheets as $excel) {
                $excel_array = $excel->toarray();   //转换为数组格式
                $excel_title = $excel->getTitle();
                $array = array();
                $create_time = date('Y-m-d H:i:s');
                foreach ($excel_array as $k => $excel_info) {
                    //第一行不处理
                    if ($k == 0) continue;
                    //标准库存在不处理
                    if(SC::isStandard(preg_replace('/\s+/', '',$excel_info[0]), $excel_title)) continue;
                    //其他
                    if(preg_match('/^\s*DP\s*$/i',$excel_info[0])){
                        $array[$k]['gt_sn'] = strtoupper($excel_info[0]);
                        $array[$k]['gt_width'] = '';
                        $array[$k]['gt_mark'] = strtoupper($excel_info[0]);
                        $array[$k]['gt_length'] = '';
                        $array[$k]['width'] = 0;
                        $array[$k]['length'] = 0;
                        $array[$k]['type'] = $excel_title;
                    }else{
                        $excel_info = strtoupper($excel_info[0]);
                        $gt_sn = preg_replace('/\s+/', '',$excel_info);
                        $excel_info = preg_split('/\s+/', $excel_info);
                        $array[$k]['gt_sn'] = $gt_sn;
                        $array[$k]['gt_width'] = isset($excel_info[0])?$excel_info[0]:'';
                        $array[$k]['gt_mark'] = isset($excel_info[1])?$excel_info[1]:'';
                        $array[$k]['gt_length'] = isset($excel_info[2])?$excel_info[2]:'';
                        $array[$k]['width'] = isset($excel_info[0])?array_sum(preg_split('/[X]/', $excel_info[0])):0;
                        $array[$k]['length'] = isset($excel_info[2])?array_sum(preg_split('/\+/', $excel_info[2])):0;
                        $array[$k]['type'] = $excel_title;
                    }
                    $array[$k]['create_time'] = $array[$k]['update_time'] = $create_time;
                }
                Db::name('standard_component')->insertAll($array);
            }
            Db::commit();
        }catch (\Exception $e){
            echo $e->getMessage();
            Db::rollback();
        }
        $this->redirect('lst');
    }

    /**
     * 判断是否为标准件
     */
    public function check(){
        $gt_sn = input('param.sn');
        $type = input('param.type');
        $is_standard = SC::isStandard($gt_sn,$type);
        echo json_encode(array('is_standard',$is_standard));
    }

    /**
     * 计算转化率
     *
     */
    public function transferRate(){
        $product_id = input('param.product_id');
        var_dump($product_id);die;
        return SC::getTransferRate($product_id);
    }

    /**
     * 标准件列表
     */
    public function lst(){
        $gt_sn = input('get.gt_sn');
        $gt_width = input('get.gt_width');
        $gt_mark = input('get.gt_mark');
        $gt_length = input('get.gt_length');
        $type = input('get.type',0);
//        var_dump($gt_sn,$gt_width,$gt_mark,$gt_length,$type);
        $map = array();
        !empty($gt_sn) && $map['gt_sn'] = ['LIKE', "%$gt_sn%"];
        !empty($gt_width) && $map['gt_width'] = ['LIKE', "%$gt_width%"];
        !empty($gt_mark) && $map['gt_mark'] = ['LIKE', "%$gt_mark%"];
        !empty($gt_length) && $map['gt_length'] = ['LIKE', "%$gt_length%"];
        !empty($type) && $map['type'] = ['EXP', "= $type"];
        $list = Db::name('standard_component')->where($map)->order('id DESC')->paginate(15,false,['query' => request()->param()]);
//        echo Db::name('standard_component')->getLastSql();
        $this->assign('gt_sn', $gt_sn);
        $this->assign('gt_width', $gt_width);
        $this->assign('gt_mark', $gt_mark);
        $this->assign('gt_length', $gt_length);
        $this->assign('type', $type);
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * 标准件详情
     */
    public function view(){
        $id = input('param.id');
        $standard_info = Db::name('standard_component')->where('id', $id)->find();
        $this->assign('standard_info',$standard_info);
        return $this->fetch();
    }

    /**
     * 导入标准件页面
     */
    public function viewImport()
    {
        return $this->fetch();
    }

    /**
     * 标准件新增页面
     */
    public function viewAdd(){
        return $this->fetch();
    }

    /**
     * 标准件编辑页面
     */
    public function viewEdit(){
        $id = input('param.id');
        $standard_info = Db::name('standard_component')->where('id', $id)->find();
        $this->assign('standard_info', $standard_info);
        return $this->fetch();
    }

    /**
     * 标准件新增
     */
    public function add(){
        $post = input('post.');
//        var_dump($post);die;
        $data = $post['data'];
        $rule = ['gt_mark' => 'require'];
        $msg = ['gt_mark.require' => '志特系列不能为空'];
        $validate = new Validate($rule,$msg);
        if (!$validate->check($data)) {
            return ['error' => 1, 'msg' => $validate->getError(), 'data' => ''];
        }
        $data['gt_width'] = strtoupper($data['gt_width']);
        $data['gt_mark'] = strtoupper($data['gt_mark']);
        $data['gt_length'] = strtoupper($data['gt_length']);
        $data['gt_sn'] = $data['gt_width'] . $data['gt_mark'] . $data['gt_length'];
        $data['width'] = isset($data['gt_width'])?array_sum(preg_split('/[X]/', $data['gt_width'])):0;
        $data['length'] = isset($data['gt_length'])?array_sum(preg_split('/\+/', $data['gt_length'])):0;
        $data['create_time'] = $data['update_time'] = date('Y-m-d H:i:s');
        $id = Db::name('standard_component')->data($data)->insert();
        if($id){
            return ['error' => 0, 'msg' => '新增标准件成功', 'data' => ['id' => $id]];
        }else{
            return ['error' => 1, 'msg' => '新增标准件失败', 'data' => ''];
        }
    }


    /**
     * 标准件编辑
     */
    public function edit(){
        $id = input('param.id');
        $post = input('post.');
        $data = $post['data'];
//        var_dump($id,$data);die;
        $rule = ['gt_mark' => 'require'];
        $msg = ['gt_mark.require' => '志特系列不能为空'];
        $validate = new Validate($rule,$msg);
        if (!$validate->check($data)) {
            return ['error' => 1, 'msg' => $validate->getError(), 'data' => ''];
        }
        $data['gt_width'] = strtoupper($data['gt_width']);
        $data['gt_mark'] = strtoupper($data['gt_mark']);
        $data['gt_length'] = strtoupper($data['gt_length']);
        $data['gt_sn'] = $data['gt_width'] . $data['gt_mark'] . $data['gt_length'];
        $data['width'] = isset($data['gt_width'])?array_sum(preg_split('/[X]/', $data['gt_width'])):0;
        $data['length'] = isset($data['gt_length'])?array_sum(preg_split('/\+/', $data['gt_length'])):0;
        $data['update_time'] = date('Y-m-d H:i:s');
        $effect_rows = Db::name('standard_component')->where('id', $id)->data($data)->update();
        if($effect_rows){
            return ['error' => 0, 'msg' => '编辑标准件成功', 'data' => ''];
        }else{
            return ['error' => 1, 'msg' => '编辑标准件失败', 'data' => ''];
        }
    }

    /**
     * 标准件删除
     */
    public function del(){
        $standard_id = input('param.standard_id');
        $effect_rows = Db::name('standard_component')->where('id', $standard_id)->delete();
//        echo Db::name('standard_component')->getLastSql();
        if($effect_rows){
            return ['error' => 0, 'msg' => '删除标准件成功', 'data' => ''];
        }else{
            return ['error' => 1, 'msg' => '删除标准件失败', 'data' => ''];
        }
    }


}
