<?php

/**
 * This file is part of playSMS.
 *
 * playSMS is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * playSMS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with playSMS. If not, see <http://www.gnu.org/licenses/>.
 */
defined('_SECURE_') or die('Forbidden');

if (!auth_isvalid()) {
	auth_block();
}

switch (_OP_) {
	case "sendsms" :
		
		// get $to and $message from session or query string
		$to = stripslashes($_REQUEST['to']);
		$message = (stripslashes($_REQUEST['message']) ? stripslashes($_REQUEST['message']) : trim(stripslashes($_SESSION['tmp']['message'])));
		
		// sender ID
		$sms_from = sendsms_get_sender($user_config['username']);
		$ismatched = FALSE;
		foreach (sender_id_getall($user_config['username']) as $sender_id ) {
			$selected = '';
			if (strtoupper($sms_from) == strtoupper($sender_id)) {
				$selected = 'selected';
				$ismatched = TRUE;
			}
			$option_values .= "<option value=\"" . $sender_id . "\" title=\"" . $sender_id . "\" " . $selected . ">" . $sender_id . "</option>";
		}
		$sms_sender_id = "<select name=sms_sender style='width: 100%'>" . $option_values . "</select>";
		
		if (!$ismatched) {
			$sms_sender_id = "<input type='text' style='width: 100%' name='sms_sender' value='" . $sms_from . "' readonly>";
		}
		
		// SMS footer
		$sms_footer = $user_config['footer'];
		
		// message template
		$option_values = "<option value=\"\" default>--" . _('Please select template') . "--</option>";
		$c_templates = sendsms_get_template();
		for($i = 0; $i < count($c_templates); $i++) {
			$option_values .= "<option value=\"" . $c_templates[$i]['text'] . "\" title=\"" . $c_templates[$i]['text'] . "\">" . $c_templates[$i]['title'] . "</option>";
			$input_values .= "<input type=\"hidden\" name=\"content_" . $i . "\" value=\"" . $c_templates[$i]['text'] . "\">";
		}
		if ($c_templates[0]) {
			$sms_template = "<div id=msg_template><select name=smstemplate id=msg_template_select style='width: 100%' onClick=\"SetSmsTemplate();\">$option_values</select></div>";
		}
		
		$content = '';
		if ($err = $_SESSION['error_string']) {
			$error_content = "<div class=error_string>$err</div>";
		}
		
		// build form
		unset($tpl);
		$tpl = array(
			'name' => 'sendsms',
			'vars' => array(
				'Send message' => _('Send message'),
				'Sender ID' => _('Sender ID'),
				'Message footer' => _('Message footer'),
				'Send to' => _('Send to'),
				'Message' => _('Message'),
				'Flash message' => _('Flash message'),
				'Unicode message' => _('Unicode message'),
				'Send' => _('Send'),
				'Schedule' => _('Schedule'),
				'Options' => _('Options'),
				'ERROR' => $error_content,
				'HTTP_PATH_BASE' => _HTTP_PATH_BASE_,
				'HTTP_PATH_THEMES' => _HTTP_PATH_THEMES_,
				'HINT_SEND_TO' => _('Prefix with # for groups and @ for users'),
				'HINT_SCHEDULE' => _('Format YYYY-MM-DD hh:mm'),
				'sms_from' => $sms_from,
				'sms_footer' => $sms_footer,
				'allow_custom_footer' => $allow_custom_footer,
				'to' => $to,
				'sms_sender_id' => $sms_sender_id,
				'sms_template' => $sms_template,
				
				// 'sms_schedule' => core_display_datetime(core_get_datetime()),
				'sms_schedule' => '',
				'message' => $message,
				'sms_footer_length' => $user_config['opt']['sms_footer_length'],
				'per_sms_length' => $user_config['opt']['per_sms_length'],
				'per_sms_length_unicode' => $user_config['opt']['per_sms_length_unicode'],
				'max_sms_length' => $user_config['opt']['max_sms_length'],
				'max_sms_length_unicode' => $user_config['opt']['max_sms_length_unicode'],
				'lang' => substr($user_config['language_module'], 0, 2),
				'chars' => _('chars'),
				'SMS' => _('SMS') 
			),
			'ifs' => array(
				'calendar' => file_exists($core_config['apps_path']['themes'] . '/common/jscss/bootstrap-datetimepicker/bootstrap-datetimepicker.' . substr($user_config['language_module'], 0, 2) . '.js'),
				'combobox' => file_exists($core_config['apps_path']['themes'] . '/common/jscss/combobox/select2_locale_' . substr($user_config['language_module'], 0, 2) . '.js') 
			) 
		);
		_p(tpl_apply($tpl));
		break;
	
	case "sendsms_yes" :
		
		// sender ID
		if ($core_config['main']['allow_custom_sender']) {
			$sms_sender = trim($_REQUEST['sms_sender']);
		} else {
			$sms_sender = sendsms_get_sender($user_config['username']);
		}
		
		// SMS footer
		if ($core_config['main']['allow_custom_footer']) {
			$sms_footer = trim($_REQUEST['sms_footer']);
		} else {
			$sms_footer = $user_config['footer'];
		}
		
		// nofooter option
		$nofooter = true;
		if ($sms_footer) {
			$nofooter = false;
		}
		
		// schedule option
		$sms_schedule = trim($_REQUEST['sms_schedule']);
		
		// type of SMS, text or flash
		$msg_flash = $_REQUEST['msg_flash'];
		$sms_type = "text";
		if ($msg_flash == "on") {
			$sms_type = "flash";
		}
		
		// unicode or not
		$msg_unicode = $_REQUEST['msg_unicode'];
		$unicode = "0";
		if ($msg_unicode == "on") {
			$unicode = "1";
		}
		
		// SMS message
		$message = $_REQUEST['message'];
		
		// save it in session for next form
		$_SESSION['tmp']['message'] = $message;
		
		// destination numbers
		if ($sms_to = trim($_REQUEST['p_num_text'])) {
			$sms_to = explode(',', $sms_to);
		}
		
		if ($sms_to[0] && $message) {
			
			list($ok, $to, $smslog_id, $queue, $counts, $sms_count, $sms_failed) = sendsms_helper($user_config['username'], $sms_to, $message, $sms_type, $unicode, '', $nofooter, $sms_footer, $sms_sender, $sms_schedule, $reference_id);
			
			$_SESSION['error_string'] = _('Your message has been delivered to queue') . " (" . _('queued') . ":" . (int) $sms_count . " " . _('failed') . ":" . (int) $sms_failed . ")";
		} else {
			$_SESSION['error_string'] = _('You must select receiver and your message should not be empty');
		}
		header("Location: " . _u('index.php?app=main&inc=core_sendsms&op=sendsms'));
		exit();
		break;
}
