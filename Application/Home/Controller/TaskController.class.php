<?php
namespace Home\Controller;
use Think\Controller;
class TaskController extends Controller {
    //UID, TaskModel
    //--> isSuccess
    public function AddTask(){
    	$result = array(
    		'isSuccess' => false
    	);
        
        $UID = I('UID');
        if($this->isValidUID($UID)){
            $taskInfoTable = M('taskinfo'.$UID);

            $taskModel = I('TaskModel');
            $taskCreateTime = $taskModel['createtime'];
            $condition['createtime'] = $taskCreateTime;
            $ans = $taskInfoTable -> where($condition) -> find();
            if($ans !== false && $ans == 0){
                $data['title'] = $taskModel['title'];
                $data['content'] = $taskModel['content'];
                $data['createtime'] = $taskModel['createtime'];
                $data['lastedittime'] = $taskModel['lastedittime'];
                $data['alerttime'] = $taskModel['alerttime'];
                $data['level'] = $taskModel['level'];
                $data['state'] = $taskModel['state'];
                
                $insert = $taskInfoTable -> data($data) -> add();
                if($insert !== false && $insert == 1){
                    $result['isSuccess'] = true;
                }
            }
        }
        echo json_encode($result);
    }

    //UID, TaskModel, taskID
    //--> isSuccess
    public function UpdateTask(){
    	$result = array(
    		'isSuccess' => false
    	);

        $UID = I('UID');
        if($this->isValidUID($UID)){
            $taskInfoTable = M('taskinfo'.$UID);

            $taskModel = I('TaskModel');
            $taskCreateTime = $taskModel['createtime'];
            $condition['createtime'] = $taskCreateTime;
            $ans = $taskInfoTable -> where($condition) -> find();
            if($ans !== false && $ans > 0){
                $data['title'] = $taskModel['title'];
                $data['content'] = $taskModel['content'];
                $data['lastedittime'] = $taskModel['lastedittime'];
                $data['alerttime'] = $taskModel['alerttime'];
                $data['level'] = $taskModel['level'];
                $data['state'] = $taskModel['state'];

                $condition['createtime'] = $taskModel['createtime'];
                $update = $taskInfoTable -> where($condition) -> data($data) -> save();
                if($update !== false && $update == 1){
                    $result['isSuccess'] = true;
                }
            }
        }
        echo json_encode($result);
    }

    //UID, taskID, finished(0~1)
    //--> isSuccess
    public function SwitchTask(){
    	$result = array(
    		'isSuccess' => false
    	);
        $UID = I('UID');
        if($this->isValidUID($UID)){
            $taskInfoTable = M('taskinfo'.$UID);
            $taskCreateTime = I('createtime');
            $condition['createtime'] = $taskCreateTime;
            $ans = $taskInfoTable -> where($condition) -> find();
            if($ans !== false && $ans > 0){
                $finished = I('finished');
                $data['state'] = ($finished ^ 1) * 2;
                $update = $taskInfoTable -> where($condition) -> data($data) -> save();
                if($update !== false && $update == 1){
                    $result['isSuccess'] = true;
                }
            }
        }
        echo json_encode($result);
    }

    //UID, TaskID
    //--> isSuccess
    public function DeleteTask(){
    	$result = array(
    		'isSuccess' => false
    	);
        $UID = I('UID');
        if($this->isValidUID($UID)){
            $taskInfoTable = M('taskinfo'.$UID);
            $taskCreateTime = I('createtime');
            $condition['createtime'] = $taskCreateTime;
            $ans = $taskInfoTable -> where($condition) -> find();
            if($ans !== false && $ans > 0){
                $data['state'] = ($ans['state'] & 2) + 1;
                $delete = $taskInfoTable -> where($condition) -> data($data) -> save();
                if($delete !== false && $delete == 1){
                    $result['isSuccess'] = true;
                }
            }
        }
        echo json_encode($result);
    }

