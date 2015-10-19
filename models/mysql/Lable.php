<?php
class Lable extends CActiveRecord
{
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * 女神标签
     *
     * @param  int $heroine_id
     *
     * @return array
     *
     */
    public function heroineLables($heroine_id)
    {
        $data = array();
        try{
            $con_heroine = Yii::app()->db_heroine;
            $data = $con_heroine->createCommand()
                            ->select('lable_id')
                            ->from('heroine_tag')
                            ->where('heroine_id=:heroine_id')
                            ->bindParam(':heroine_id', $heroine_id, PDO::PARAM_INT, 11)
                            ->queryColumn();
        }catch(Exception $e){
            error_log($e);
        }

        return $data;
    }

    /**
     * 获取标签
     *
     */
    public function lables()
    {
        $data = array();
        try{
            $con_common = Yii::app()->db_common;
            $info = $con_common->createCommand()
                            ->select('lable_id, lable')
                            ->from('lable')
                            ->queryAll();
            $data = array();
            foreach ($info as $val) {
                $data[$val['lable_id']] = $val['lable'];
            }
        }catch(Exception $e){
            error_log($e);
        }

        return $data;
    }
}