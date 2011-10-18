<?php
/**
* DokuMicroBugTracker Plugin: allows to create simple bugtracker
*
* @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
* @author     Benoît HERVIER <khertan@khertan.net>  
*/
//error_reporting(E_ALL);ini_set('display_errors', true);
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
function metaFN2($id,$ext){    global $conf;    $id = cleanID($id);    $id = str_replace(':','/',$id);    $fn = $conf['metadir'].'/'.utf8_encodeFN($id).$ext;    return $fn;}

/**
* All DokuWiki plugins to extend the parser/rendering mechanism
* need to inherit from this class
*/
class syntax_plugin_dokumicrobugtracker extends DokuWiki_Syntax_Plugin 
{
    /**
    * return some info
    */
    function getInfo(){
        return confToHash(dirname(__FILE__).'/INFO');
    }

    function getType(){ return 'substition';}
    function getPType(){ return 'block';}
    function getSort(){ return 167;}
    /**
  
    * Connect pattern to lexer
    */
    function connectTo($mode){
        $this->Lexer->addSpecialPattern('\{\{dokumicrobugtracker>[^}]*\}\}',$mode,'plugin_dokumicrobugtracker');
    }
    /**
    * Handle the match
    */
    function handle($match, $state, $pos, &$handler){
        $match = substr($match,22,-2); //strip markup from start and end
        //handle params
        $data = array();
        $data['match'] = $match;
        $params = explode('|',$match,3);
        
        //Default Value
        $data['display'] = 'ALL';
        $data['status'] = 'ALL';
        foreach($params as $param){            
            $splitparam = explode('=',$param);
            if ($splitparam[1] != '')
                {
                if ($splitparam[0]=='project')
                	{$data['project'] = $splitparam[1];
                    /*continue;*/}

                if ($splitparam[0]=='status')
                	{$data['status'] = strtoupper($splitparam[1]);
                    /*continue;*/}
                
                if ($splitparam[0]=='display')
                	{$data['display'] = strtoupper($splitparam[1]);
                    /*continue;*/}                                   
                }
        }
        
		return $data;
		}
    
	/**	* Captcha OK	*/    
		function _captcha_ok()
		{        			
			$helper = null;		
			if(@is_dir(DOKU_PLUGIN.'captcha'))
				$helper = plugin_load('helper','captcha');
			if(!is_null($helper) && $helper->isEnabled())
				{	
				return $helper->check();
				}
			return ($this->getConf('use_captcha'));
		}
	
    /**
    * Create output
    */
    function render($mode, &$renderer, $data) {        
        global $ID;
		if ($mode == 'xhtml'){

            $renderer->info['cache'] = false;
            
            // get bugs file contents
            $pfile = metaFN2(md5($data['project']), '.bugs');            
            if (@file_exists($pfile))
            	{$bugs  = unserialize(@file_get_contents($pfile));}
            else
            	{$bugs = array();}
				
		    
            
			$Generated_Header = '';
            if (($data['display']=='FORM') || ($data['display']=='ALL'))
            {
                //If it s a usr report add it to the pseudo db
                $Generated_Header = '';
				if (isset($_REQUEST['severity'])) {
					if ($_REQUEST['severity'])
					{
					    if ($_REQUEST['version']== '')
					    {
					        $Generated_Header = '<div class ="error">Please enter a version number.</div>';
					    }
					    elseif ($_REQUEST['description']== '')
					    {
					        $Generated_Header = '<div class ="error">Please enter a description.</div>';
					    }
					    elseif (!$this->_captcha_ok())
					    {
					        $Generated_Header = '<div class ="error">Wrong answer to the antispam question.</div>';
					    }					    
						else
						{
							if (checkSecurityToken())
							{
								//Add it
								$bug_id=count($bugs);      
								foreach ($bugs as $value)
									{if ($value['id'] >= $bug_id) {$bug_id=$value['id'] + 1;}}
								$bugs[$bug_id]['id'] = $bug_id;    
								$bugs[$bug_id]['version'] = htmlspecialchars(stripslashes($_REQUEST['version']));
								$bugs[$bug_id]['severity'] = htmlspecialchars(stripslashes($_REQUEST['severity']));
								$bugs[$bug_id]['status'] = "New";
								$bugs[$bug_id]['description'] = htmlspecialchars(stripslashes($_REQUEST['description']));
								$bugs[$bug_id]['resolution'] = '';
								$bugs[$bug_id]['author'] = htmlspecialchars(stripslashes($_REQUEST['email']));
								//Ecriture en pseudo db
								$fh = fopen($pfile, 'w');
								fwrite($fh, serialize($bugs));
								fclose($fh);
								$Generated_Header = '<div style="border: 3px green solid; background-color: lightgreen; margin: 10px; padding: 10px;">Your report have been successfully stored as bug#'.$bug_id.'</div>';
								$this->_emailForNewBug($bug_id,$data['project'],$bugs[$bug_id]['version'],$bugs[$bug_id]['severity'],$bugs[$bug_id]['description']);
								$_REQUEST['description'] = '';
							}
						}
					}
				}
            }
            $Generated_Table = '';
            $Generated_Scripts = '';
            $Generated_Report = '';
            
            // Creation de la table            
            if (($data['display']=='BUGS') || ($data['display']=='ALL'))
            {
                $Generated_Table = $this->_table_render($bugs,$data); 
                $Generated_Scripts = $this->_scripts_render();
            }

            // Count only ...        
            if ($data['display']=='COUNT') 
            {
                $Generated_Table = $this->_count_render($bugs);                
            }            
            // Generation du form
            if (($data['display']=='FORM') || ($data['display']=='ALL'))
            {$Generated_Report = $this->_report_render();}
                        
            // Render            
            $renderer->doc .= $Generated_Header.$Generated_Table.$Generated_Scripts.$Generated_Report;
        }
    }

