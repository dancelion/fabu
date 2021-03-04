<?php


namespace app\apps\model;


use think\Model;

class AppsModel extends Model
{
    protected $autoWriteTimestamp = true;

    public function remove()
    {
        $query = $this->db(false);
        $query->startTrans();
        try{
            VersionModel::where(['appId'=>$this->{'id'}])->delete();
            $this->delete();
            $query->commit();
        }catch (\Exception $e){
            $this->error = $e->getMessage();
            $query->rollback();
            return false;
        }
        return true;
    }
}