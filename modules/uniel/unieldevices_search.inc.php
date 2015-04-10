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
  //searching 'TITLE' (varchar)
  global $title;
  if ($title!='') {
   $qry.=" AND TITLE LIKE '%".DBSafe($title)."%'";
   $out['TITLE']=$title;
  }
  // QUERY READY
  global $save_qry;
  if ($save_qry) {
   $qry=$session->data['unieldevices_qry'];
  } else {
   $session->data['unieldevices_qry']=$qry;
  }
  if (!$qry) $qry="1";
  // FIELDS ORDER
  global $sortby_unieldevices;
  if (!$sortby_unieldevices) {
   $sortby_unieldevices=$session->data['unieldevices_sort'];
  } else {
   if ($session->data['unieldevices_sort']==$sortby_unieldevices) {
    if (Is_Integer(strpos($sortby_unieldevices, ' DESC'))) {
     $sortby_unieldevices=str_replace(' DESC', '', $sortby_unieldevices);
    } else {
     $sortby_unieldevices=$sortby_unieldevices." DESC";
    }
   }
   $session->data['unieldevices_sort']=$sortby_unieldevices;
  }
  if (!$sortby_unieldevices) $sortby_unieldevices="TITLE";
  $out['SORTBY']=$sortby_unieldevices;
  // SEARCH RESULTS
  $res=SQLSelect("SELECT * FROM unieldevices WHERE $qry ORDER BY ".$sortby_unieldevices);
  if ($res[0]['ID']) {
   colorizeArray($res);
   $total=count($res);
   for($i=0;$i<$total;$i++) {
    // some action for every record if required
   }
   $out['RESULT']=$res;
  }
