<?php
namespace Home\Controller;
use Think\Controller;
class UserController extends Controller {
    public function test(){
        echo phpversion();
    }
	//user_phoneNumber, user_psw
	//--> isSuccess, UID
    public function Login(){
        $result = array(
        	'isSuccess' => false,
        	'UID' => ''
        );
        $userInfoTable = M('userinfo');
        $condition['phonenumber'] = I('user_phoneNumber');
        $condition['password'] = I('user_psw');
        $ans = $userInfoTable -> where($condition) -> find();
        if($ans !== false && $ans > 0){
            $result['isSuccess'] = true;
            $result['UID'] = $ans['uid'];
        }
        echo json_encode($result);
    }

    //user_phoneNumber, user_psw, user_nickname
    //--> isSuccess, UID
    public function SignUp(){
    	$result = array(
    		'isSuccess' => false,
    		'UID' => ''
    	);
        $userInfoTable = M('userinfo');
        $phoneNumber = I('user_phoneNumber');
        $condition['phonenumber'] = $phoneNumber;

        $ans = $userInfoTable->where($condition)->find();

        if($ans !== false  && $ans == null){
            $UID = $this->getUuid();
            if($this->createTaskInfoTable($UID)){
                $data['uid'] = $UID;
                $data['phonenumber'] = $phoneNumber;
                $data['password'] = I('user_psw');
                $data['nickname'] = I('user_nickname');
                if($userInfoTable -> data($data) -> add() == 1){
                    $result['UID'] = $UID;
                    $result['isSuccess'] = true;
                }
            }
        }
        echo json_encode($result);
    }

    //UID, user_oldPassword, user_newPassword
    //--> isSuccess
    public function ChangePassword(){
    	$result = array(
    		'isSuccess' => false
    	);
        $userInfoTable = M('userinfo');
        $condition['uid'] = I('UID');
        $condition['password'] = I('user_oldPassword');
        $data['password'] = I('user_newPassword');
        $ans = $userInfoTable->where($condition) -> data($data) -> save();
        if($ans !== false && $ans > 0){
            $result['isSuccess'] = true;
        }
        echo json_encode($result);
    }

    //UID, user_newNickname
    //--> isSuccess
    public function ChangeNickname(){
    	$result = array(
    		'isSuccess' => false
    	);
        $userInfoTable = M('userinfo');
        $condition['uid'] = I('UID');
        $data['nickname'] = I('user_newNickname');
        $ans = $userInfoTable->where($condition) -> save($data);
        if($ans !== false && $ans > 0){
            $result['isSuccess'] = true;
        }
        echo json_encode($result);
    }

    function getUuid(){
        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        $charid = md5(uniqid(rand(), true));
        $uuid = substr($charid, 0, 32);
        return $uuid;
    }

    function createTaskInfoTable($uid){
        $sql = "CREATE TABLE IF NOT EXISTS taskinfo$uid (
        `title` varchar(100) NOT NULL,
        `content` varchar(200) NOT NULL,
        `createtime` varchar(30) NOT NULL PRIMARY KEY,
        `lastedittime` varchar(30) NOT NULL,
        `alerttime` varchar(30) NOT NULL,
        `timestamp` varchar(30) NOT NULL,
        `level` int(11) NOT NULL,
        `state` int(11) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        $con = mysql_connect('localhost', 'root', '');
        if($con === false){
            die('Could not connect: '.mysql_error());
            return false;
        }
        mysql_select_db('todolist', $con);
        if(!mysql_query($sql, $con)){
            die("Could not create this table, .mysql_errnor()：".mysql_errno()."mysql_error()：".mysql_error());
        }
        mysql_close($con);
        return true;
    }
    
    function createUserInfoTable(){
        $sql = "CREATE TABLE IF NOT EXISTS userinfo (
        `uid` varchar(32) NOT NULL PRIMARY KEY,
        `phonenumber` varchar(20) NOT NULL,
        `password` varchar(64) NOT NULL,
        `nickname` varchar(50) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        $con = mysql_connect('localhost', 'root', '');
        if($con === false){
            die('Could not connect: '.mysql_error());
            return false;
        }
        mysql_select_db('todolist', $con);
        if(!mysql_query($sql, $con)){
            die("Could not create this table, .mysql_errnor()：".mysql_errno()."mysql_error()：".mysql_error());
        }
        mysql_close($con);
        return true;
    }
}