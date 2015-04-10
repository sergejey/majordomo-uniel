<?php
/*
* @version 0.1 (wizard)
*/
 global $session;
  if ($this->owner->name=='panel') {
   $out['CONTROLPANEL']=1;
  }
  $qry="1";
  // search filters
  if (IsSet($this->device_id)) {
   $device_id=$this->device_id;
   $qry.=" AND DEVICE_ID='".$this->device_id."'";
  } else {
   global $device_id;
  }
  // QUERY READY
  global $save_qry;
  if ($save_qry) {
   $qry=$session->data['unielproperties_qry'];
  } else {
   $session->data['unielproperties_qry']=$qry;
  }
  if (!$qry) $qry="1";
  // FIELDS ORDER
  global $sortby_unielproperties;
  if (!$sortby_unielproperties) {
   $sortby_unielproperties=$session->data['unielproperties_sort'];
  } else {
   if ($session->data['unielproperties_sort']==$sortby_unielproperties) {
    if (Is_Integer(strpos($sortby_unielproperties, ' DESC'))) {
     $sortby_unielproperties=str_replace(' DESC', '', $sortby_unielproperties);
    } else {
     $sortby_unielproperties=$sortby_unielproperties." DESC";
    }
   }
   $session->data['unielproperties_sort']=$sortby_unielproperties;
  }
  if (!$sortby_unielproperties) $sortby_unielproperties="NUM";
  $out['SORTBY']=$sortby_unielproperties;
  // SEARCH RESULTS
  $res=SQLSelect("SELECT * FROM unielproperties WHERE $qry ORDER BY ".$sortby_unielproperties);
  if ($res[0]['ID']) {
   colorizeArray($res);
   $total=count($res);
   for($i=0;$i<$total;$i++) {
    // some action for every record if required
   }
   $out['RESULT']=$res;
  }
