<?php
/**
 * 用户上传图片接口
 */
class UploadController extends ApiPublicController
{
    /**
     * 插入用户女神相片
     *
     * @param string $user_id
     * @param string $token
     *
     */
    public function actionInsertUserGoddess()
    {
        $qq         = trim(Yii::app()->request->getParam('qq'));
        $name       = trim(Yii::app()->request->getParam('name'));
        $nick_name  = trim(Yii::app()->request->getParam('nickname'));
        $img        = trim(Yii::app()->request->getParam('img'));
        
        // 参数检查
        if(!isset($_REQUEST['qq']) || !isset($_REQUEST['name']) || !isset($_REQUEST['nickname']) || !isset($_REQUEST['img'])){
            $this->_return('MSG_ERR_LESS_PARAM');
        }
        
        $param = array(
            'qq' => $qq,
            'name' => $name,
            'nick_name' => $nick_name,
        );
        
        $user_goddess_id = Upload::model()->insertUserGoddess($param);
        $param = array(
            'url' => $img,
            'user_goddess_id' => $user_goddess_id,    
        );
        $res = Upload::model()->insertUpload($param);
        if($res == false){
            $this->_return('MSG_ERR_UNKOWN');
        }
        $this->_return('MSG_SUCCESS');

    }

}