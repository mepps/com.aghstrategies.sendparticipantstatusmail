<?php

require_once 'sendparticipantstatusmail.civix.php';


/**
 * Implementation of hook_civicrm_post
 */
function sendparticipantstatusmail_civicrm_post( $op, $objectName, $objectId, &$objectRef ) {
$custom_statuses = array(16, 14, 15); // for GEF
  if ($objectName=='Participant'){
    if ($op=='edit' or $op=='create'){
    $custom = FALSE;
      if (in_array($objectRef->status_id, $custom_statuses)){
        $custom = TRUE;  
      }    
      if (process_status_update($objectRef->id, $objectRef->status_id, $custom)){
      //figure out correct message
      $messageTemplateID = NULL;
      switch($objectRef->status_id){
        case 16:  //approvedwithoutfunding
         $messageTemplateID = 59;
         break;
       case 14: //approvedwithfunding
         $messageTemplateID = 61;
         break;
       case 15: //declined
         $messageTemplateID = 60;       
        break;       
      }
        $contact_id = $objectRef->contact_id;
        //get all required contacts detail.
          // get the contact details.
#          $contactIds = array($contact_id => $contact_id);
#          list($currentContactDetails) = CRM_Utils_Token::getTokenDetails($contactIds, NULL,
#            FALSE, FALSE, NULL,
#            array(),
#            'CRM_Event_BAO_Participant'
#          );


          //get the domain values.
          if (empty($domainValues)) {
            // making all tokens available to templates.
            $domain = CRM_Core_BAO_Domain::getDomain();
            $tokens = array('domain' => array('name', 'phone', 'address', 'email'),
              'contact' => CRM_Core_SelectValues::contactTokens(),
            );
           }
           foreach ($tokens['domain'] as $token) {
              $domainValues[$token] = CRM_Utils_Token::getDomainTokenReplacement($token, $domain);
            }
            $params = array(
              'version' => 3,
              'sequential' => 1,
              'id' => $contact_id,
            );
            $result = civicrm_api('Contact', 'getsingle', $params);

           $contact = $result;          
           $participant_bao = new CRM_Event_BAO_Participant;
           $participantId = $objectRef->id;
            $params = array(
              'version' => 3,
              'sequential' => 1,
              'id' => $participant_id,
            );
            $result = civicrm_api('Participant', 'getsingle', $params);            
            $participant = $result;            
            $params = array(
              'version' => 3,
              'sequential' => 1,
              'id' => $objectRef->event_id,
            );
            $result = civicrm_api('Event', 'getsingle', $params);            
            $event = $result;
            $event['start_date'] = date('F j, Y', $event['start_date']);
            $event['end_date'] = date('F j, Y', $event['end_date']);

          list($mailSent, $subject, $message, $html) = CRM_Core_BAO_MessageTemplates::sendTemplate(
            array(
             'messageTemplateID' => $messageTemplateID,
              'contactId' => $contact_id,
              'tplParams' => array(
                'contact' => $contact,
                'domain' => $domainValues,
                'participantStatus' => 'yes',
                'participant' => $participant,
                'event' => $event,
#                'paidEvent' => 0,
#                'isShowLocation' => 0,
#                'isAdditional' => 0,
#                'checksumValue' => $checksumValue,
              ),
//              'from' => '"'.$domainValues['name'].'"<'.$domainValues['email'].'>',
              'from' => '"'.$domainValues['name'].'"<assembly@thegef.org>',       
              'toName' => $contact['display_name'],
              'toEmail' => $contact['email'],
#              'cc' => '',
#              'bcc' => '',
            )
          );
      }
    }
  }
}

/*finds out if status has been changed*/
function process_status_update($participant_id, $status_id, $custom){
    $send_email = FALSE;
    $changed_status = FALSE;
    $query = 'SELECT participant_id, status_id FROM civicrm_save_participant_status WHERE participant_id='.$participant_id;
    $dao = CRM_Core_DAO::executeQuery($query);
    if ($dao->fetch()){
      if ($dao->status_id != $status_id){
        if ($custom){
          $send_email = TRUE;
        }
        $query = 'UPDATE civicrm_save_participant_status SET status_id='.$status_id.', updated_at=NOW() WHERE participant_id='.$participant_id.';';      
        $dao = CRM_Core_DAO::executeQuery($query);
      }
    }
    elseif ($custom){
      $query = 'INSERT INTO civicrm_save_participant_status (participant_id, status_id, updated_at) VALUES('.$participant_id.', '.$status_id.', NOW());';  
      $dao = CRM_Core_DAO::executeQuery($query);
      $send_email = TRUE;
    }
    return $send_email;
}
/**
 * Implementation of hook_civicrm_config
 */
function sendparticipantstatusmail_civicrm_config(&$config) {
  _sendparticipantstatusmail_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function sendparticipantstatusmail_civicrm_xmlMenu(&$files) {
  _sendparticipantstatusmail_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function sendparticipantstatusmail_civicrm_install() {
  $query = 'CREATE TABLE civicrm_save_participant_status (participant_id integer(255), status_id integer(255), updated_at datetime);';
  $dao = CRM_Core_DAO::executeQuery($query);
  return _sendparticipantstatusmail_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function sendparticipantstatusmail_civicrm_uninstall() {
  return _sendparticipantstatusmail_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function sendparticipantstatusmail_civicrm_enable() {
  return _sendparticipantstatusmail_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function sendparticipantstatusmail_civicrm_disable() {
  return _sendparticipantstatusmail_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function sendparticipantstatusmail_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _sendparticipantstatusmail_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function sendparticipantstatusmail_civicrm_managed(&$entities) {
  return _sendparticipantstatusmail_civix_civicrm_managed($entities);
}
