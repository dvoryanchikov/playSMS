<?php
defined('_SECURE_') or die('Forbidden');
if(!isadmin()){forcenoaccess();};

switch ($op) {
	case "all_inbox":
		$base_url = 'index.php?app=menu&inc=all_inbox&op=all_inbox';
		$search = themes_search($base_url);
		$fields = array('in_hidden' => 0);
		if ($kw = $search['keyword']) {
			$keywords = array(
				'username' => '%'.$kw.'%',
				'in_msg' => '%'.$kw.'%',
				'in_sender' => '%'.$kw.'%',
				'in_datetime' => '%'.$kw.'%');
		}
		$join = 'INNER JOIN '._DB_PREF_.'_tblUser AS B ON in_uid=B.uid';
		$count = dba_count(_DB_PREF_.'_tblUserInbox', $fields, $keywords, '', $join);
		$nav = themes_nav($count, $search['url']);
		$extras = array('ORDER BY' => 'in_id DESC', 'LIMIT' => $nav['limit'], 'OFFSET' => $nav['offset']);
		$list = dba_search(_DB_PREF_.'_tblUserInbox', $fields, $keywords, $extras, $join);

		$actions_box = "
			<table width=100% cellpadding=0 cellspacing=0 border=0>
			<tbody><tr>
				<td width=100% align=left>".$nav['form']."</td>
				<td>&nbsp;</td>
				<td><input type=submit name=go value=\""._('Export as CSV')."\" class=button /></td>
				<td><input type=submit name=go value=\""._('Delete selection')."\" class=button /></td>
			</tr></tbody>
			</table>";

		$content = "
			<h2>"._('All inbox')."</h2>
			<p>".$search['form']."</p>
			<form name=\"fm_inbox\" action=\"index.php?app=menu&inc=all_inbox&op=actions\" method=post onSubmit=\"return SureConfirm()\">
			".$actions_box."
			<table cellpadding=1 cellspacing=2 border=0 width=100% class=\"sortable\">
			<thead>
			<tr>
				<th align=center width=4>*</th>
				<th align=center width=10%>"._('User')."</th>
				<th align=center width=20%>"._('Time')."</th>
				<th align=center width=10%>"._('From')."</th>
				<th align=center width=60%>"._('Message')."</th>
				<th width=4 class=\"sorttable_nosort\"><input type=checkbox onclick=CheckUncheckAll(document.fm_inbox)></td>
			</tr>
			</thead>
			<tbody>";

		$i = $nav['top'];
		$j = 0;
		for ($j=0;$j<count($list);$j++) {
			$in_username = $list[$j]['username'];
			$in_msg = core_display_text($list[$j]['in_msg'], 25);
			$list[$j] = core_display_data($list[$j]);
			$in_id = $list[$j]['in_id'];
			$in_sender = $list[$j]['in_sender'];
			$p_desc = phonebook_number2name($in_sender);
			$current_sender = $in_sender;
			if ($p_desc) {
				$current_sender = "$in_sender<br>($p_desc)";
			}
			$in_datetime = core_display_datetime($list[$j]['in_datetime']);
			$i--;
			$td_class = ($i % 2) ? "box_text_odd" : "box_text_even";
			$content .= "
				<tr>
					<td valign=top class=$td_class align=left>$i.</td>
					<td valign=top class=$td_class align=center>$in_username</td>
					<td valign=top class=$td_class align=center>$in_datetime</td>
					<td valign=top class=$td_class align=center>$current_sender</td>
					<td valign=top class=$td_class align=left>$in_msg</td>
					<td class=$td_class width=4>
						<input type=hidden name=itemid".$j." value=\"$in_id\">
						<input type=checkbox name=checkid".$j.">
					</td>
				</tr>";
		}

		$content .= "
			</tbody>
			</table>
			".$actions_box."
			</form>";

		if ($err = $_SESSION['error_string']) {
			echo "<div class=error_string>$err</div><br><br>";
		}
		echo $content;
		break;
	case "actions":
		$nav = themes_nav_session();
		$search = themes_search_session();
		$go = $_REQUEST['go'];
		switch ($go) {
			case _('Export as CSV'):
				$fields = array('in_hidden' => 0);
				if ($kw = $search['keyword']) {
					$keywords = array(
						'username' => '%'.$kw.'%',
						'in_msg' => '%'.$kw.'%',
						'in_sender' => '%'.$kw.'%',
						'in_datetime' => '%'.$kw.'%');
				}
				$join = 'INNER JOIN '._DB_PREF_.'_tblUser AS B ON in_uid=B.uid';
				$list = dba_search(_DB_PREF_.'_tblUserInbox', $fields, $keywords, '', $join);
				$data[0] = array(_('User'), _('Time'), _('From'), _('Message'));
				for ($i=0;$i<count($list);$i++) {
					$j = $i + 1;
					$data[$j] = array(
						$list[$i]['username'],
						core_display_datetime($list[$i]['in_datetime']),
						$list[$i]['in_sender'],
						$list[$i]['in_msg']);
				}
				$content = csv_format($data);
				$fn = 'all_inbox-'.$core_config['datetime']['now_stamp'].'.csv';
				download($content, $fn, 'text/csv');
				break;
			case _('Delete selection'):
				for ($i=0;$i<$nav['limit'];$i++) {
					$checkid = $_POST['checkid'.$i];
					$itemid = $_POST['itemid'.$i];
					if(($checkid=="on") && $itemid) {
						$up = array('c_timestamp' => mktime(), 'in_hidden' => '1');
						dba_update(_DB_PREF_.'_tblUserInbox', $up, array('in_id' => $itemid));
					}
				}
				$ref = $nav['url'].'&search_keyword='.$search['keyword'].'&page='.$nav['page'].'&nav='.$nav['nav'];
				$_SESSION['error_string'] = _('Selected incoming SMS has been deleted');
				header("Location: ".$ref);
		}
		break;
}

?>