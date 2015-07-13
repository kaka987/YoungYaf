<?php
class Model_Alarm_DataNode
{
    public function __construct()
    {
        $this->dao = new Ym_Dao('log');

        $this->monitorApps              = Sys_Database::getTable('apps');
        $this->monitorHosts             = Sys_Database::getTable('hosts');
        $this->monitorServices          = Sys_Database::getTable('services');

    }

    public function getNodeData($hosts='') {

        $returns = array();

        $beginTime = time() - 60;

        $node_str = Ym_Config::getAppItem('monitor:lbs.node');

        $node_arr = $node_str ? explode(',',$node_str) : array(201,203,205,207,209,200,0);
        $nodes = array();
        $delay = 10;
        $len   = 60;

        $end = strtotime(date('Y-m-d H:i', (time() - $delay)));
        $from = $end - $len;
        //echo date('Y-m-d H:i',$from);

        foreach($node_arr as $node) {

            $sql = "select sum(num) as num from log_hps where time>".$from." and time<=".$end." and node=".$node;

            if ($hosts)
                $sql = "select sum(num) as num from log_uhps where time>".$from." and time<=".$end." and node=".$node." and host_id in (".$hosts.")";
            $nums = $this->dao->queryRow($sql, TRUE);
            $nodes[$node] = isset($nums['num']) ? (int)$nums['num'] : 0;
        }

        return $nodes;//array(200=>0,201=>88311,203=>66022,205=>86922,207=>80858);
    }

    public function getDataBySeconds($start,$step,$hosts='') {

        $sql = "select time,sum(num) as num from log_hps where time>=".$start."  and time < ".($start+$step)." group by time";

        if ($hosts)
            $sql = "select time,sum(num) as num from log_uhps where host_id in (".$hosts.") and time>=".$start."  and time < ".($start+$step)." group by time";
        return $this->dao->fetchAll($sql,'', true);
    }

    public function getNumByMinute($time,$hosts='') {

        $sql = "select sum(num) as num from log_hps where time>=".$time."  and time < ".($time+60);

        if ($hosts) {
            $sql = "select sum(num) as num from log_uhps where host_id in (".$hosts.") and time>=".$time."  and time < ".($time+60);
        }

        return $this->dao->queryRow($sql, TRUE);
    }

    public function getHostId($hosts=NULL) {

        $sql = "select id as host_id from relation_host where host in (".$hosts.")";

        return $this->dao->fetchAll($sql,'', true);
    }

    public function getPathId($host,$path=NULL) {

        $sql = "select id as path_id,host_id from relation_path where host='".$host."' and path='".$path."'";

        return $this->dao->queryRow($sql, TRUE);
    }

    public function getIndex($id_start,$id_end,$hosts=NULL,$path=NULL) {

        $to_str =  $id_end ? "id>=".$id_start." and id<=".$id_end  : " id>=".$id_start;

        if (!$hosts) $to_str =  $id_end ? "time>=".$id_start." and time<=".$id_end : " time>=".$id_start;

        $sql = "select sum(num) as num from log_hps where {$to_str}";

        $h = $q = '';
        if (!empty($hosts)) {
            foreach ($hosts as $k=>$host) {
                $h .= "'".$host['host_id']."',";
                $q .= isset($host['path_id']) ? "(host_id = {$host['host_id']} and path_id = {$host['path_id']}) OR " : '';
            }
            $sql = "select sum(num) as num from log_uhps 
            where $to_str
            and host_id in (".rtrim($h,',').")";
        }

        if ($q) {
            $sql = "select sum(num) as num from log_uhps 
            where $to_str 
            and (".rtrim($q,'OR ').")";
        }
        //echo $sql;

        return $this->dao->queryRow($sql, TRUE);
    }

