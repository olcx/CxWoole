<?php
/**
 * CxDao是对Mysql操作封装的一个公共类库
 */
class UserDao extends CxDao{

    public function getAllUsers(){
        return $this->fetchs();
    }



}