    //UID, array{TaskModel}
    //--> isSuccess, user_nickname, array{TaskModel}
    public function SynchronizeTask(){
    	$result = array(
    		'isSuccess' => false,
    		'user_nickname' => '',
            'taskModelArr' => array()
    	);
        $userInfoTable = M('userinfo');
        $condition['uid'] = $UID;
        $ans = $userInfoTable -> where($condition) -> find();

        $UID = I('UID');
        $userInfoTable = M('userinfo');
        $ans = $userInfoTable -> where('uid="'.$UID.'"') -> find();
        if($ans !== false && $ans > 0){//find the user
            //get nickname
            $result['user_nickname'] = $ans['nickname'];
            //get all task model in database
            $taskInfoTable = M('taskinfo'.$UID);
            //all tasks not deleted
            $ans = $taskInfoTable -> where('state = 0 or state = 2') ->select();
            if($ans === false){
                echo json_encode($result);
                return;
            }
            foreach($ans as &$taskModel){
                $taskModelArrInDatabase[$taskModel['createtime']] = $taskModel;
            }
            $taskModelArrInDatabaseKeys = array_keys($taskModelArrInDatabase);

            //tasks from client
            $taskModelArrInClient = I('TaskModelArr');
            $taskModelArrInClientKeys = array_keys($taskModelArrInClient);

            //check tasks from client to database
            foreach($taskModelArrInClientKeys as &$key){
                $taskModelInClient = $taskModelArrInClient[$key];
                $exist = array_key_exists($key, $taskModelArrInDatabase);
                if($exist){
                    //task in database
                    $taskModelInDatabase = $taskModelArrInDatabase[$key];

                    if($taskModelInClient['lastedittime'] < $taskModelInDatabase['lastedittime']){
                        //return to client
                        $data['title'] = $taskModelInDatabase['title'];
                        $data['content'] = $taskModelInDatabase['content'];
                        $data['createtime'] = $taskModelInDatabase['createtime'];
                        $data['lastedittime'] = $taskModelInDatabase['lastedittime'];
                        $data['alerttime'] = $taskModelInDatabase['alerttime'];
                        $data['level'] = $taskModelInDatabase['level'];
                        $data['state'] = $taskModelInDatabase['state'];

                        $result['taskModelArr'][] = $data;
                    }
                    else if($taskModelInClient['lastedittime'] > $taskModelInDatabase['lastedittime']){//update to database
                        $data['title'] = $taskModelInClient['title'];
                        $data['content'] = $taskModelInClient['content'];
                        $data['lastedittime'] = $taskModelInClient['lastedittime'];
                        $data['alerttime'] = $taskModelInClient['alerttime'];
                        $data['level'] = $taskModelInClient['level'];
                        $data['state'] = $taskModelInClient['state'];

                        $condition['createtime'] = $taskModelInClient['createtime'];
                        $update = $taskInfoTable -> where($condition) -> data($data) -> save();
                        if($update == false || $update == 0){
                            echo json_encode($result);
                            return;
                        }
                    }
                }
                else{//insert to database
                    $ans = $taskInfoTable -> where('createtime="'.$taskModelInClient['createtime'].'"') -> find();
                    if($ans !== false){
                        if($ans == 0){
                            $data['title'] = $taskModelInClient['title'];
                            $data['content'] = $taskModelInClient['content'];
                            $data['createtime'] = $taskModelInClient['createtime'];
                            $data['lastedittime'] = $taskModelInClient['lastedittime'];
                            $data['alerttime'] = $taskModelInClient['alerttime'];
                            $data['level'] = $taskModelInClient['level'];
                            $data['state'] = $taskModelInClient['state'];
                        
                            $insert = $taskInfoTable -> data($data) -> add();
                            if($insert == false || $insert == 0){
                                echo json_encode($result);
                                return;
                            }
                        }
                    }
                    else{
                        echo json_encode($result);
                        return;
                    }   
                }
            }

            //check tasks from database to client
            foreach($taskModelArrInDatabaseKeys as &$key){
                $taskModelInDatabase = $taskModelArrInDatabase[$key];
                $exist = array_key_exists($key, $taskModelArrInClient);
                if(!$exist){
                    //return to client
                    $data['title'] = $taskModelInDatabase['title'];
                    $data['content'] = $taskModelInDatabase['content'];
                    $data['createtime'] = $taskModelInDatabase['createtime'];
                    $data['lastedittime'] = $taskModelInDatabase['lastedittime'];
                    $data['alerttime'] = $taskModelInDatabase['alerttime'];
                    $data['level'] = $taskModelInDatabase['level'];
                    $data['state'] = $taskModelInDatabase['state'];

                    $result['taskModelArr'][] = $data;
                }
            }
            $result['isSuccess'] = true;
        }
        echo json_encode($result);
    }

    function isValidUID($UID){
        $userInfoTable = M('userinfo');
        $condition['uid'] = $UID;
        $ans = $userInfoTable -> where($condition) -> find();
        if($ans !== false && $ans > 0){
            return true;
        }
        else{
            return false;
        }
    }
}