    function _count_render($bugs)
    {
        $count = array();
        foreach ($bugs as $bug)
        {
            $status = $this->_get_one_value($bug,'status');
            if ($status != '')
                    if ($this->_get_one_value($count,$status)=='')
                    {$count[$status] = array(1,$status);}
                else
                    {$count[$status][0] += 1;}                                
        }
        $rendered_count = '<ul>';
        foreach ($count as $value)
        {
            $rendered_count .= '<li>'.$value[1].' : '.$value[0].'</li>';
        }
        $rendered_count .= '</ul>';
        return $rendered_count;
    }
    
    function _explode_pref_to_jsarray($pref_name)
    {
        $jsarray = "";
        $values = explode(',', $this->getConf($pref_name));
        foreach ($values as $value) {
            $jsarray .= '[\''.$value.'\',\''.$value.'\'],';
        }
        return $jsarray;
    }


    
    
    function _edit_scripts_render()
    {
        $BASE = DOKU_BASE."lib/plugins/dokumicrobugtracker/";
        return    "
            <script type=\"text/javascript\"><!--
                jQuery(document).ready(function() {
                   /* Init DataTables */
                    //Delete row
                    jQuery('td.deleterow').click( function () {
                        /* Get the position of the current data from the node */
                        var aPos = oTable.fnGetPosition( this );
                        /* Get the data array for this row */
                        var answer = confirm('Are you sure you want to delete this report ?');
                        if (answer) {
                            jQuery.ajax({
                                url : '".$BASE."edit.php',
                                type: 'POST',
                                data: 'row_id='+this.parentNode.getAttribute('id')+'&field=delete',
                                method:'POST',
                                }).done(function() {
                                    oTable.fnDeleteRow(aPos[0]);
                            });
                        }
                        });
                            
                   var oTable = jQuery('.display').dataTable( {
                        \"aaSorting\": [[ 0, \"desc\" ]],
                        \"aLengthMenu\": [[10, 25, 50, -1], [10, 25, 50, \"All\"]],
                        \"bLengthChange\": true,
                        \"bAutoWidth\": true
                      });
   
                       /* Apply the jEditable handlers to the table */
                       jQuery('td[class!=\"deleterow\"]', oTable.fnGetNodes()).editable( '".$BASE."edit.php', {
                          \"placeholder\": '',
                          \"callback\": function( sValue, y ) {
                             var aPos = oTable.fnGetPosition( this );
                             oTable.fnUpdate( sValue, aPos[0], aPos[1] );
                          },
                          \"submitdata\": function ( value, settings ) {
                            var oSettings = oTable.fnSettings();  // you can find all sorts of goodies in the Settings
                            var col_id = oSettings.aoColumns[oTable.fnGetPosition( this )[2]].sTitle;  //for this code, we just want the sTitle


                             return {
                                \"row_id\": this.parentNode.getAttribute('id'),
                                \"field\": col_id,

                                \"column\": oTable.fnGetPosition( this )[2]
                             };
                          },
                          \"height\": \"14px\"
                       });
   
                       //Bind keydown for auto validation
                       jQuery('td.editbox').bind('keydown', function(event) {
                            if(event.keyCode==9) {
                                jQuery(this).find(\"input\").submit();
                                if ($(this).is(\".lasteditbox\")) {
                                    jQuery(\"td.editbox:first\").click();
                                } else {
                                    jQuery(this).next(\"td.editbox\").click();
                                }
                            return false;
                            }
                        });
                });
            // --></script>";
    }

    function _default_scripts_render() {
        $BASE = DOKU_BASE."lib/plugins/dokumicrobugtracker/";
        return    "
            <script type=\"text/javascript\"><!--
                jQuery(document).ready(function() {
                    /* Init DataTables */
                    var oTable = jQuery('.display').dataTable();
                    });
            // --></script>";
    }

    function _scripts_render() {
        global $ID;
        if (auth_quickaclcheck($ID) >= AUTH_ADMIN) {
            return $this->_edit_scripts_render();
        } else {
            return $this->_default_scripts_render();
        }
    }
    
    function _table_render($bugs,$data)
    {
        global $ID;
        if (auth_quickaclcheck($ID) >= AUTH_ADMIN)        
            {            $head = "<div class='dokumicrobugtracker_div'><table id='".$data['project']."' class=\"display sortable editable inline resizable \"><thead><tr><td id='id'>Id</td><td id='Status'>Status</td><td id='Severity'>Severity</td><td id='Version'>Version</td><td id='Description'>Description</td><td id='Resolution'>Resolution</td><td class=\"noedit nocol\"></td></tr></thead>";        } 
        else       
            {            $head = "<div class='dokumicrobugtracker_div'><table id='".$data['project']."' class=\"display sortable inline resizable \"><thead><tr><td id='id'>Id</td><td id='Status'>Status</td><td id='Severity'>Severity</td><td id='Version'>Version</td><td id='Description'>Description</td><td id='Resolution'>Resolution</td></tr></thead>";        }
        
        $body = "<tbody>";
        foreach ($bugs as $bug)
        {

			
            if (($data['status']=='ALL') || (strtoupper($bug['status'])==$data['status'])) {
                $body .= '<tr id = "'.$data['project'].' '.$this->_get_one_value($bug,'id').'" class="'.$this->_status_color($bug).'">'.
                            '<td>'.$this->_get_one_value($bug,'id').'</td>'.
                            '<td>'.$this->_get_one_value($bug,'status').'</td>'.
                            '<td>'.$this->_get_one_value($bug,'severity').'</td>'.
                            '<td>'.$this->_get_one_value($bug,'version').'</td>'.
                            '<td class="canbreak">'.$this->_get_one_value($bug,'description').'</td>'.
                            '<td>'.$this->_get_one_value($bug,'resolution').'</td>';
                if (auth_quickaclcheck($ID) >= AUTH_ADMIN) {
                    $body .= '<td class="deleterow">Delete</td>';    
                }
                $body .= '</tr>';    
            }
        }
        $body .= '</tbody></table></div>';        
        return $head.$body;
    }

    function _status_color($bug) {
        $statuses = explode(',', $this->getConf('statuses'));
        $key = array_search($bug['status'],$statuses);
        
        if ($key == 0)
            return 'status_bad';
        if ($key == count($statuses) - 1)
            return  'status_good';
        return 'status_normal';
    }
    
    function _get_one_value($bug, $key) {
        if (array_key_exists($key,$bug))
            return $bug[$key];
        return '';
    }

    function _emailForNewBug($id,$project,$version,$severity,$description)
    {
        if ($this->getConf('send_email')==1)
        {
            $body='A new bug have entered in the project : '.$project.' (id : '.$id.")\n\n".' Version : '.$version.' ('.$severity.") :\n".$description;
            $subject='A new bug have entered in the project : '.$project.' Version :'.$version.' ('.$severity.') : '.$id;
            $from=$this->getConf('email_address') ;
            $to=$from;
            mail_send($to, $subject, $body, $from, $cc='', $bcc='', $headers=null, $params=null);
        }
    }

   function _report_render()
    {
        global $lang;
        global $ID;
		$ret = '<br /><br /><form class="dokumicrobugtracker__form" method="post" action="'.$_SERVER['REQUEST_URI'].'" accept-charset="'.$lang['encoding'].'"><p>';
        $ret .= formSecurityToken(false).
            '<input type="hidden" name="do" value="show" />'.
            '<input type="hidden" name="id" value="'.$ID.'" />'.
            '<label> Version : </label><input class="dokumicrobugtracker__option" name="version" type="text" maxlength="20" value="'.$_REQUEST['version'].'"/>'.
            '<label> Email : </label><input class="dokumicrobugtracker__option" name="email" type="text" value="'.$_REQUEST['email'].'"/></p>'.
            '<p><label> Severity : </label>'.
            '  <select class="element select small dokumicrobugtracker__option" name="severity">';
        $severities = explode(',', $this->getConf('severities'));
        foreach ($severities as $severity) {
            $ret .= '<option value="'.$severity.'" >'.$severity.'</option>';
        }
        $ret .= ' </select></p>'.      
            '<p><label> Description : </label><br /><textarea class="dokumicrobugtracker__option" name="description">'.$_REQUEST['description'].'</textarea></p>';

        if ($this->getConf('use_captcha')==1) {
            $helper = null;
            if(@is_dir(DOKU_PLUGIN.'captcha')) {
                $helper = plugin_load('helper','captcha'); }
            if(!is_null($helper) && $helper->isEnabled()) {
                $ret .= '<p>'.$helper->getHTML().'</p>';}
        }

        $ret .= '<p><input class="button" type="submit" '.
            'value="Report" /></p>'.
            '</form>';

        return $ret;    
    }


    function _show_message($string){
        return "<script type='text/javascript'>
            alert('$string');
        </script>";
    }
}
?>
