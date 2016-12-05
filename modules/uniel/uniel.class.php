<?php
/**
* Uniel 
*
* Uniel
*
* @package project
* @author Serge J. <jey@tut.by>
* @copyright http://www.atmatic.eu/ (c)
* @version 0.1 (wizard, 12:04:34 [Apr 09, 2015])
*/
Define('DEF_TYPE_OPTIONS', 'automation=Automation|light=Light controller|dimmer=Dimmer controller'); // options for 'TYPE'
//
//
class uniel extends module {
/**
* uniel
*
* Module class constructor
*
* @access private
*/
function uniel() {
  $this->name="uniel";
  $this->title="Uniel RS485";
  $this->module_category="<#LANG_SECTION_DEVICES#>";
  $this->checkInstalled();
}
/**
* saveParams
*
* Saving module parameters
*
* @access public
*/
function saveParams($data=0) {
 $p=array();
 if (IsSet($this->id)) {
  $p["id"]=$this->id;
 }
 if (IsSet($this->view_mode)) {
  $p["view_mode"]=$this->view_mode;
 }
 if (IsSet($this->edit_mode)) {
  $p["edit_mode"]=$this->edit_mode;
 }
 if (IsSet($this->data_source)) {
  $p["data_source"]=$this->data_source;
 }
 if (IsSet($this->tab)) {
  $p["tab"]=$this->tab;
 }
 return parent::saveParams($p);
}
/**
* getParams
*
* Getting module parameters from query string
*
* @access public
*/
function getParams() {
  global $id;
  global $mode;
  global $view_mode;
  global $edit_mode;
  global $data_source;
  global $tab;
  if (isset($id)) {
   $this->id=$id;
  }
  if (isset($mode)) {
   $this->mode=$mode;
  }
  if (isset($view_mode)) {
   $this->view_mode=$view_mode;
  }
  if (isset($edit_mode)) {
   $this->edit_mode=$edit_mode;
  }
  if (isset($data_source)) {
   $this->data_source=$data_source;
  }
  if (isset($tab)) {
   $this->tab=$tab;
  }
}
/**
* Run
*
* Description
*
* @access public
*/
function run() {
 global $session;
  $out=array();
  if ($this->action=='admin') {
   $this->admin($out);
  } else {
   $this->usual($out);
  }
  if (IsSet($this->owner->action)) {
   $out['PARENT_ACTION']=$this->owner->action;
  }
  if (IsSet($this->owner->name)) {
   $out['PARENT_NAME']=$this->owner->name;
  }
  $out['VIEW_MODE']=$this->view_mode;
  $out['EDIT_MODE']=$this->edit_mode;
  $out['MODE']=$this->mode;
  $out['ACTION']=$this->action;
  $out['DATA_SOURCE']=$this->data_source;
  $out['TAB']=$this->tab;
  if (IsSet($this->device_id)) {
   $out['IS_SET_DEVICE_ID']=1;
  }
  if ($this->single_rec) {
   $out['SINGLE_REC']=1;
  }
  $this->data=$out;
  $p=new parser(DIR_TEMPLATES.$this->name."/".$this->name.".html", $this->data, $this);
  $this->result=$p->result;
}
/**
* BackEnd
*
* Module backend
*
* @access public
*/
function admin(&$out) {
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='unieldevices' || $this->data_source=='') {
  if ($this->view_mode=='' || $this->view_mode=='search_unieldevices') {
   $this->search_unieldevices($out);
  }
  if ($this->view_mode=='edit_unieldevices') {
   $this->edit_unieldevices($out, $this->id);
  }
  if ($this->view_mode=='delete_unieldevices') {
   $this->delete_unieldevices($this->id);
   $this->redirect("?data_source=unieldevices");
  }
 }
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='unielproperties') {
  if ($this->view_mode=='' || $this->view_mode=='search_unielproperties') {
   $this->search_unielproperties($out);
  }
 }
}
/**
* FrontEnd
*
* Module frontend
*
* @access public
*/
function usual(&$out) {
 $this->admin($out);
}
/**
* unieldevices search
*
* @access public
*/
 function search_unieldevices(&$out) {
  require(DIR_MODULES.$this->name.'/unieldevices_search.inc.php');
 }
