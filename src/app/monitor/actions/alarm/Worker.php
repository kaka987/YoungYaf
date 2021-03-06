<?php
class Action_Worker extends Controller_Application {

    protected $layout = 'main';

    public function run() {

    	$this->groupTable = Sys_Database::getTable('worker');
    	$this->alarmModel = $this->loadModel();

    	if ($this->getRequest()->getPost('o') == 'delete') {
    		$gid = $this->getRequest()->getPost('gid');
    		$this->delete($gid);
    	}
    	
    	if ($this->getRequest()->getQuery('o') == 'edit') {
    		
    		 Yaf_Dispatcher::getInstance()->disableView();
    		 $this->initView();
    		 
    		 $configData = array();
    		 $id = $this->getRequest()->getQuery('id');
    		 if ($id) {
    		 	
    		 	$configDataTmp = $this->alarmModel->selectData($id,1,1,$this->groupTable);
                $configData = $configDataTmp[0];
    		 }

    		 $this->display('addworker', array('D'=>$configData));
    	}
    	
    	if ($this->getRequest()->getQuery('o') == 'add') {
    		 $this->add();
    	}
    	
    	$page = $this->getRequest()->getQuery('p');
    	$alarmList['page'] = $page ? $page : 1;
    	$id = $this->getRequest()->getQuery('pluginid');
    	$pid = $id ? $id : NULL;
    	$limit = 20;
    	
    	$alarmList['list'] = $this->alarmModel->selectData($pid, ($alarmList['page']-1)*$limit, $limit, $this->groupTable);
    	$alarmNum = $this->alarmModel->selectData($pid, -1, -1, $this->groupTable);
    	$alarmList['num'] = $alarmNum['num'];
    	
    	$alarmList['pageCount'] = (int)($alarmList['num']/$limit) + 1;
    	$alarmList['pageView'] = $this->page($alarmList['page'], $alarmList['pageCount']);
    	
    	$this->getView()->assign('alarmList', $alarmList);
    }

    private function loadModel() {
    	 
    	return new Model_Alarm_Config();
    }
    
    public function delete($uid=0) {
    	
    	if ($uid==0) Ym_CommonTool::myoutput(0,'Query is error!');
    	
    	$this->alarmModel->deleteWorker($uid);
    	$result =  $this->alarmModel->deleteData($uid, $this->groupTable);
    	if ($result) {
    		Ym_CommonTool::myoutput(1,'Query is OK!',$result);
    	} else {
    		Ym_CommonTool::myoutput(0,'Query is error!',array());
    	}
		
    	$this->display = 'none';
    }
    
    public function add() {
    
    	$this->configData = $this->getPostData($this->checkPostData());
    	
    	if (isset($this->configData['id']) AND $this->configData['id']) {
    		$result =  $this->updateData();
    	} else {
    		$result =  $this->insertData();
    	}
    	if ($result) {
    		Ym_CommonTool::myoutput(1,'Query is OK!',$result);
    	} else {
    		Ym_CommonTool::myoutput(0,'Query is error!',array());
    	}
    	
    	$this->display = 'none';
    }
    
     private function checkPostData() {
    	
    	$postData = $this->getRequest()->getPost();
    	
    	return $postData;
    }
    
    private function getPostData($configData) {
    	 
    	$arr = array('notify_method','notify_type','apps','hosts','services','user_id');
    	
    	foreach($arr as $k) {
    		
	    	if (isset($configData[$k]) AND $configData[$k]) {
	    		$configData[$k] = implode(',', $configData[$k]);
	    	}
    	}
    	
    	return $configData;
    }
        
    public function page($p, $pageCount) {
    
    	$page = '';
    	$active = '';
		$pageStart = 1;
        if($p>10) $pageStart = $p-9;
        $pageMax = ($pageCount<10) ? $pageCount+1 : $pageStart + 10;
        
        $page  = '<ul>';
        $page .= '<li class="previous disabled"><a href="#">&#8249;</a></li>';
        for($i=$pageStart;$i<$pageMax;$i++) {
        	if($i==$p) $active = 'active';
        	$page .= '<li><a class="'.$active.'" href="?p='.$i.'">'.$i.'</a></li>';
        }
        
        $page .= '<li class="next"><a href="#">&#8250;</a></li>';
        $page .= '</ul>';
        
        return $page;
    }

    private function insertData() {
    	
    	if ($this->configData) {
            return $this->alarmModel->insertWorker($this->configData);
    	}
    	return FALSE;
    }
    
    private function updateData() {
    	if ($this->configData) {
	    	$where = 'id='.$this->configData['id'];
	    	unset($this->configData['id']);
            return $this->alarmModel->updateData($this->configData, $where, $this->groupTable);
    	}
    	return FALSE;
    }
    
}