<?php

//clean for url
function _clean_url($text){
  return sanitize_title($text); 
}
  


//clean_page_type
function _clean_page_type($page_type,$plural='no'){
  
  if($page_type=='private-colleges'){$page_type='4 [dash] year-private-colleges';}
  if($page_type=='public-colleges'){$page_type='4 [dash] year-public-colleges';}
  if($page_type=='fashion-art-schools'){$page_type='fashion-or-art-schools';}
  if($page_type=='healthcare-nursing-schools'){$page_type='healthcare-or-nursing-schools';}
  
if ($plural === 'no') {
    $strReplace = str_replace(
        array("-", "schools", "colleges", "seminaries", " [dash] "),
        array(" ", "school", "college", "seminary", "-"),
        $page_type
    );

    return _proper($strReplace);
}
  
  //plural
  return _proper(str_replace(array("-"," [dash] "),array(" ","-"),$page_type));
}


//get aos from page_id
function _get_aos_from_degree_id($degree_id){

    $results=$GLOBALS['database']->query_first("SELECT `value` as aos,left(value_id,2) as aos_id from `field_dictionary_values` where `field_name` = 'cipcode' and `value_id` = '".substr($degree_id,0,2)."'");
    
   return $results;
}



//virtual tour
function _check_virtual_tour($name,$webaddr){

  global $wpdb;

if(strlen($webaddr)>5){
 $data=$wpdb->get_results("SELECT * FROM `youvisit_ids` where 
 domain like '%//www."._format_url($webaddr,'yes')."%' or
 domain like '%//"._format_url($webaddr,'yes')."%' or
 domain like '%//ww%."._format_url($webaddr,'yes')."%' or 
 `school_name` = '%{$name}%'  or `school_name` = '{$name}'",ARRAY_A);
 


}else{
 $data=$wpdb->get_results("SELECT * FROM `youvisit_ids` where `school_name` = '%{$name}%'  or `school_name` = '{$name}'",ARRAY_A);
}

 
 if(isset($data[0]['youvisit_id'])){
  if(strlen($data[0]['youvisit_id'])>1){
  
    //update domain if domain not found
    if(@strlen($data[0]['domain'])<4){
      $wpdb->query("update `youvisit_ids` set `domain` = 'http://"._format_url($webaddr,'yes')."' where youvisit_id = '{$data[0]['youvisit_id']}'");
    }
   
    
    return $data[0]['youvisit_id']; 
  }
 }
 
 return;
 
}



//prepare alias
function _prepare_alias($entity_id,$alias,$add_parenthesis='yes',$number_of_abbreviation=1){
  
  global $wpdb;
  
  //first check for alias in college_alias table
  $get_alias=$wpdb->get_results("SELECT `ialias` FROM `college_alias` WHERE `entity_id` = '{$entity_id}'",ARRAY_A);
  
  if(count($get_alias)>0){
   $alias=$get_alias[0]['ialias'];
  }
       
                              
  if(strlen($alias)>0){
    
    $alias=explode("|",$alias);
  
  //no alias found
  }else{
    return;  
  }
          
 //print_r($alias_array);   
  //explode alias
  $alias_value='';
  $length=30;
                       
                              
  //check ialias for non-unique alias exists for larger college (10,000+ enrollment)
  if($number_of_abbreviation==1 && strlen($alias[0])>0){

  
      //check if ialias is unique 
      $check_if_ialias_is_unique=$wpdb->get_results("SELECT `entity_id` FROM `"._get_latest_year_table('institutional_characteristics_directory_information')."` WHERE 
      (`ialias` = '".$alias[0]."' or `ialias` like '".$alias[0]."|%') and `instsize` > 3 and `entity_id` != '{$entity_id}'",ARRAY_A);
  
     
      if(count($check_if_ialias_is_unique)>0){return;}
 
  }
  
  //loop through each alias
  $i=0;
  foreach($alias as $key => $value){
    //limit alias to 25 chars
    
    if(strlen($value.$alias_value)<=$length){
      //add comma
      if($key>0 && $value!='' && $alias_value!='' && $value!='N/A'){$alias_value.=', ';}
      
      if($value!='N/A'){$alias_value.=rtrim($value);}
      
      ++$i;
      
      if($i==$number_of_abbreviation){break;}
      if(strlen($value.$alias_value)>$length){break;}
    }
    
  }
  
  if($add_parenthesis=='yes' && $alias_value!=''){return ' ('.$alias_value.')';};
  return $alias_value;
}
//end

function _create_link_sitemap_pages($display='link',$page_type,$id,$anchor_or_sub_id='',$class='',$anchor_link=''){
    
    global $wpdb;

    if($page_type=='college-degree'){      
    
     //get college page_id
     $get_college_data=$GLOBALS['database']->query_first("select post_name as page_id, meta_value as page_type,instsize from `mc_posts` p 
                  left join `"._get_latest_year_table('institutional_characteristics_directory_information')."` icdi on icdi.entity_id = p.post_parent
                  left join `mc_postmeta` pm on pm.post_id = p.ID 
                  where p.post_parent = '{$id}' and meta_key = 'college_type'");
                          
               
       if(isset($get_college_data['page_id'])){
          //prepare url safe variable            
           
           //get degree info
	           $get_degree_data=$GLOBALS['database']->fetch_all_array("SELECT p.post_title as `degree_name`, p.post_name as `page_id`,pm.meta_value from `mc_posts` p
	                                                        left join `mc_postmeta` pm on p.ID = pm.post_id
	                                                        where `meta_key` = 'degree_id' and meta_value = '{$anchor_or_sub_id}' limit 1");
                                                         
             if($get_degree_data['number_results']>0){
             
             
                   //verify that college-degree page really exists       
                   $check_college_degree_exists=$GLOBALS['database']->fetch_all_array("SELECT `cipcode` FROM `"._get_latest_year_table('completions_degrees_by_program')."` WHERE `cipcode` = '{$anchor_or_sub_id}' AND `majornum` = '1' and `entity_id` = '{$id}'");                  
           
                   //check if ", other or .9999" degree type
                    if($check_college_degree_exists['number_results']==0){
                      $check_college_degree_exists=$GLOBALS['database']->fetch_all_array("SELECT `cipcode`,(SELECT value FROM `field_dictionary_values` WHERE `field_name` = 'cipcode' AND `value_id` = cipcode) AS `degree_name` FROM `"._get_latest_year_table('completions_degrees_by_program')."` WHERE  `cipcode` NOT LIKE '99.%' and `cipcode` != '99' and `majornum` = '1' and `entity_id` = '{$id}' having degree_name = '{$get_degree_data['data'][0]['degree_name']}'");
                    }    
                      
              
               //now get degree_id if page_type = public, private, community with inst size > 1
               if(($get_college_data['page_type']=='public-colleges' || $get_college_data['page_type']=='private-colleges' || $get_college_data['page_type']=='community-colleges' || $get_college_data['page_type']=='alternative-medicine-schools' || $get_college_data['page_type']=='career-colleges' || $get_college_data['page_type']=='cosmetology-schools' || $get_college_data['page_type']=='graduate-schools' || $get_college_data['page_type']=='healthcare-nursing-schools' || $get_college_data['page_type']=='massage-schools') && $check_college_degree_exists['number_results']>0){
                           
                  echo 'messing here';
                   //url
                   $url=$GLOBALS['domain'].'/'.$get_college_data['page_type'].'/'.$get_college_data['page_id'].'/'.$get_degree_data['data'][0]['page_id'];    
                
               //not one of special schools that have college degree pages so make link "schools" link
               }else{
                  echo 'its already messing here';
                  return _create_link($display,'schools',$anchor_or_sub_id);
               
               }
        
           //not found in degree table so create search link
           }else{            
            return _create_link($display,'schools',$anchor_or_sub_id);
           }
          
          
          //create anchor text
          $anchor_or_sub_id = $GLOBALS['database']->fetch_all_array("SELECT * FROM `"._get_latest_year_table('completions_degrees_by_program_list')."` WHERE `cipcode` = '".$get_degree_data['data'][0]['meta_value']."' AND `entity_id` = '{$id}' LIMIT 1");        
          if($anchor_or_sub_id['data'][0]['degree_name'] != '')
          {
            $anchor_or_sub_id=$anchor_or_sub_id['data'][0]['degree_name'];
          }
          else
          {
            $anchor_or_sub_id=$get_degree_data['data'][0]['degree_name'];
          }
          
        }else{$url=$GLOBALS['domain'];}
    
      
    }
    
    //return only url if $display='url'
    if($display=='url'){return 'the url is' . $url;}
    
    if($class!=''){      
      return 'the anchor is'.'<a href="'.$url.'" class="'.$class.'">'.$anchor_or_sub_id.'</a>';
    }    
    //create link
    return 'another anchor is'.'<a href="'.$url.'">'.$anchor_or_sub_id.'</a>';
}


//master_link_creator
function _create_link($display='link',$page_type,$id,$anchor_or_sub_id='',$class='',$anchor_link=''){
    
    global $wpdb;
    

    //aos page
    if($page_type=='aos'){
      //  
     $get_data=$GLOBALS['database']->fetch_all_array("SELECT `post_name`,`post_title` from `mc_posts` where `post_type` ='".$page_type."' and post_name = '{$id}'");
     $url=$GLOBALS['home_url'].'/'.$page_type.'/'.$get_data['data']['0']['aos_page_id'];
     if($anchor_or_sub_id==''){$anchor_or_sub_id=$get_data['data']['0']['aos'];}    
    }    

    //state page
    if($page_type=='state'){
     $id=strtolower(str_replace(" ","-",$id));
     $url=$GLOBALS['domain'].'/'.$page_type.'/'.$id;
     if($anchor_or_sub_id==''){$anchor_or_sub_id=$id;}    
    }
    
    if($page_type=='city'){
     $id=strtolower(str_replace(" ","-",$id));
     $url=$GLOBALS['domain'].'/'.$page_type.'/'.$id.'-'.strtolower($anchor_or_sub_id);
     if($anchor_or_sub_id==''){$anchor_or_sub_id=$id;}    
    }

        //schools page
    if($page_type=='schools'){
      
    //try degree_information table first
      $get_data=$GLOBALS['database']->fetch_all_array("SELECT post_title as `degree_name`, post_name as `page_id` from `mc_posts` p
                                                        left join `mc_postmeta` pm on p.ID = pm.post_id
                                                        where `meta_key` = 'degree_id' and p.post_status = 'publish' and meta_value = '{$id}' limit 1");

	    $get_degree_data=$GLOBALS['database']->fetch_all_array("SELECT p.post_title as `degree_name`, p.post_name as `page_id`,pm.meta_value from `mc_posts` p
                                                        left join `mc_postmeta` pm on p.ID = pm.post_id
                                                        where `meta_key` = 'degree_id' and meta_value = '{$anchor_or_sub_id}' limit 1");

      if($get_data['number_results']>0){
        
         $url=$GLOBALS['domain'].'/'.$page_type.'/'.$get_data['data'][0]['page_id'];

         if($anchor_or_sub_id==''){$anchor_or_sub_id=$get_data['data'][0]['degree_name'];}

      }
      
      //no degree page available
      if($get_data['number_results']==0){
        $get_data=$GLOBALS['database']->fetch_all_array("SELECT value as `degree_name`,(SELECT `value` FROM `field_dictionary_values` WHERE `field_name` = 'cipcode' AND `value_id` = '".substr($id,0,2)."') AS `degree_category` FROM `field_dictionary_values` WHERE `field_name` = 'cipcode' AND `value_id` = '$id'");
        
          $url='http://search.matchcollege.com/search#&subject='.$get_data['data'][0]['degree_category'].'&degree_id='.$id.'&'.$anchor_link.'&distance_away=10000';      
          
          if($anchor_or_sub_id==''){$anchor_or_sub_id=$get_data['data'][0]['degree_name'];}  
      }

    
    
      
    }elseif($page_type=='college'){
      //run mysql database to grab link             


	    $get_data=$GLOBALS['database']->fetch_all_array("select ID,post_name,post_title from `mc_posts` where post_parent = '{$id}' and post_status = 'publish' ");
             
      
               
       if(isset($get_data['data'][0]['ID'])){
          //prepare url safe variable

          //get college_page_id
          $get_college_page_id=$GLOBALS['database']->fetch_all_array("select meta_value as college_type from `mc_postmeta` where post_id = '{$get_data['data'][0]['ID']}' and `meta_key` = 'college_type'");
              
         
           //url
           $url=$GLOBALS['home_url'].'/'.$get_college_page_id['data'][0]['college_type'].'/'.$get_data['data'][0]['post_name'];
          
          //create default anchor if anchor does not exists
          if($anchor_or_sub_id==''){$anchor_or_sub_id=$get_data['data'][0]['post_title'];}
        }else{$url=$GLOBALS['home_url'];}
    } elseif ($page_type == 'colleges-online-degrees') {

        //run mysql database to grab link
        $get_data = $GLOBALS['database']->fetch_all_array("select ID,post_name,post_title from `mc_posts` where post_parent = '{$id}'");

        if (isset($get_data['data'][0]['ID'])) {
            //prepare url safe variable
            //get college_page_id
            $get_college_page_id = $GLOBALS['database']->fetch_all_array("select meta_value as college_type from `mc_postmeta` where post_id = '{$get_data['data'][0]['ID']}' and `meta_key` = 'college_type'");

            //url
            $url = $GLOBALS['home_url'] . '/' . $page_type . '/' . strtolower(str_replace(" ", "-", $anchor_or_sub_id)) . '/' . $get_college_page_id['data'][0]['college_type'];
        } else {
            $url = $GLOBALS['home_url'];
        }
    } elseif ($page_type == 'colleges-online-degrees-career') {
	    echo "colleges-online-degrees-career";
	    exit;
    	//run mysql database to grab link
        $get_data = $GLOBALS['database']->fetch_all_array("SELECT post_title as `degree_name`, post_name as `page_id` from `mc_posts` p
                                                        left join `mc_postmeta` pm on p.ID = pm.post_id
                                                        where `meta_key` = 'degree_id' and meta_value = '{$id}' limit 1");
        if ($get_data['number_results'] > 0) {
            $url = $GLOBALS['home_url'] . '/colleges-online-degrees/state/' . strtolower(str_replace(" ", "-", $anchor_or_sub_id)) . '/' . $get_data['data'][0]['page_id'];
        } else {
            return _create_link($display, 'schools', $id);
        }
    } elseif($page_type == 'search') {
          //prepare url safe variable
          $url_encoded_variable=_convert_text(strtolower($id),"yes");
          $url=$GLOBALS['domain'].'/'.$page_type.'/'.$url_encoded_variable;
          //create default anchor if anchor does not exists
          if($anchor_or_sub_id==''){$anchor_or_sub_id=_proper($id);}
          
                  
    }elseif($page_type=='college-degree'){

     //get college page_id
     $get_college_data=$GLOBALS['database']->query_first("select post_name as page_id, meta_value as page_type,instsize from `mc_posts` p 
                  left join `"._get_latest_year_table('institutional_characteristics_directory_information')."` icdi on icdi.entity_id = p.post_parent
                  left join `mc_postmeta` pm on pm.post_id = p.ID 
                  where p.post_parent = '{$id}' and meta_key = 'college_type'");            
                          
               
       if(isset($get_college_data['page_id'])){             
           
           //get degree info
           $get_degree_data=$GLOBALS['database']->fetch_all_array("SELECT p.post_title as `degree_name`, p.post_name as `page_id`,pm.meta_value from `mc_posts` p
                                                        left join `mc_postmeta` pm on p.ID = pm.post_id
                                                        where `meta_key` = 'degree_id' and meta_value = '{$anchor_or_sub_id}' limit 1");           
                                                         
             if($get_degree_data['number_results']>0){
             
             
                   //verify that college-degree page really exists       
                   $check_college_degree_exists=$GLOBALS['database']->fetch_all_array("SELECT `cipcode` FROM `"._get_latest_year_table('completions_degrees_by_program')."` WHERE `cipcode` = '{$anchor_or_sub_id}' AND `majornum` = '1' and `entity_id` = '{$id}'");                   
           
                   //check if ", other or .9999" degree type
                    if($check_college_degree_exists['number_results']==0){
                      $check_college_degree_exists=$GLOBALS['database']->fetch_all_array("SELECT `cipcode`,(SELECT value FROM `field_dictionary_values` WHERE `field_name` = 'cipcode' AND `value_id` = cipcode) AS `degree_name` FROM `"._get_latest_year_table('completions_degrees_by_program')."` WHERE  `cipcode` NOT LIKE '99.%' and `cipcode` != '99' and `majornum` = '1' and `entity_id` = '{$id}' having degree_name = '{$get_degree_data['data'][0]['degree_name']}'");
                    }             
                      
              
               //now get degree_id if page_type = public, private, community with inst size > 1
               if(($get_college_data['page_type']=='public-colleges' || $get_college_data['page_type']=='private-colleges' || $get_college_data['page_type']=='community-colleges' || $get_college_data['page_type']=='alternative-medicine-schools' || $get_college_data['page_type']=='career-colleges' || $get_college_data['page_type']=='cosmetology-schools' || $get_college_data['page_type']=='graduate-schools' || $get_college_data['page_type']=='healthcare-nursing-schools' || $get_college_data['page_type']=='massage-schools') && $get_college_data['instsize']>1 && $check_college_degree_exists['number_results']>0){                         
                  
                   //url
                   $url=$GLOBALS['domain'].'/'.$get_college_data['page_type'].'/'.$get_college_data['page_id'].'/'.$get_degree_data['data'][0]['page_id'];
	               //$url=$GLOBALS['domain'].'/schools/'.$get_degree_data['data'][0]['page_id'];
               //not one of special schools that have college degree pages so make link "schools" link
               }else{                  
                  return _create_link($display,'schools',$anchor_or_sub_id);
               
               }
        
           //not found in degree table so create search link
           }else{            
            return _create_link($display,'schools',$anchor_or_sub_id);
           }
          
          
          //create anchor text
          $anchor_or_sub_id = $GLOBALS['database']->fetch_all_array("SELECT * FROM `"._get_latest_year_table('completions_degrees_by_program_list')."` WHERE `cipcode` = '".$get_degree_data['data'][0]['meta_value']."' AND `entity_id` = '{$id}' LIMIT 1");        
          if($anchor_or_sub_id['data'][0]['degree_name'] != '')
          {
            $anchor_or_sub_id=$anchor_or_sub_id['data'][0]['degree_name'];
          }
          else
          {
            $anchor_or_sub_id=$get_degree_data['data'][0]['degree_name'];
          }
          
        }else{$url=$GLOBALS['domain'];}
    
      
    }
    
    //return only url if $display='url'
    if($display=='url'){return 'the url is' . $url. '-'.$page_type;}
    
    if($class!=''){      
      return 'the anchor is'.'<a href="'.$url.'" class="'.$class.'">'.$anchor_or_sub_id.'</a>'. '-'.$page_type;
    }    
    //create link
    return 'another anchor is'.'<a href="'.$url.'">'.$anchor_or_sub_id.'</a>'. '-'.$page_type;
}


//degree name
function _degree_name($cipcode, $with_terminology='n',$id=''){

  global $wpdb;
  
  //try degree_information table first
  $get_degree=$wpdb->get_results("SELECT `post_title` as degree_name,
                                          (select meta_value from mc_postmeta where meta_key = 'terminology' and post_id = pm.post_id ) as `terminology` 
                                          from `mc_postmeta` pm 
                                          left join mc_posts p on p.ID = pm.post_id 
                                          where meta_key = 'degree_id' and meta_value = '{$cipcode}'",ARRAY_A);
  
  if(count($get_degree)==0){
    $get_degree=$wpdb->get_results("SELECT value_id, value as `degree_name` FROM `field_dictionary_values` WHERE `field_name` = 'cipcode' AND `value_id` = '$cipcode'",ARRAY_A);
    if($id != '')
    {
      $get_degree_from_list = $wpdb->get_results("SELECT `degree_name` FROM `"._get_latest_year_table('completions_degrees_by_program_list')."` WHERE `cipcode` = '".$get_degree[0]['value_id']."' AND `entity_id` = '{$id}'", ARRAY_A);      
      if($get_degree_from_list[0]['degree_name'] != '')
      {
        $str2 = str_ireplace(", Other","",$get_degree_from_list[0]['degree_name']);
        $get_degree_from_list[0]['degree_name']= _proper($str2);
        
        if($with_terminology!='n'){
          return $get_degree_from_list[0]['degree_name'].' programs';
        }
        
        return $get_degree_from_list[0]['degree_name'];
      }
    }
    else
    {
      $str2 = str_ireplace(", Other","",$get_degree[0]['degree_name']);
      $get_degree[0]['degree_name']= _proper($str2);
      
      if($with_terminology!='n'){
        return $get_degree[0]['degree_name'].' programs';
      }
      
      return $get_degree[0]['degree_name'];
    }
    
  }

  if($with_terminology!='n'){
    return $get_degree[0]['degree_name'].' '.$get_degree[0]['terminology'];
  }
  
  return $get_degree[0]['degree_name'];
      
}


//get system description
function _get_system_description($entity_id,$number_chars_returned=500000){
  global $wpdb;
  
  $get_system_post=$wpdb->get_results("SELECT p.ID
                                                          FROM mc_posts p
                                                          WHERE post_title = (
                                                          
                                                          SELECT meta_value
                                                          FROM `mc_posts` p
                                                          LEFT JOIN mc_postmeta pm ON `meta_key` = 'system_name'
                                                          AND pm.post_id = p.ID
                                                          WHERE p.`post_parent` = '{$entity_id}'
                                                         )",ARRAY_A
                                                        );
  
  if(count($get_system_post)>0){return _excerpt_by_id($get_system_post[0]['ID'], $number_chars_returned);}                                                      
  
  return;                                                        

}


//get field value
function _get_field_value($field_name,$id,$proper='yes',$return='N/A'){

  global $wpdb;

//check if N/A
if(_data_na($id)=='') return $return;
                                          
//try matchcollege field name first
$get_field_value=$wpdb->get_results("SELECT `value` FROM `field_dictionary_values` WHERE `field_name` = '$field_name' AND `value_id` = '$id'",ARRAY_A);
if($proper=='yes' && count($get_field_value)>0){return _proper($get_field_value[0]['value']);}
if($proper=='no' && count($get_field_value)>0){return $get_field_value[0]['value'];}

//default values 1 = yes, 2 = no
if($id=='1') {return 'Yes';}
if($id=='2') {return 'No';}
return $id;
}
//end get field value
                              
                              

//organize cipcodes by program
function _programs_offered_tuitions_by_program($college_data){

  if($college_data['student_charges_program']['number_results']==0){return;}        

  //largest program correct for tuition represented twice $data['program_1_tuition_fees'] or $data['y3_program_1_tuition_fees']
   $college_data['student_charges_program']['data'][0]['ciptuit1']=$college_data['student_charges_program']['data'][0]['ciptuit1']+$college_data['student_charges_program']['data'][0]['chg1py3'];
   $college_data['student_charges_program']['data'][0]['cipsupp1']=$college_data['student_charges_program']['data'][0]['cipsupp1']+$college_data['student_charges_program']['data'][0]['chg4py3'];

 //organize data
  //1st program
  if($college_data['student_charges_program']['data'][0]['ciptuit1']>0){
    $programs_offered[$college_data['student_charges_program']['data'][0]['cipcode1']][1]['tuition']=$college_data['student_charges_program']['data'][0]['ciptuit1'];
    $programs_offered[$college_data['student_charges_program']['data'][0]['cipcode1']][1]['cipsupp']=$college_data['student_charges_program']['data'][0]['cipsupp1'];
    $programs_offered[$college_data['student_charges_program']['data'][0]['cipcode1']][1]['ciplgth']=$college_data['student_charges_program']['data'][0]['ciplgth1'];
    $programs_offered[$college_data['student_charges_program']['data'][0]['cipcode1']][1]['prgmsr']=$college_data['student_charges_program']['data'][0]['prgmsr1'];
    $programs_offered[$college_data['student_charges_program']['data'][0]['cipcode1']][1]['mthcmp']=$college_data['student_charges_program']['data'][0]['mthcmp1'];
  }

  //2nd program  
  if($college_data['student_charges_program']['data'][0]['ciptuit2']>0){
    $programs_offered[$college_data['student_charges_program']['data'][0]['cipcode2']][2]['tuition']=$college_data['student_charges_program']['data'][0]['ciptuit2'];
    $programs_offered[$college_data['student_charges_program']['data'][0]['cipcode2']][2]['cipsupp']=$college_data['student_charges_program']['data'][0]['cipsupp2'];
    $programs_offered[$college_data['student_charges_program']['data'][0]['cipcode2']][2]['ciplgth']=$college_data['student_charges_program']['data'][0]['ciplgth2'];
    $programs_offered[$college_data['student_charges_program']['data'][0]['cipcode2']][2]['prgmsr']=$college_data['student_charges_program']['data'][0]['prgmsr2'];
    $programs_offered[$college_data['student_charges_program']['data'][0]['cipcode2']][2]['mthcmp']=$college_data['student_charges_program']['data'][0]['mthcmp2'];
  }
  
  //3rd program
  if($college_data['student_charges_program']['data'][0]['ciptuit3']>0){
    $programs_offered[$college_data['student_charges_program']['data'][0]['cipcode3']][3]['tuition']=$college_data['student_charges_program']['data'][0]['ciptuit3'];
    $programs_offered[$college_data['student_charges_program']['data'][0]['cipcode3']][3]['cipsupp']=$college_data['student_charges_program']['data'][0]['cipsupp3'];
    $programs_offered[$college_data['student_charges_program']['data'][0]['cipcode3']][3]['ciplgth']=$college_data['student_charges_program']['data'][0]['ciplgth3'];
    $programs_offered[$college_data['student_charges_program']['data'][0]['cipcode3']][3]['prgmsr']=$college_data['student_charges_program']['data'][0]['prgmsr3'];
    $programs_offered[$college_data['student_charges_program']['data'][0]['cipcode3']][3]['mthcmp']=$college_data['student_charges_program']['data'][0]['mthcmp3'];
  }
 
  //4th program  
  if($college_data['student_charges_program']['data'][0]['ciptuit4']>0){
    $programs_offered[$college_data['student_charges_program']['data'][0]['cipcode4']][4]['tuition']=$college_data['student_charges_program']['data'][0]['ciptuit4'];
    $programs_offered[$college_data['student_charges_program']['data'][0]['cipcode4']][4]['cipsupp']=$college_data['student_charges_program']['data'][0]['cipsupp4'];
    $programs_offered[$college_data['student_charges_program']['data'][0]['cipcode4']][4]['ciplgth']=$college_data['student_charges_program']['data'][0]['ciplgth4'];
    $programs_offered[$college_data['student_charges_program']['data'][0]['cipcode4']][4]['prgmsr']=$college_data['student_charges_program']['data'][0]['prgmsr4'];
    $programs_offered[$college_data['student_charges_program']['data'][0]['cipcode4']][4]['mthcmp']=$college_data['student_charges_program']['data'][0]['mthcmp4'];
  }
  
  //5th program  
  if($college_data['student_charges_program']['data'][0]['ciptuit5']>0){
    $programs_offered[$college_data['student_charges_program']['data'][0]['cipcode5']][5]['tuition']=$college_data['student_charges_program']['data'][0]['ciptuit5'];
    $programs_offered[$college_data['student_charges_program']['data'][0]['cipcode5']][5]['cipsupp']=$college_data['student_charges_program']['data'][0]['cipsupp5'];
    $programs_offered[$college_data['student_charges_program']['data'][0]['cipcode5']][5]['ciplgth']=$college_data['student_charges_program']['data'][0]['ciplgth5'];
    $programs_offered[$college_data['student_charges_program']['data'][0]['cipcode5']][5]['prgmsr']=$college_data['student_charges_program']['data'][0]['prgmsr5'];
    $programs_offered[$college_data['student_charges_program']['data'][0]['cipcode5']][5]['mthcmp']=$college_data['student_charges_program']['data'][0]['mthcmp5'];
  }
  
  //6th program
  if($college_data['student_charges_program']['data'][0]['ciptuit6']>0){
    $programs_offered[$college_data['student_charges_program']['data'][0]['cipcode6']][6]['tuition']=$college_data['student_charges_program']['data'][0]['ciptuit6'];
    $programs_offered[$college_data['student_charges_program']['data'][0]['cipcode6']][6]['cipsupp']=$college_data['student_charges_program']['data'][0]['cipsupp6'];
    $programs_offered[$college_data['student_charges_program']['data'][0]['cipcode6']][6]['ciplgth']=$college_data['student_charges_program']['data'][0]['ciplgth6'];
    $programs_offered[$college_data['student_charges_program']['data'][0]['cipcode6']][6]['prgmsr']=$college_data['student_charges_program']['data'][0]['prgmsr6'];
    $programs_offered[$college_data['student_charges_program']['data'][0]['cipcode6']][6]['mthcmp']=$college_data['student_charges_program']['data'][0]['mthcmp6'];
  }    

  return $programs_offered;

}




//get admissions highlight info
function _get_entity_admin_info($entity_id){

  $array=array();

  //get data
  $get_data=$GLOBALS['database']->fetch_all_array("select * FROM `"._get_latest_year_table('institutional_characteristics_educational_offerings')."` where `entity_id` = '$entity_id'");
  
  //% admitted
  @$array['pct_admitted']=_number_format(_total('pct',$get_data['data'][0]['admssnm']+$get_data['data'][0]['admssnw'],$get_data['data'][0]['applcnm']+$get_data['data'][0]['applcnw']),'',0,'','');
  
  //SAT SCORE RANGE
  $array['sat_avg']='';
  if(@$get_data['data'][0]['satvr25']>0 && @$get_data['data'][0]['satmt25']>0 && @$get_data['data'][0]['satwr25']>0 && @$get_data['data'][0]['satvr75']>0 && @$get_data['data'][0]['satmt75']>0 && @$get_data['data'][0]['satwr75']>0){
    @$array['sat_25']=$get_data['data'][0]['satvr25']+$get_data['data'][0]['satmt25']+$get_data['data'][0]['satwr25'];
    @$array['sat_75']=$get_data['data'][0]['satvr75']+$get_data['data'][0]['satmt75']+$get_data['data'][0]['satwr75'];
    @$array['sat_avg']=($array['sat_25']+$array['sat_75'])/2;
  }
  
  //ACT SCORE RANGE     `actcm25`, `actcm75`, `acten25`, `acten75`, `actmt25`, `actmt75`
  $array['act_avg']='';
  if(@$get_data['data'][0]['actcm25']>0 && @$get_data['data'][0]['acten25']>0 && @$get_data['data'][0]['actmt25']>0 && @$get_data['data'][0]['actcm75']>0 && @$get_data['data'][0]['acten75']>0 && @$get_data['data'][0]['actmt75']>0){  
    @$array['act_25']=($get_data['data'][0]['actcm25']+$get_data['data'][0]['acten25']+$get_data['data'][0]['actmt25'])/3;
    @$array['act_75']=($get_data['data'][0]['actcm75']+$get_data['data'][0]['acten75']+$get_data['data'][0]['actmt75'])/3;
    @$array['act_avg']=($array['act_25']+$array['act_75'])/2;
   }  
  
  return $array;
  
}


//get financial aid highlight info
function _get_entity_fin_aid_info($entity_id){

  $array=array();

  //FIN AID
  //get data
  $get_data=$GLOBALS['database']->fetch_all_array("select `anyaidp` FROM `"._get_latest_year_table('student_financial_aid')."` where `entity_id` = '$entity_id'");
  
  //percent getting fin aid
  @$array['pct_fin_aid']=$get_data['data'][0]['anyaidp'];  
  
  //DEFAULT
  //get data
  $get_data=$GLOBALS['database']->fetch_all_array("select `drate_1` FROM `"._get_latest_year_table('entity_default_rates')."`  edr 
                          left join `"._get_latest_year_table('institutional_characteristics_directory_information')."` icdi on left(icdi.opeid,6) = edr.OPE_ID
                          where icdi.`entity_id` = '$entity_id'");
    
  //percent getting fin aid
  @$array['pct_default']=$get_data['data'][0]['drate_1']; 
  
  return $array;   

}


//get grad highlight info
function _get_entity_grad_info($entity_id,$college_type){

  $array=array();
  
  //4-year
  if($college_type=='1' || $college_type=='2' || $college_type=='3'){
  //total cohort Adjusted cohort (revised cohort minus exclusions) GRTYPE = 2
  //grad GRTYPE = 3
  //calculate rate by deviding transfer rate / total cohort
  // total students = grtotlt      
  
   $get_data=$GLOBALS['database']->fetch_all_array("select `grtype`, `grtotlt` FROM `"._get_latest_year_table('graduation_rates_4_2_year')."` where `entity_id` = '$entity_id' and (`grtype` = 2 || `grtype` = 3) order by grtype");

   if($get_data['number_results']>1 && isset($get_data['data'][1]['grtype'])){
    $array['pct_grad']=$get_data['data'][1]['grtotlt']/$get_data['data'][0]['grtotlt']*100;
   }     

  }
  
  
  //community college (4)  
  if($college_type=='4'){
  
    //total cohort Adjusted cohort (revised cohort minus exclusions) GRTYPE = 29
    //grad GRTYPE = 30
    //transfer out GRTYPE = 33
    //calculate rate by deviding transfer rate / total cohort
    // total students = grtotlt
    
     //get data
     $get_data=$GLOBALS['database']->fetch_all_array("select `grtype`, `grtotlt` FROM `"._get_latest_year_table('graduation_rates_4_2_year')."` where `entity_id` = '$entity_id' and (`grtype` = 29 || `grtype` = 30 || `grtype` = 33) order by grtype");
  
     if($get_data['number_results']>1 && isset($get_data['data'][2]['grtype'])){
      $array['pct_transfer']=$get_data['data'][2]['grtotlt']/$get_data['data'][0]['grtotlt']*100;
     }  
     
     if($get_data['number_results']>1 && isset($get_data['data'][1]['grtype'])){
      $array['pct_grad']=$get_data['data'][1]['grtotlt']/$get_data['data'][0]['grtotlt']*100;
     }     
    
  }
  

  //career schools
  if($college_type=='5' || $college_type=='6' || $college_type=='7' || $college_type=='8' || $college_type=='9'){
  
    //first check <2 year school, then check 4 & 2 year schools
      
      //2 year
      //total cohort Adjusted cohort (revised cohort minus exclusions) LINE_50
      //grad LINE_11
      //transfer out LINE_30
      //calculate rate by deviding transfer rate / total cohort

     //get data
     $get_data=$GLOBALS['database']->fetch_all_array("select `line_50`, `line_11`, `line_30` FROM `"._get_latest_year_table('graduation_rates_4_2_year')."` where `entity_id` = '$entity_id'");
  
      if($get_data['number_results']>0){
        
        //grad
        if($get_data['data'][0]['line_50']>0 && $get_data['data'][0]['line_11']>0){
          $array['pct_grad']=$get_data['data'][0]['line_11']/$get_data['data'][0]['line_50']*100;
        }
        
        //transfer  
        if($get_data['data'][0]['line_50']>0 && $get_data['data'][0]['line_30']>0){
          $array['pct_transfer']=$get_data['data'][0]['line_30']/$get_data['data'][0]['line_50']*100;
        } 
             
      }else{
      //check if 4+2 year grad info exists
      
         $get_data=$GLOBALS['database']->fetch_all_array("select `grtype`, `grtotlt` FROM `"._get_latest_year_table('graduation_rates_4_2_year')."` where `entity_id` = '$entity_id' and (`grtype` = 29 || `grtype` = 30 || `grtype` = 33) order by grtype");
      
         if($get_data['number_results']>1 && isset($get_data['data'][2]['grtype'])){
          $array['pct_transfer']=$get_data['data'][2]['grtotlt']/$get_data['data'][0]['grtotlt']*100;
         }  
         
         if($get_data['number_results']>1 && isset($get_data['data'][1]['grtype'])){
          $array['pct_grad']=$get_data['data'][1]['grtotlt']/$get_data['data'][0]['grtotlt']*100;
         }           
          
      
      }
  
  }  

  
  
  //percent getting fin aid
  @$array['pct_fin_aid']=$get_data['data'][0]['anyaidp'];  
  
  //DEFAULT
  //get data
  $get_data=$GLOBALS['database']->fetch_all_array("select `drate_1` FROM `"._get_latest_year_table('entity_default_rates')."` where `entity_id` = '$entity_id'");
  
  //percent getting fin aid
  @$array['pct_default']=$get_data['data'][0]['drate_1'];   
  
  return $array;        

}

// 24th January 2017
function wpdocs_register_my_custom_menu_page(){
    add_menu_page( 
        __( 'Custom Menu Title', 'textdomain' ),
        'Change Course',
        'manage_options',
        'custompage',
        'my_custom_menu_page',
        false,
        6
    ); 
}
add_action( 'admin_menu', 'wpdocs_register_my_custom_menu_page' );
function my_custom_menu_page(){  
  include get_stylesheet_directory().'/form-file.php';
  //include DOCUMENT_ROOT.'/form-file.php';  
}
add_action('wp_ajax_nopriv_walk_transit_bike_score', 'walk_transit_bike_score');
add_action('wp_ajax_walk_transit_bike_score', 'walk_transit_bike_score');
function walk_transit_bike_score(){                          
      //$str1 = 'something';
      $address=urlencode($_REQUEST['address']);
      $lat = $_REQUEST['lat'];
      $lon = $_REQUEST['lon'];
      $wsapikey = $_REQUEST['wsapikey'];     
      $url = "http://api.walkscore.com/score?format=json&address=$address&lon=$lon&lat=$lat&transit=1&bike=1&wsapikey=$wsapikey"; 
      $str = @file_get_contents($url);      
      echo $str;
      wp_die();
}
/* 7th February 2017 */
/* for search box in Change Course section on backend side  */
add_action('wp_ajax_nopriv_walk_transit_bike_score1', 'walk_transit_bike_score1');
add_action('wp_ajax_walk_transit_bike_score1', 'walk_transit_bike_score1');
function walk_transit_bike_score1(){
    global $wpdb;
    $results = $wpdb->get_results("SELECT `entity_id`, `instnm` FROM `institutional_characteristics_directory_information_2015` WHERE `instnm` LIKE '%".$_REQUEST['college_name']."%'");
    $result_college = array();
    foreach($results as $result){
      $result_college[] = $result->instnm;
    }          
    echo json_encode($result_college);
    wp_die();
}

/* for autoload colleges in Change Course section on backend side  */
add_action('wp_ajax_nopriv_search_course_auto', 'search_course_auto');
add_action('wp_ajax_search_course_auto', 'search_course_auto');
function search_course_auto(){
	global $wpdb;
	if ( is_admin() ) {
		$results        = $wpdb->get_results( "SELECT `entity_id`, `instnm` FROM `institutional_characteristics_directory_information_2015` " );
		$result_college = array();
		foreach ( $results as $result ) {
			$result_college[] = $result->instnm;
		}
		echo json_encode( $result_college );
		wp_die();
	}
}

add_filter( 'postmeta_form_limit', 'meta_limit_increase' );
function meta_limit_increase( $limit ) {
    return 50;
}

// 24th January 2017

// 25th September 2017
function wpdocs_register_custom_CE_Widget_default_val_menu_page(){
	add_menu_page(
		__( 'Custom Menu Title', 'textdomain' ),
		'Set CE Widget Default Val',
		'manage_options',
		'ce_custompage',
		'my_ce_widget_default_val_page',
		false,
		6
	);
}
add_action( 'admin_menu', 'wpdocs_register_custom_CE_Widget_default_val_menu_page' );
function my_ce_widget_default_val_page(){
	include get_stylesheet_directory().'/ce-widget-default-val-admin.php';
	//include DOCUMENT_ROOT.'/form-file.php';
}

/* for search box in Set CE Widget Default Value on backend side  */
add_action('wp_ajax_nopriv_search_ce_values', 'search_ce_values');
add_action('wp_ajax_search_ce_values', 'search_ce_values');
function search_ce_values(){
	global $wpdb;
	if ( is_admin() ) {
		$results        = $wpdb->get_results( "SELECT `url` FROM `ce_widget_default_val` " );
		$result_college = array();
		foreach ( $results as $result ) {
			$result_college[] = $result->url;
		}
		echo json_encode( $result_college );
		wp_die();
	}
}

add_action('wp_ajax_nopriv_search_url_val', 'search_url_val');
add_action('wp_ajax_search_url_val', 'search_url_val');
function search_url_val(){
	global $wpdb;
	$results = $wpdb->get_results("SELECT `url` FROM `ce_widget_default_val` WHERE `url` LIKE = '".$_REQUEST['college_name']."'");
	$result_college = array();
	foreach($results as $result){
		$result_college[] = $result->url;
	}
	echo json_encode($result_college);
	wp_die();
}
// 25th September 2017

function maybe_change_wp_title_ver( $title, $sep ) {
	//if(strlen($title) > 10)
	//return $title.'testing conten';
}

add_filter( 'wp_title', 'maybe_change_wp_title_ver', 99, 2 );

/********** State pages custom text update ***************/

function wpdocs_register_state_page_text_update_menu_page(){
	add_menu_page(
		__( 'Custom Menu Title', 'textdomain' ),
		'Set State New content',
		'manage_options',
		'state_content_custompage',
		'my_state_pages_new_content_page',
		false,
		6
	);
}
add_action( 'admin_menu', 'wpdocs_register_state_page_text_update_menu_page' );
function my_state_pages_new_content_page(){
	include get_stylesheet_directory().'/state_new_content_admin.php';
}

/* for search box in State pages custom text on backend side  */
add_action('wp_ajax_nopriv_search_state_contnet_url', 'search_state_contnet_url');
add_action('wp_ajax_search_state_contnet_url', 'search_state_contnet_url');
function search_state_contnet_url(){
	global $wpdb;
	if ( is_admin() ) {
		$results        = $wpdb->get_results( "SELECT `url` FROM `state_page_new_content_data` " );
		$result_college = array();
		foreach ( $results as $result ) {
			$result_college[] = $result->url;
		}
		echo json_encode( $result_college );
		wp_die();
	}
}

add_action('wp_ajax_nopriv_search_state_url_val', 'search_state_url_val');
add_action('wp_ajax_search_state_url_val', 'search_state_url_val');
function search_state_url_val(){
	global $wpdb;
	$results = $wpdb->get_results("SELECT `url` FROM `state_page_new_content_data` WHERE `url` LIKE = '".$_REQUEST['college_name']."'");
	$result_college = array();
	foreach($results as $result){
		$result_college[] = $result->url;
	}
	echo json_encode($result_college);
	wp_die();
}

/********** State pages custom text update ***************/