/**
* unieldevices edit/add
*
* @access public
*/
 function edit_unieldevices(&$out, $id) {
  require(DIR_MODULES.$this->name.'/unieldevices_edit.inc.php');
 }

/**
* Title
*
* Description
*
* @access public
*/
 function refreshDevice($id) {
  $rec=SQLSelectOne("SELECT * FROM unieldevices WHERE ID='".$id."'");
  if (!$rec['ID']) {
   return;
  }

  //check inputs
  $inputs=SQLSelect("SELECT * FROM unielproperties WHERE DEVICE_ID='".$rec['ID']."' AND TYPE=0 ORDER BY NUM");
  $total=count($inputs);

  for($i=0;$i<$total;$i++) {
   $result=$this->sendDeviceCommand($rec['ID'], 0x05, array(0x00, 0x0a+(int)$inputs[$i]['NUM'], 0x00));
   if (isset($result[4])) {
    $value=$result[4];
    $old_value=$inputs[$i]['CURRENT_VALUE'];
    $inputs[$i]['CURRENT_VALUE']=$value;
    SQLUpdate('unielproperties', $inputs[$i]);

    if ($inputs[$i]['LINKED_OBJECT'] && $inputs[$i]['LINKED_PROPERTY']) {
     if ($old_value!=$inputs[$i]['CURRENT_VALUE'] || $inputs[$i]['CURRENT_VALUE']!=gg($inputs[$i]['LINKED_OBJECT'].'.'.$inputs[$i]['LINKED_PROPERTY'])) {
      setGlobal($inputs[$i]['LINKED_OBJECT'].'.'.$inputs[$i]['LINKED_PROPERTY'], $inputs[$i]['CURRENT_VALUE'], array($this->name=>'0'));
     }
    }

   }
  }

   $outputs=SQLSelect("SELECT * FROM unielproperties WHERE DEVICE_ID='".$rec['ID']."' AND TYPE=1 ORDER BY NUM");
   $result=$this->sendDeviceCommand($rec['ID'], 0x0b, array(0x00, 0x00, 0x00));
   if (isset($result[4])) {
    //$value=decbin($result[4]);
    //print_r($result);
    //echo $value."<br/>";
    $ret = decbin($result[4]); 
    while(strlen($ret) < 8)
        {
         $ret = "0".$ret;
        }  
    $value = array_reverse(str_split($ret));

    $total=count($outputs);
    for($i=0;$i<$total;$i++) {
     $old_value=$outputs[$i]['CURRENT_VALUE'];
     if ($rec['TYPE']=='dimmer' && $value[(int)$outputs[$i]['NUM']]=='1') {
      $result=$this->sendDeviceCommand($rec['ID'], 0x05, array(0x00, 0x40+(int)$outputs[$i]['NUM'], 0x00));
      if (isset($result[4])) {
       $level=(int)$result[4];
       $outputs[$i]['CURRENT_VALUE']=$level;
      }
     } else {
      $outputs[$i]['CURRENT_VALUE']=$value[$i];//(int)$value[(int)$outputs[$i]['NUM']];
     }
     SQLUpdate('unielproperties', $outputs[$i]);

     if ($outputs[$i]['LINKED_OBJECT'] && $outputs[$i]['LINKED_PROPERTY']) {
      if ($old_value!=$outputs[$i]['CURRENT_VALUE'] || $outputs[$i]['CURRENT_VALUE']!=gg($outputs[$i]['LINKED_OBJECT'].'.'.$outputs[$i]['LINKED_PROPERTY'])) {
      }
      setGlobal($outputs[$i]['LINKED_OBJECT'].'.'.$outputs[$i]['LINKED_PROPERTY'], $outputs[$i]['CURRENT_VALUE'], array($this->name=>'0'));
     }

   }
  }

 }

