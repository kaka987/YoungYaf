<?php

/**
 * The main process of monitoring
 * 
 */
class Action_Check_weblog_key_test extends Yaf_Action_Abstract
{
	
	
    public function execute() {

    	$this->Model = new Model_Alarm_Monitorcheck;
    	
    	$O = $this->Model->go($this->getRequest()->getParams());
    	$checkResult = $this->getCheckResult($O->monitorConfig, $O->lastTime, $O->currentTime);
    	if ($checkResult) $O->doMonitor($checkResult);
    	
        return FALSE;
    }
	
	/**
	 * 
	 * 检测脚本钩子程序，每次执行该检测脚本均会执行
	 */
	public function plugins($var=array()) {
		
		$maxTime = $this->Model->alarmModel->getCheckMaxTime('weblog.'.$var['app']);
    	/*if ($maxTime['time'] AND $var['currentTime']>$maxTime['time']) {
			Ym_Logger::info('weblog.'.$var['app'].' check time: '.$var['currentTime'].' grant and equal than data time: '.$maxTime['time']);
    	}*/
    	return $maxTime['time']-11;
	}
	
	/**
	 * 
	 * 检测脚本主要程序（只需要编写该方法即可）
	 * 
	 * return array(
    		"time"	=>	$checkTime,
    		"code"	=>	$code,
    		"ip"	=>  $ip,
    		"app"	=>  $app,
    		"msg"	=>	$msg,
    		"logid" => 	$logid,
    		"log" 	=> 	$log
    	);
	 */
	public function getCheckResult($monitorConfig, $lastTime, $currentTime) {
		
		if (empty($monitorConfig)) Ym_CommonTool::myoutput(FALSE, 'get monitor data error or empty!');
		
		$id    = isset($monitorConfig['monitor_app']) ? $monitorConfig['monitor_app'] : '';
		$ip    = isset($monitorConfig['monitor_ip']) ? $monitorConfig['monitor_ip'] : '';
		$app   = isset($monitorConfig['app_name']) ? $monitorConfig['app_name'] : 'system';	
		$service   = isset($monitorConfig['monitor_service']) ? $monitorConfig['monitor_service'] : '';	
		$param = isset($monitorConfig['monitor_param']) ? explode("\n", $monitorConfig['monitor_param']) : '';
		
		
		$maxTime = $this->plugins(array('currentTime'=> $currentTime,'app'=>$app));
		$checkTime = strtotime(date('Y-m-d H:i',$maxTime));
		if ($checkTime<=$lastTime) {
			Ym_Logger::info('*weblog.'.$service.' checktime: '.$checkTime.' <= Last: '.$lastTime.', pass!*');
			$checkTime = strtotime(date('Y-m-d H:i',$currentTime));
		}
		
		$model = new Model_Alarm_Monitorscript;
		
    	$code = 0;
    	$msg = array();
    	if (count($param)>0) {
    		
    		foreach ($param as $v) {
    			
    			$arr = explode('=', $v);
    			$key = isset($arr[0]) ? $arr[0] : 'error';
    			$alarm = isset($arr[1]) ? $arr[1] : 100;
    			$return = $model->{__FUNCTION__}($key, $id, $lastTime, $checkTime);
    			
    			if (isset($return['num']) AND $return['num']>=$alarm) {
    				
    				$msg[] = array(
    							"key" => $key,
    							"num" => $return['num'],
    							"alarm" => $alarm,
    							"logid" => $return['content_id'],
    							"log"	=> $return['sample']
    					);
    				$code = 2;
    				
    			} else {
    				
    				$num = (isset($return['num']) AND $return['num']) ? $return['num'] : 0;
    				$msg[] = array(
    					"key"=>$key,
    					"num"=>$num,
    					"alarm"=>$alarm
    				);
    			}
    		}
    	}
    	
    	$status_detail = '';
    	$logid = 0;
    	$log = '';
		foreach ($msg as $msgd) {
						
			$logid .= isset($msgd['logid']) ? $msgd['logid'].'<br/>' : '';
			
			$log    .= isset($msgd['log']) ? str_replace($msgd['key'], '<font color="red">'.$msgd['key'].'</font>', $msgd['log']).'<br/>' : '';
			
			if ($logid) $color = 'red'; else $color = 'green';
			$status_detail .= 'Key:'.$msgd['key'];
			$status_detail .= ' Num:<font color="'.$color.'">'.$msgd['num'].'</font>';
			$status_detail .= ' Alarm:'.$msgd['alarm'];
			$status_detail .= '<br/>';
		}
		
		$thealarm  = 'Time:'.date('Y/m/d H:i:s',$checkTime);
		$thealarm .= ' Code:'.$code;
		$thealarm .= ' IP:'.long2ip($ip);
		$thealarm .= ' App:'.$app;
		$thealarm .= ' Msg:"'.rtrim($status_detail,'<br/>').'"';
		
		Ym_Logger::info($thealarm);
    	return array(
    		"time"	=>	$checkTime + 10,
    		"code"	=>	$code,
    		"ip"	=>  $ip,
    		"app"	=>  $app,
    		"msg"	=>	rtrim($status_detail,'<br/>'),
    		"logid" => 	$logid,
    		"log" 	=> 	$log
    	);
	}
	    
}