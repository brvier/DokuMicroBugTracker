<?php
/**
 * DokuMicroBugTracker Plugin: allows to create simple bugtracker
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Benoît HERVIER <khertan@khertan.net>  
 */
 
//session_start();
 
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_dokumicrobugtracker extends DokuWiki_Syntax_Plugin {
  
  /**
   * return some info
   */
  function getInfo(){
    return confToHash(dirname(__FILE__).'/INFO');
  }

  function getType(){ return 'substition';}
  function getPType(){ return 'block';}
  function getSort(){ return 167; }
//      function getSort() { return 314; }
  
  /**
   * Connect pattern to lexer
   */
  function connectTo($mode){
    $this->Lexer->addSpecialPattern('\{\{dokumicrobugtracker>[^}]*\}\}',$mode,'plugin_dokumicrobugtracker');
    
/*    $this->Lexer->addSpecialPattern('{dokumicrobugtracker.*?>.+?</dokumicrobugtracker>', $mode, 'plugin_dokumicrobugtracker'); */
  }

  /**
   * Handle the match
   */
    function handle($match, $state, $pos, &$handler){
        $match = substr($match,22,-2); //strip markup from start and end

        $data = array();

        //handle params
        $params = explode('|',$match,3);
        foreach($params as $param){
            $splitparam = explode('=',$param);
            if ($splitparam[0]=='project'){$data['project'] = $splitparam[1];}
            else {if ($splitparam[0]=='status'){$data['status'] = $splitparam[1];}
			else {if ($splitparam[0]=='display'){$data['display'] = $splitparam[1];}
            // DO NOT USE THAT IT S TO UNSECURE : else {$data[$splitparam[0]] = $splitparam[1];}
            }}
            }
        return $data;
    }


  /**
   * Create output
   */
  function render($mode, &$renderer, $data) {
  
    if ($mode == 'xhtml'){
      global $ID;
      
      $renderer->info['cache'] = false;

      /*print 'test:'.$data.'<br>suite<br>';
      foreach ($data as $key=>$value)
        {print ':'.$key.'=>'.$value.':'.$data[$key].'<br>';}*/
        
      if (!($data['status'])) {$data['status']='ALL';}
	  
      // prevent caching to ensure the list are fresh

            
      // get bugs file contents
      $pfile = metaFN(md5($data['project']), '.bugs');
	  if (@file_exists($pfile)) {
		$bugs  = unserialize(@file_get_contents($pfile));}
	  else {$bugs = array();}
      
	  //print $bugs[0]['version'];
      //$more = trim(array_shift($options));      

      
	  if (($_REQUEST['admin_edit']=='yes') && (auth_quickaclcheck($ID) >= AUTH_EDIT))
	  {
		if (checkSecurityToken())
		{
			$id = (int) $_REQUEST['edit_id'];
			$bugs[$id]['version'] = htmlspecialchars(stripslashes($_REQUEST['edit_version']));
			$bugs[$id]['severity'] = htmlspecialchars(stripslashes($_REQUEST['edit_severity']));
			$bugs[$id]['status'] = htmlspecialchars(stripslashes($_REQUEST['edit_status']));
			$bugs[$id]['description'] = htmlspecialchars(stripslashes($_REQUEST['edit_description']));
			$bugs[$id]['resolution'] = htmlspecialchars(stripslashes($_REQUEST['edit_resolution']));
			//Ecriture en pseudo db
			$fh = fopen($pfile, 'w');
			fwrite($fh, serialize($bugs));
			fclose($fh);
		}
	  }

	  if (($_REQUEST['admin_edit']=='delete') && (auth_quickaclcheck($ID) >= AUTH_ADMIN))
	  {
		if (checkSecurityToken())
		{
			$id = (int) $_REQUEST['edit_id'];
			unset($bugs[$id]);
			$fh = fopen($pfile, 'w');
			fwrite($fh, serialize($bugs));
			fclose($fh);
		}
	  }
	  
      //If it s a usr report add it to the pseudo db
      
      if ($_REQUEST['captcha'])
      {
          if (($_REQUEST['captcha']==$_SESSION['security_number']) && ($_REQUEST['admin_edit']!='yes') && (($data['display']=='form') || (!($data['display']))))
            {
            if (checkSecurityToken())
                {
                //Add it      
                $id = count($bugs);
                $bugs[$id]['id'] = $id;    
                $bugs[$id]['version'] = htmlspecialchars(stripslashes($_REQUEST['version']));
                $bugs[$id]['severity'] = htmlspecialchars(stripslashes($_REQUEST['severity']));
                $bugs[$id]['status'] = "New";
                $bugs[$id]['description'] = htmlspecialchars(stripslashes($_REQUEST['description']));
                $bugs[$id]['resolution'] = '';
                //Ecriture en pseudo db
                $fh = fopen($pfile, 'w');
                fwrite($fh, serialize($bugs));
                fclose($fh);
                $resumed_enter = 'Your report have been successfully stored as bug#'.$id;
                $this->_emailForNewBug($id,$data['project'],$bugs[$id]['version'],$bugs[$id]['severity'],$bugs[$id]['description']);
                }
            }
      else
            {
            $resumed_enter = $_SESSION['security_number'].':Wrong answer to the antispam question.';
            }  
      }
 
      
	  
      //If no display option show all
	  if (!($data['display']))
		{
		// display title
		$renderer->doc .= '<fieldset class="dokumicrobugtracker">'.
        '<legend>'.$data['project'].'</legend>';
		
		//Show current bugs
		$renderer->doc .= $this->_showBugs($data['project'],$bugs,$data['status']);
		//Show Form			
	    $renderer->doc .= $this->_showReportForm($renderer);
		if ($resumed_enter)
			{$renderer->doc .= $this->_show_message($resumed_enter);}
//        else if ($_REQUEST['secure']==$_SESSION['security_number']) {{$renderer->doc .= $this->_show_message('<br>Wrong Answer to the security question<br/>');}}

		$renderer->doc .= '</fieldset>';
		}
	  else 
		{ 
		if ($data['display']=='form') 
			{
			// display title
			$renderer->doc .= '<fieldset class="dokumicrobugtracker">'.
			'<legend>'.$data['project'].'</legend>';
		
			//Show Form
	        $renderer->doc .= $this->_showReportForm($renderer);
            if ($resumed_enter)
                {$renderer->doc .= $this->_show_message($resumed_enter);}
 //           else if ($_REQUEST['secure']==$_SESSION['security_number']) {{$renderer->doc .= $this->_show_message('<br>Wrong Answer to the security question<br/>');}}
            
			$renderer->doc .= '</fieldset>';
			} 
		else 
			{
			if ($data['display']=='bugs') 
				{
				// display title
				$renderer->doc .= '<fieldset class="dokumicrobugtracker">'.
				'<legend>'.$data['project'].'</legend>';
		
				//Show current bugs
				$renderer->doc .= $this->_showBugs($data['project'],$bugs,$data['status']);
				
				 $renderer->doc .= '</fieldset>';
				}
			}
		}
      
     
          
      return true;
    }
    return false;
  }

  function _emailForNewBug($id,$project,$version,$severity,$description)
    {
        if ($this->getConf('address_email')==True)
        {
            $body='A new bug have entered in the project : '.$project.' (id : '.$id.")\n\n".'Version : '.$version.' ('.$severity.") :\n".$description;
            $subject='A new bug have entered in the project : '.$project.'Version :'.$version.' ('.$severity.') : '.$id;
            $from=$this->getConf('address_email') ;
            $to=$from;
            mail_send($to, $subject, $body, $from, $cc='', $bcc='', $headers=null, $params=null);
        }
    }
  
  function _showBugs($project,$bugs,$status){
    GLOBAL $ID;
	
    $total = count($bugs);
    if ($total == 0) return '';
	
	if (!(auth_quickaclcheck($ID) >= AUTH_EDIT))
		{
	    $ret = '<table id="sortableTable.'.$project.'.'.$status.'" class="inline" style="margin-left: auto;margin-right: auto;">'.
			   '<colgroup>'.
					'<col id="col1_1"></col>'.
					'<col id="col1_2"></col>'.
					'<col id="col1_3"></col>'.
					'<col id="col1_4"></col>'.
					'<col id="col1_5"></col>'.
					'<col id="col1_6"></col>'.
			   '</colgroup>'.
			   '<thead><tr class="row0"><td class="col0">Id</td><td class="col1">Status</td><td class="col2">Severity</td><td class="col3">Version</td><td class="col4">Description</td><td class="col5">Resolution</td></tr></thead><tbody>';
		for ($i = 0; $i <= $total; $i++)
			{
			if ($bugs[$i]['status'])
				{
				if ((strtoupper($status)=='ALL') || (is_numeric(strpos(strtoupper($status),strtoupper($bugs[$i]['status'])))))
					{
					$ret .= '<tr class="row'.($i+1).'">'.
					'<td class="col0">'.$bugs[$i]['id'].'</td>'.
					'<td class="col1">'.$bugs[$i]['status'].'</td>'.
					'<td class="col2">'.$bugs[$i]['severity'].'</td>'.
					'<td class="col3">'.$bugs[$i]['version'].'</td>'.
					'<td class="col4">'.$bugs[$i]['description'].'</td>'.
					'<td class="col5">'.$bugs[$i]['resolution'].'</td>'.
					'</tr>';
					}
				}
			}
		$ret .= '</tbody></table><script type="text/javascript">initSortTable("sortableTable.'.$project.'.'.$status.'",Array(\'N\',\'S\',\'S\',\'N\',\'S\',\'S\'));</script>';
		}
	else
		{	
	    $ret = '<table id="sortableTable.'.$project.'.'.$status.'" class="inline" style="margin-left: auto;margin-right: auto;">'.
			   '<colgroup>'.
					'<col id="col1_1"></col>'.
					'<col id="col1_2"></col>'.
					'<col id="col1_3"></col>'.
					'<col id="col1_4"></col>'.
					'<col id="col1_5"></col>'.
					'<col id="col1_6"></col>';
                    '<col id="col1_7"></col>';
        if ((auth_quickaclcheck($ID) >= AUTH_ADMIN))            
            {$ret .= '<col id="col1_8"></col>';}
            
        $ret .= '</colgroup>'.
			   '<thead><tr class="row0"><td class="col0">Id</td><td class="col1">Status</td><td class="col2">Severity</td><td class="col3">Version</td><td class="col4">Description</td><td class="col5">Resolution</td><td class="col6"></td>';
        if ((auth_quickaclcheck($ID) >= AUTH_ADMIN))            
               {$ret .= '<td class="col7"></td>';}               
        $ref .= '</tr></thead><tbody>';
		for ($i = 0; $i <= $total; $i++){
		
			if ($bugs[$i]['status'])
			{
				if ((strtoupper($status)=='ALL') || (is_numeric(strpos(strtoupper($status),strtoupper($bugs[$i]['status'])))))
				{
					
					$ret .= '<tr class="row'.($i+1).'">'.
					'<form id="dokumicrobugtracker__form" method="post" action="'.script().'" accept-charset="'.$lang['encoding'].'">'.
					'<input type="hidden" name="do" value="show" />'.
					'<input type="hidden" name="id" value="'.$ID.'" />'.
					'<input type="hidden" name="admin_edit" value="yes" />'.
					'<input type="hidden" name="edit_id" value="'.$bugs[$i]['id'].'" />'.
					formSecurityToken(false).
					
					'<td class="col0">'.$bugs[$i]['id'].'</td>'.
					'<td class="col1">'.'<input id="dokumicrobugtracker__option" name="edit_status" type="text" maxlength="64" value="'.$bugs[$i]['status'].'"/>'.'</td>'.
					'<td class="col2">'.'<input id="dokumicrobugtracker__option" name="edit_severity" type="text" maxlength="64" value="'.$bugs[$i]['severity'].'"/>'.'</td>'.
					'<td class="col3">'.'<input id="dokumicrobugtracker__option" name="edit_version" type="text" maxlength="64" value="'.$bugs[$i]['version'].'"/>'.'</td>'.
					'<td class="col4">'.'<textarea id="dokumicrobugtracker__option" cols=30 rows=8 name="edit_description" type="text"/>'.$bugs[$i]['description'].'</textarea></td>'.
					'<td class="col5">'.'<input id="dokumicrobugtracker__option" name="edit_resolution" type="text" maxlength="64" value="'.$bugs[$i]['resolution'].'"/>'.'</td>'.
					'<td class="col6">'.'<input class="button" type="submit" value="Save">'.'</td>'.
   					'</form>';
                    if ((auth_quickaclcheck($ID) >= AUTH_ADMIN))            
                        {$ret .= '<form id="dokumicrobugtracker__form" method="post" action="'.script().'" accept-charset="'.$lang['encoding'].'">'.
                        '<input type="hidden" name="do" value="show" />'.
                        formSecurityToken(false).
                        '<input type="hidden" name="id" value="'.$ID.'" />'.
                        '<input type="hidden" name="admin_edit" value="delete" />'.
                        '<input type="hidden" name="edit_id" value="'.$bugs[$i]['id'].'" />'.
                        '<td class="col7"><input class="button" type="submit" value="Delete"></td></form>';
                        }

					$ref .= '</tr>';
				}
			}
		}
		$ret .= '</tbody></table><script type="text/javascript">initSortTable("sortableTable.'.$project.'.'.$status.'",Array(\'N\',\'S\',\'S\',\'N\',\'S\',\'S\'));</script>';
    }
	
    return $ret;
  }
    
  function _showReportForm(&$renderer){
    global $lang;
    global $ID;
	
    $i = 0;
    $ret = '<br><h5>Report a new bug</h5><br><form id="dokumicrobugtracker__form" method="post" action="'.script().'" accept-charset="'.$lang['encoding'].'"><div class="no">';
	$ret .= formSecurityToken(false).
      '<input type="hidden" name="do" value="show" />'.
      '<input type="hidden" name="id" value="'.$ID.'" />'.
      '<span><label> Version : </label><input id="dokumicrobugtracker__option" name="version" type="text" maxlength="64" value="'.$_REQUEST['version'].'"/></span>'.
      '<span><label> Severity : </label>'.
      '  <select class="element select small" id="dokumicrobugtracker__option" name="severity">'.
      '       <option value="Minor Bug" >Minor Bug</option>'.
      '       <option value="Feature Request" >Feature Request</option>'.
      '       <option value="Major Bug" >Major Bug</option>'.
	  '	 </select></span>'.      
      '<br><label> Description : </label><br /><textarea id="dokumicrobugtracker__option" name="description" cols=80 rows=6 type="text"/>'.$_REQUEST['description'].'</textarea><br />'.
      '<span><img src="'.DOKU_BASE.'lib/plugins/dokumicrobugtracker/image.php"><label>what s the result? </label><input id="dokumicrobugtracker__option" name="captcha" type="text" maxlength="3" value=""/></span>';      

    $ret .= '<input class="button" type="submit" '.
      'value="Report" />'.
      '</div></form>';
				
    $ret .= 'DEBUG:'.$ID[0].':'.$ID[1];
    return $ret;
  }  
  
  function _show_message($string){
		return "<script type='text/javascript'>
			alert('$string');
		</script>";
	}
}
 
//Setup VIM: ex: et ts=4 enc=utf-8 :