/**
* unieldevices delete record
*
* @access public
*/
 function delete_unieldevices($id) {
  $rec=SQLSelectOne("SELECT * FROM unieldevices WHERE ID='$id'");
  // some action for related tables
  SQLExec("DELETE FROM unielproperties WHERE DEVICE_ID='".$rec['ID']."'");
  SQLExec("DELETE FROM unieldevices WHERE ID='".$rec['ID']."'");
  
 }

 function propertySetHandle($object, $property, $value) {
   $properties=SQLSelect("SELECT ID FROM unielproperties WHERE LINKED_OBJECT LIKE '".DBSafe($object)."' AND LINKED_PROPERTY LIKE '".DBSafe($property)."' AND TYPE=1");
   $total=count($properties);
   if ($total) {
    for($i=0;$i<$total;$i++) {
     $this->setProperty($properties[$i]['ID'], $value);
    }
   }
 }

 /**
 * Title
 *
 * Description
 *
 * @access public
 */
  function updateDevices() {
   $devices=SQLSelect("SELECT * FROM unieldevices WHERE UPDATE_PERIOD>0 AND NEXT_UPDATE<=NOW()");
   $total=count($devices);
   for($i=0;$i<$total;$i++) {
    $devices[$i]['NEXT_UPDATE']=date('Y-m-d H:i:s', time()+$devices[$i]['UPDATE_PERIOD']);
    $this->refreshDevice($devices[$i]['ID']);
   }
  }


 /**
 * Title
 *
 * Description
 *
 * @access public
 */
  function sendDeviceCommand($device_id, $command, $data, $raw=0) {
   $device=SQLSelectOne("SELECT * FROM unieldevices WHERE ID='".$device_id."'");

   if ($raw==1) {
    //raw
    $ar=$this->HexStringToArray(str_replace(' ', '', $command));
   } else {
    $ar=array();
    $ar[]=0xff;
    $ar[]=0xff;
    $ar[]=$command;
    if ($raw>1) {
     $ar[]=$raw;
    } else {
     $ar[]=(int)$device['ADDRESS'];
    }
    $ar[]=$data[0];
    $ar[]=$data[1];
    $ar[]=$data[2];

    $cs=$ar[2]+$ar[3]+$ar[4]+$ar[5]+$ar[6];
    if ($cs>255) {
     $high_byte=floor($cs/256);
     $low_byte=$cs-$high_byte*256;
     $cs=$low_byte;
    }
    $ar[]=$cs;
   }
   $payload=$this->makePayload($ar);

   //echo "Sending ".$this->binaryToString($payload)."<br/>";exit;
   if ($device['CONNECTION_TYPE']==0) {
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if ($socket === false) {
     echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "<br/>\n";
     return 0;
    }
    $result = socket_connect($socket, $device['IP'], $device['PORT']);
    if ($result === false) {
     echo "socket_connect() failed.\nReason: ($result) " . socket_strerror(socket_last_error($socket)) . "\n";
     return 0;
    }
    $len=socket_write($socket, $payload, 8);
    $out = socket_read($socket, 8, PHP_BINARY_READ);
    //echo "Reply ".$this->binaryToString($out)."<br/>";
    return ($this->HexStringToArray($this->binaryToString($out)));
   } elseif ($device['CONNECTION_TYPE']==1) {
    //serial communication is not supported:
    //http://www.lspace.nildram.co.uk/freeware.html
   }

  }


