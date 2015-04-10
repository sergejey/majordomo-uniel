<?php
/*
* @version 0.1 (wizard)
*/
  if ($this->owner->name=='panel') {
   $out['CONTROLPANEL']=1;
  }
  $table_name='unieldevices';
  $rec=SQLSelectOne("SELECT * FROM $table_name WHERE ID='$id'");
  if ($this->mode=='update') {
   $ok=1;
  // step: default
  if ($this->tab=='') {
  //updating 'TITLE' (varchar, required)
   global $title;
   $rec['TITLE']=$title;
   if ($rec['TITLE']=='') {
    $out['ERR_TITLE']=1;
    $ok=0;
   }
  //updating 'TYPE' (select)
   global $type;
   $rec['TYPE']=$type;
   if (!$rec['TYPE']) {
    $out['ERR_TYPE']=1;
    $ok=0;
   }


  //updating 'CONNECTION_TYPE' (int)
   global $connection_type;
   $rec['CONNECTION_TYPE']=(int)$connection_type;
  //updating 'PORT' (int)
   global $port;
   $rec['PORT']=(int)$port;
   if (!$rec['PORT']) {
    $out['ERR_PORT']=1;
    $ok=0;
   }

  //updating 'IP' (varchar)
   global $ip;
   $rec['IP']=$ip;
  //updating 'ADDRESS' (int)
   global $address;
   $rec['ADDRESS']=(int)$address;

   if (!$rec['ADDRESS']) {
    $rec['ADDRESS']=1;
   }

  //updating 'UPDATE_PERIOD' (int)
   global $update_period;
   $rec['UPDATE_PERIOD']=(int)$update_period;

   if ($rec['UPDATE_PERIOD']) {
    $rec['NEXT_UPDATE']=date('Y-m-d H:i:s', time()+$rec['UPDATE_PERIOD']);
   }

  }
  // step: config
  if ($this->tab=='config') {
  //updating 'CONFIG' (text)
   //global $config;
   $config=array();
   $rec['CONFIG']=serialize($config);
  }
  //UPDATING RECORD
   if ($ok) {
    if ($rec['ID']) {
     SQLUpdate($table_name, $rec); // update
    } else {
     $new_rec=1;
     $rec['ID']=SQLInsert($table_name, $rec); // adding new record

     $total=8;
     for($i=0;$i<$total;$i++) {
      $prop=array();
      $prop['DEVICE_ID']=$rec['ID'];
      $prop['NUM']=$i;
      $prop['TYPE']=0;
      SQLInsert('unielproperties', $prop);

      unset($prop['ID']);
      $prop['TYPE']=1;
      SQLInsert('unielproperties', $prop);
     }

    }
    $out['OK']=1;
   } else {
    $out['ERR']=1;
   }
  }
  // step: default
  if ($this->tab=='') {
  //options for 'TYPE' (select)
  $tmp=explode('|', DEF_TYPE_OPTIONS);
  foreach($tmp as $v) {
   if (preg_match('/(.+)=(.+)/', $v, $matches)) {
    $value=$matches[1];
    $title=$matches[2];
   } else {
    $value=$v;
    $title=$v;
   }
   $out['TYPE_OPTIONS'][]=array('VALUE'=>$value, 'TITLE'=>$title);
   $type_opt[$value]=$title;
  }
  for($i=0;$i<count($out['TYPE_OPTIONS']);$i++) {
   if ($out['TYPE_OPTIONS'][$i]['VALUE']==$rec['TYPE']) {
    $out['TYPE_OPTIONS'][$i]['SELECTED']=1;
    $out['TYPE']=$out['TYPE_OPTIONS'][$i]['TITLE'];
    $rec['TYPE']=$out['TYPE_OPTIONS'][$i]['TITLE'];
   }
  }
  }
  // step: config
  if ($this->tab=='config') {
  }
  if (is_array($rec)) {
   foreach($rec as $k=>$v) {
    if (!is_array($v)) {
     $rec[$k]=htmlspecialchars($v);
    }
   }
  }
  outHash($rec, $out);

  if ($rec['ID']) {
   $properties=SQLSelect("SELECT * FROM unielproperties WHERE DEVICE_ID='".$rec['ID']."' ORDER BY TYPE, NUM");
   $total=count($properties);
   for($i=0;$i<$total;$i++) {
    if ($this->mode=='update' && $this->tab=='data') {
     global ${"linked_object".$properties[$i]['TYPE'].$properties[$i]['NUM']};
     global ${"linked_property".$properties[$i]['TYPE'].$properties[$i]['NUM']};
     $properties[$i]['LINKED_OBJECT']=${"linked_object".$properties[$i]['TYPE'].$properties[$i]['NUM']};
     $properties[$i]['LINKED_PROPERTY']=${"linked_property".$properties[$i]['TYPE'].$properties[$i]['NUM']};
     SQLUpdate('unielproperties', $properties[$i]);
     if ($properties[$i]['LINKED_OBJECT']) {
      addLinkedProperty($properties[$i]['LINKED_OBJECT'], $properties[$i]['LINKED_PROPERTY'], $this->name);
     }
    }
   }
   $out['PROPERTIES']=$properties;

   if ($rec['CONFIG']) {
    $config=unserialize($rec['CONFIG']);
    foreach($config as $k=>$v) {
     $out['CONFIG_'.$k]=$v;
    }
   }

  }


  if ($this->mode=='set_type') {
   global $pin;
   global $type;

   $output=$this->sendDeviceCommand($rec['ID'], 0x06, array((int)$type, 0x22+(int)$pin, 0x00));
   $out['RESULT']=$this->binaryToString($this->makePayload($output));
   if (preg_match('/^ffff0600/is')) {
    //ok
   }

  }


  if ($this->mode=='set_timeout') {
   global $pin;
   global $timeout;
   $output=$this->sendDeviceCommand($rec['ID'], 0x06, array((int)$timeout, 0x1a+(int)$pin, 0x00));
   $out['RESULT']=$this->binaryToString($this->makePayload($output));
   if (preg_match('/^ffff0600/is')) {
    //ok
   }
  }

  if ($this->mode=='set_fade') {
   global $fade;
   $output=$this->sendDeviceCommand($rec['ID'], 0x06, array((int)$fade, 0x04, 0x00));
   $out['RESULT']=$this->binaryToString($this->makePayload($output));
   if (preg_match('/^ffff0600/is')) {
    //ok
   }
  }


  if ($this->mode=='set_low_threshold_pin') {
   global $pin;
   global $threshold;
   $output=$this->sendDeviceCommand($rec['ID'], 0x06, array((int)$threshold, 0x02+(int)$pin, 0x00));
   $out['RESULT']=$this->binaryToString($this->makePayload($output));
   if (preg_match('/^ffff0600/is')) {
    //ok
   }
  }
  if ($this->mode=='set_high_threshold_pin') {
   global $pin;
   global $threshold;
   $output=$this->sendDeviceCommand($rec['ID'], 0x06, array((int)$threshold, 0x18+(int)$pin, 0x00));
   $out['RESULT']=$this->binaryToString($this->makePayload($output));
   if (preg_match('/^ffff0600/is')) {
    //ok
   }
  }

  if ($this->mode=='set_low_threshold') {
   global $threshold;
   $output=$this->sendDeviceCommand($rec['ID'], 0x06, array((int)$threshold, 0x02, 0x00));
   $out['RESULT']=$this->binaryToString($this->makePayload($output));
   if (preg_match('/^ffff0600/is')) {
    //ok
   }
  }
  if ($this->mode=='set_high_threshold') {
   global $threshold;
   $output=$this->sendDeviceCommand($rec['ID'], 0x06, array((int)$threshold, 0x03, 0x00));
   $out['RESULT']=$this->binaryToString($this->makePayload($output));
   if (preg_match('/^ffff0600/is')) {
    //ok
   }
  }

  if ($this->mode=='send_command') {
   global $command;
   $out['COMMAND']=$command;
   $output=$this->sendDeviceCommand($rec['ID'], $command, 0, 1);
   $out['RESULT']=$this->binaryToString($this->makePayload($output));
  }

  if ($this->mode=='set_address_broadcast') {
   //FF FF 06 FE FE 01 00 03
   $output=$this->sendDeviceCommand($rec['ID'], 0x06, array(0xfe, 0x01, 0x00), 0xfe);
   $out['RESULT']=$this->binaryToString($this->makePayload($output));
  }

  if ($this->mode=='set_address') {
   //FF FF 06 FD 04 01 00 08
   global $address;
   $output=$this->sendDeviceCommand($rec['ID'], 0x06, array((int)$address, 0x01, 0x00), 0xfd);
   $out['RESULT']=$this->binaryToString($this->makePayload($output));
   if (preg_match('/^ffff0600/is')) {
    SQLExec("UPDATE unieldevices SET ADDRESS=".(int)$address." WHERE ID=".(int)$rec['ID']);
   }
  }

  if ($this->mode=='getdata') {
   $this->refreshDevice($rec['ID']);
   $this->redirect("?view_mode=".$this->view_mode."&tab=".$this->tab."&id=".$rec['ID']);
  }
