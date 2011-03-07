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
//require_once('FirePHPCore/FirePHP.class.php');
    
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
    
    /**
    * Create output
    */
    function render($mode, &$renderer, $data) {        
        global $ID;
        if ($mode == 'xhtml'){
            
            $renderer->info['cache'] = false;     
   
            //$renderer->doc .= '<h1>Bug tracker is currently broken : i m working on it.</h1>';
            
            // get bugs file contents
            $pfile = metaFN(md5($data['project']), '.bugs');            
            if (@file_exists($pfile))
            	{$bugs  = unserialize(@file_get_contents($pfile));}
            else
            	{$bugs = array();}	          

            //If it s a usr report add it to the pseudo db
            $Generated_Header = '';
            if (($_REQUEST['captcha']) || ($_REQUEST['severity']))
              {
                  if (($_REQUEST['captcha']==$_SESSION['security_number']) || ($this->getConf('use_captcha')==0))
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
                        //Ecriture en pseudo db
                        $fh = fopen($pfile, 'w');
                        fwrite($fh, serialize($bugs));
                        fclose($fh);
                        $Generated_Header = '<div style="border: 3px green solid; background-color: lightgreen; margin: 10px; padding: 10px;">Your report have been successfully stored as bug#'.$bug_id.'</div>';
                        $this->_emailForNewBug($bug_id,$data['project'],$bugs[$bug_id]['version'],$bugs[$bug_id]['severity'],$bugs[$bug_id]['description']);
                        $_REQUEST['description'] = '';
                        }
                    }
              else
                    {
                    $Generated_Header = $_SESSION['security_number'].':<div class ="important">Wrong answer to the antispam question.</div> :'.$_REQUEST['captcha'];
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
    
    function _scripts_render()
    {
        $BASE = DOKU_BASE."lib/plugins/dokumicrobugtracker/";
        return    "<script type=\"text/javascript\" src=\"".$BASE."prototype.js\"></script>        <script type=\"text/javascript\" src=\"".$BASE."fabtabulous.js\"></script>
        <script type=\"text/javascript\" src=\"".$BASE."tablekit.js\"></script>
        <script type=\"text/javascript\">
            TableKit.Sortable.addSortType(new TableKit.Sortable.Type('status', {                    editAjaxURI : '".$BASE."edit.php',                    ajaxURI : '".$BASE."edit.php',
                    pattern : /^[New|In Progress|Closed]$/,
                    normal : function(v) {
                        var val = 3;
                        switch(v) {
                            case 'New':
                                val = 0;
                                break; 
                            case 'Closed':
                                val = 2;
                                break;
                            default:
                                val = 1;
                                break;
                        }
                        return val;
                    }
                }
            ));
            TableKit.options.editAjaxURI = '".$BASE."edit.php';
            TableKit.Editable.multiLineInput('Description');
            var _tabs = new Fabtabs('tabs');
            $$('a.next-tab').each(function(a) {
                Event.observe(a, 'click', function(e){
                    Event.stop(e);
                    var t = $(this.href.match(/#(\w.+)/)[1]+'-tab');
                    _tabs.show(t);
                    _tabs.menu.without(t).each(_tabs.hide.bind(_tabs));
                }.bindAsEventListener(a));
            });
        </script>";
    }

    function _table_render($bugs,$data)
    {
        global $ID;
        if (auth_quickaclcheck($ID) >= AUTH_ADMIN)        
            {            $head = "<div class='dokumicrobugtracker_div'><table id='".$data['project']."' class=\"sortable editable inline\"><thead><tr><td id='id'>Id</td><td id='Status'>Status</td><td id='Severity'>Severity</td><td id='Version'>Version</td><td id='Description'>Description</td><td id='Resolution'>Resolution</td></tr></thead>";        } 
        else       
            {            $head = "<div class='dokumicrobugtracker_div'><table id='".$data['project']."' class=\"sortable inline\"><thead><tr><td id='id'>Id</td><td id='Status'>Status</td><td id='Severity'>Severity</td><td id='Version'>Version</td><td id='Description'>Description</td><td id='Resolution'>Resolution</td></tr></thead>";        }
        
        $body = "<tbody>";
        foreach ($bugs as $bug)
        {
            if (($data['status']=='ALL') || (strtoupper($bug['status'])==$data['status']))
            {
                $body .= '<tr id = "'.$data['project'].' '.$this->_get_one_value($bug,'id').'">'.
                '<td>'.$this->_get_one_value($bug,'id').'</td>'.
                '<td>'.$this->_get_one_value($bug,'status').'</td>'.
                '<td>'.$this->_get_one_value($bug,'severity').'</td>'.
                '<td>'.$this->_get_one_value($bug,'version').'</td>'.
                '<td class="canbreak">'.$this->_get_one_value($bug,'description').'</td>'.
                '<td>'.$this->_get_one_value($bug,'resolution').'</td>'. 
                '</tr>';        
            }
        }
        $body .= '</tbody></table></div>';        
        return $head.$body;
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
            $from=$this->getConf('address_email') ;
            $to=$from;
            mail_send($to, $subject, $body, $from, $cc='', $bcc='', $headers=null, $params=null);
        }
    }

   function _report_render()
    {
        global $lang;
        global $ID;

        $ret = '<br /><h5>Report a new bug</h5><br /><form class="dokumicrobugtracker__form" method="post" action="'.$_SERVER['REQUEST_URI'].'" accept-charset="'.$lang['encoding'].'"><p>';
        $ret .= formSecurityToken(false).
        '<input type="hidden" name="do" value="show" />'.
        '<input type="hidden" name="id" value="'.$ID.'" />'.
        '<label> Version : </label><input class="dokumicrobugtracker__option" name="version" type="text" maxlength="20" value="'.$_REQUEST['version'].'"/></p>'.
        '<p><label> Severity : </label>'.
        '  <select class="element select small dokumicrobugtracker__option" name="severity">'.
        '       <option value="Minor Bug" >Minor Bug</option>'.
        '       <option value="Feature Request" >Feature Request</option>'.
        '       <option value="Major Bug" >Major Bug</option>'.
        '	 </select></p>'.      
        '<p><label> Description : </label><br /><textarea class="dokumicrobugtracker__option" name="description">'.$_REQUEST['description'].'</textarea></p>';

        if ($this->getConf('use_captcha')==1)  
            $ret .= '<p><img src="'.DOKU_BASE.'lib/plugins/dokumicrobugtracker/image.php" alt="captcha" /><label>what s the result? </label><input class="dokumicrobugtracker__option" name="captcha" type="text" maxlength="3" value=""/></p>';      


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