    public function getIndexError($id_start,$id_end,$hosts='',$path=NULL) {

        $to_str =  $id_end ? "id>=".$id_start." and id<=".$id_end  : " id>=".$id_start;

        $sql = "select sum(num) as num from log_uhps where $to_str and code in (500,502,504)";

        $h = $q = '';
        if (!empty($hosts)) {
            foreach ($hosts as $k=>$host) {
                $h .= "'".$host['host_id']."',";
                $q .= isset($host['path_id']) ? "(host_id = {$host['host_id']} and path_id = {$host['path_id']}) OR " : '';
            }
            $sql = "select sum(num) as num from log_uhps 
            where code in (500,502,504)  and $to_str
            and host_id in (".rtrim($h,',').")";
        }

        if ($q) {
            $sql = "select sum(num) as num from log_uhps 
            where code in (500,502,504)  and $to_str
            and (".rtrim($q,'OR ').")";
        }
        //echo $sql;

        return $this->dao->queryRow($sql, TRUE);
    }

    public function getTimeId($time) {

        $sql = "select id from log_uhps where time>=".$time." limit 1";

        return $this->dao->queryRow($sql, TRUE);
    }

    public function getIndexPeak($id_start, $id_end, $hosts='', $flag='max') {
        
        $to_str =  $id_end ? "id>=".$id_start." and id<=".$id_end  : " id>=".$id_start;

        $h = $q = '';
        if (!empty($hosts)) {
            foreach ($hosts as $k=>$host) {
                $h .= "'".$host['host_id']."',";
                $q .= isset($host['path_id']) ? "(host_id = {$host['host_id']} and path_id = {$host['path_id']}) OR " : '';
            }
            
            $sql = "select sum(num) as num,time from log_uhps 
            where $to_str
            and host_id in (".rtrim($h,',').")";
        }
                
        if ($q) {
            $sql = "select sum(num) as num,time from log_uhps 
            where $to_str 
            and (".rtrim($q,'OR ').")";
        }

        if ($flag=='max') 
            $sql .= " group by time order by num desc limit 1";
        else 
            $sql .= " group by time order by num limit 1";
        //echo $sql;

        return $this->dao->queryRow($sql, TRUE);

    }

    public function getIndexFromCached($key) {

        $sql = "select value from monitor_cached where `key`='".$key."'";
//        echo $sql;exit;
        return $this->dao->queryRow($sql, TRUE);
    }

    public function getHostName($hosts=NULL) {

        $sql = "select host from relation_host where id = ".$hosts;

        return $this->dao->queryRow($sql, true);
    }


    public function getClickORConvByMinute($time,$hosts='',$type='click') {
            
        $table = 'log_click';
        $sql = "select sum(num) as num from {$table} where time>=".$time."  and time < ".($time+60);
        if ($type=='conv') {
            $table = 'log_conv';
            $sql = "select sum(num) as num from {$table} where in_time>=".$time."  and in_time< ".($time+60);
        }

        if ($hosts) {
            $sql = "select sum(num) as num from {$table} where host_id in (".$hosts.") and in_time>=".$time."  and in_time< ".($time+60);
        }

        return $this->dao->queryRow($sql, TRUE);
    }

    public function getClickDataBySeconds($start,$step,$hosts='') {

        $sql = "select time,sum(num) as num from log_conv where time>=".$start."  and time < ".($start+$step)." group by time";

        if ($hosts)
            $sql = "select time,sum(num) as num from log_conv where host_id in (".$hosts.") and time>=".$start."  and time < ".($start+$step)." group by time";
        return $this->dao->fetchAll($sql,'', true);
    }

    public function getClickNum($time,$to) {

        $sql = "select sum(num) as num from log_click where time<=".$to." and time>=".$time;
        return $this->dao->queryRow($sql, TRUE);
    }

    public function getConvNum($time,$to) {

        $sql = "select sum(num) as num from log_conv where time<=".$to." and time>=".$time;
        return $this->dao->queryRow($sql, TRUE);
    }

    public function getHourClick($type="click") {

        $returns = array();
        $nodes = range(1,24);
        $start = strtotime(date('Y-m-d', time()));

        for($i=1;$i<=24;$i++,$start+=3600) {
            $sql = "select sum(num) as num from log_{$type} where time>".$start." and time<=".($start+3600);
            $nums = $this->dao->queryRow($sql, TRUE);
            $nodes[$i] = isset($nums['num']) ? (int)$nums['num'] : 0;
        }

        unset($nodes[0]);
        return array_values($nodes);
    }
} 