/**
* Title
*
* Description
*
* @access public
*/
 function setProperty($property_id, $value) {
  $prop=SQLSelectOne("SELECT * FROM unielproperties WHERE ID='".$property_id."'");
  $prop['CURRENT_VALUE']=$value;
  SQLUpdate('unielproperties', $prop);

  $channel=$prop['NUM'];
  $device=SQLSelectOne("SELECT TYPE FROM unieldevices WHERE ID='".$prop['DEVICE_ID']."'");

  if ($device['TYPE']=='light') {
   if ($value>0) {
    //FF FF 06 01 FF 12 00 18
    $this->sendDeviceCommand($prop['DEVICE_ID'], 0x06, array(0xff, 0x12+(int)$prop['NUM'], 0x00));
   } else {
    //FF FF 06 01 00 12 00 19
    $this->sendDeviceCommand($prop['DEVICE_ID'], 0x06, array(0x00, 0x12+(int)$prop['NUM'], 0x00));
   }
   } elseif ($device['TYPE']=='automation') {
   if ($value>0) {
    //FF FF 06 01 FF 1a 00 18
    $this->sendDeviceCommand($prop['DEVICE_ID'], 0x06, array(0xff, 0x1a+(int)$prop['NUM'], 0x00));
   } else {
    //FF FF 06 01 00 1a 00 19
    $this->sendDeviceCommand($prop['DEVICE_ID'], 0x06, array(0x00, 0x1a+(int)$prop['NUM'], 0x00));
   }
   } elseif ($device['TYPE']=='dimmer') {

   //FF FF 0A 01 11 00 00 1C
   $this->sendDeviceCommand($prop['DEVICE_ID'], 0x0a, array((int)$value, 0x00+(int)$prop['NUM'], 0x00));
  }
 }


/**
* unielproperties search
*
* @access public
*/
 function search_unielproperties(&$out) {
  require(DIR_MODULES.$this->name.'/unielproperties_search.inc.php');
 }

function makePayload($data) {
  $res='';
  foreach($data as $v) {
   $res.=chr($v);
  }
  return $res;
}

function HexStringToArray($buf) {
   $res=array();
   for($i=0;$i<strlen($buf)-1;$i+=2) {
    $res[]=(hexdec($buf[$i].$buf[$i+1]));
   }
   return $res;   
}

function HexStringToString($buf) {
   $res='';
   for($i=0;$i<strlen($buf)-1;$i+=2) {
    $res.=chr(hexdec($buf[$i].$buf[$i+1]));
   }
   return $res;   
}


function binaryToString($buf) {
   $res='';
   for($i=0;$i<strlen($buf);$i++) {
    $num=dechex(ord($buf[$i]));
    if (strlen($num)==1) {
     $num='0'.$num;
    }
    $res.=$num;
   }
   return $res;
}


/**
* Install
*
* Module installation routine
*
* @access private
*/
 function install($data='') {
  parent::install();
 }
/**
* Uninstall
*
* Module uninstall routine
*
* @access public
*/
 function uninstall() {
  SQLExec('DROP TABLE IF EXISTS unieldevices');
  SQLExec('DROP TABLE IF EXISTS unielproperties');
  parent::uninstall();
 }
/**
* dbInstall
*
* Database installation routine
*
* @access private
*/
 function dbInstall() {
/*
unieldevices - Uniel Devices
unielproperties - Uniel Properties
*/
  $data = <<<EOD
 unieldevices: ID int(10) unsigned NOT NULL auto_increment
 unieldevices: TITLE varchar(255) NOT NULL DEFAULT ''
 unieldevices: TYPE varchar(255) NOT NULL DEFAULT ''
 unieldevices: CONNECTION_TYPE int(3) NOT NULL DEFAULT '0'
 unieldevices: PORT int(10) NOT NULL DEFAULT '0'
 unieldevices: IP varchar(255) NOT NULL DEFAULT ''
 unieldevices: ADDRESS int(3) NOT NULL DEFAULT '0'
 unieldevices: UPDATE_PERIOD int(10) NOT NULL DEFAULT '0'
 unieldevices: NEXT_UPDATE datetime
 unieldevices: CONFIG text
 unielproperties: ID int(10) unsigned NOT NULL auto_increment
 unielproperties: DEVICE_ID int(10) NOT NULL DEFAULT '0'
 unielproperties: TYPE int(3) NOT NULL DEFAULT '0'
 unielproperties: NUM int(3) NOT NULL DEFAULT '0'
 unielproperties: CURRENT_VALUE int(3) NOT NULL DEFAULT '0'
 unielproperties: LINKED_OBJECT varchar(255) NOT NULL DEFAULT ''
 unielproperties: LINKED_PROPERTY varchar(255) NOT NULL DEFAULT ''
 unielproperties: UPDATED datetime
EOD;
  parent::dbInstall($data);
 }
// --------------------------------------------------------------------
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgQXByIDA5LCAyMDE1IHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
