<?php
class IndexController extends ApiPublicController 
{
    public function actionIndex()
	{
        if($this->validateSign())
        	exit('1');
        else
        	exit('2');
    }
}