<?php

require_once(realpath(dirname(__FILE__)).'/../../../inc/init.php');

// POST Sent by the edited array

//    * &row_id=row_id: The project + id attribute of the row, it may be useful to set this to the record ID you are editing 
//    * &field=field: The id attribute of the header cell of the column of the edited cell, it may be useful to set this to the field name you are editing
//    * &value=xxxxxx: The rest of the POST body is the serialised form. The default name of the field is 'value'.

global $ID;
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

if (auth_quickaclcheck($ID) < AUTH_ADMIN) {
    die;
}

function emailForChange($email, $id,$project,$field, $oldvalue,$value)
{
        global $conf;
        if (mail_isvalid($email)) {

            
            if ($conf['plugin']['dokumicrobugtracker']['email_subject_template']== '')
               {$conf['plugin']['dokumicrobugtracker']['email_subject_template'] = "The bug report @ID@ in the project @PROJECT@ changed";}
            if ($conf['plugin']['dokumicrobugtracker']['email_body_template']== '')
                {$conf['plugin']['dokumicrobugtracker']['email_body_template'] = "Hi, \n\nYou receive this bug change notification as you report the bug #@ID@ in the project '@PROJECT@'.\n\n--------------------------------------------------------------------------------\n\nWhat : @FIELD@\n\nRemoved: @OLDVALUE@\n\nAdded: @VALUE@";}

            $subject = $conf['plugin']['dokumicrobugtracker']['email_subject_template'];
            $body = $conf['plugin']['dokumicrobugtracker']['email_body_template'];
            
            $body =  str_replace('\n', "\n", $body);
            foreach (array('ID' => $id,
                           'PROJECT' => $project,
                           'FIELD' => $field,
                           'OLDVALUE' => $oldvalue,
                           'VALUE'  => $value,) as $var => $val) {
                    $body = str_replace('@' . $var . '@', $val, $body);
                    $subject = str_replace('@' . $var . '@', $val, $subject);
                }

            $from= $conf['plugin']['dokumicrobugtracker']['email_address'];

            $to=$email;

            mail_send($to, $subject, $body, $from, $cc='', $bcc='', $headers=null, $params=null);
        }
}
    
function metaFN2($id,$ext){    global $conf;    $id = cleanID($id);    $id = str_replace(':','/',$id);    $fn = $conf['metadir'].'/'.utf8_encodeFN($id).$ext;    return $fn;}

$exploded = explode(' ',htmlspecialchars(stripslashes($_POST['row_id'])));
$project = $exploded[0];
$id_bug = intval($exploded[1]);

// get bugs file contents
$pfile = metaFN2(md5($project), '.bugs');
if (@file_exists($pfile))
    {$bugs  = unserialize(@file_get_contents($pfile));}
else 
    {$bugs = array();}


$field = strtolower(htmlspecialchars($_POST['field']));
$value = htmlspecialchars(stripslashes($_POST['value']));


if (($field == 'status') || ($field == 'severity') || ($field == 'version') || ($field == 'description')|| ($field == 'resolution') || ($field == 'delete') && (auth_isadmin()==1))
    {
        if ($field == 'delete')
            {unset($bugs[$id_bug]);echo 'Deleted : ' . $id_bug;}
    else
        {
        emailForChange($bugs[$id_bug]['author'],$id_bug, $project, $field, $bugs[$id_bug][$field], $value);
        $bugs[$id_bug][$field]=$value;
        }
    }

// Save bugs file contents
$fh = fopen($pfile, 'w');
fwrite($fh, serialize($bugs));
fclose($fh);

	echo $_POST['value'];
?>
