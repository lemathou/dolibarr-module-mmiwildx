<?php

require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once '../vendor/autoload.php';

class mmi_wildx_sync
{
	protected static $api_host;
	protected static $api_app_id;
	protected static $api_app_name;
	protected static $api_secret_key;
	
	protected static $client;

	protected static $db;

	public static function __init()
	{
		global $db;
		static::$api_host = getDolGlobalString('MMI_WILDX_HOST');
		static::$api_app_id = getDolGlobalString('MMI_WILDX_APP_ID');
		static::$api_app_name = getDolGlobalString('MMI_WILDX_APP_NAME');
		static::$api_secret_key = getDolGlobalString('MMI_WILDX_SECRET_KEY');

		static::$db = $db;
	}

	public static function client()
	{
		if (!empty(static::$client))
			return static::$client;

		$config = [
			'host' => static::$api_host,
			'app_id' => static::$api_app_id,
			'app_name' => static::$api_app_name,
			'secret_key' => static::$api_secret_key,
		];

		static::$client = new Wildix\Integrations\Client($config, []);
		//var_dump($config, static::$client);
		return static::$client;
	}

	public static function api_query($url, $action='GET', $options=[])
	{
		return static::client()->get($url, $options);
	}

	// Sync

	public static function callHistory($params=[])
	{
		$options = [
			'params' => $params,
			'body' => [
			],
			'headers' => [
			]
		];

		$response = static::api_query('api/v1/PBX/CallHistory/', "GET", $options);
		//$response = $client->get('api/v1/PBX/recordings/', $options);
		//echo $response->getStatusCode(); // 200
		//echo $response->getHeaderLine('content-type'); // 'application/json; charset=utf8'
		//echo "\r\n".$response->getBody()->getContents(); // '{"type": "result", "result": {}}'
		$res = json_decode($response->getBody()->getContents(), true);
		return $res['result']['records'];
	}

	public static function getSocByTel($tel)
	{
		$sql = 'SELECT s.rowid
			FROM `'.MAIN_DB_PREFIX.'societe` s
			LEFT JOIN `'.MAIN_DB_PREFIX.'socpeople` sp ON sp.fk_soc=s.rowid
			WHERE s.phone LIKE "'.$tel.'" OR sp.phone LIKE "'.$tel.'" OR sp.phone_mobile LIKE "'.$tel.'"';
		if (substr($tel, 0, 3) == '+33') {
			$tel2 = '0'.substr($tel, 3);
			$sql .= ' OR s.phone LIKE "'.$tel2.'" OR sp.phone LIKE "'.$tel2.'" OR sp.phone_mobile LIKE "'.$tel2.'"';
		}
		//echo $sql;
		$resql = static::$db->query($sql);
		if ($resql && static::$db->num_rows($resql)) {
			$obj = static::$db->fetch_object($resql);
			return $obj->rowid;
		}
	}

	public static function sync($options=[])
	{
		global $user;

		if (empty($options['ym']))
			$options['ym'] = date('Y-m');
		$ym_begin = $options['ym'];
		$ym_end = date('Y-m', strtotime("+1 month", strtotime($ym_begin.'-01 12:00:00')));
                if (empty($options['start']))
                        $options['start'] = 0;

		$params = [
			//'fields'=>'id,start,answer,end,src,from_number,dst,to_number,disposition,lastapp,duration', //'id,start,end,src,dst'
			'filter' => [
				'start'=>[
					'from'=>$ym_begin.'-01 00:00:00',
					'to'=>$ym_end.'-01 00:00:00'
				],
			],
			'start' => $options['start'],
		];
		//var_dump($params); die();

		$users = [];
		$numbers = [];
		$sql = 'SELECT u.rowid, u.office_phone AS tel2, u2.tel_internal AS tel, u.dateemployment AS date_begin, u.dateemploymentend AS date_end
			FROM `'.MAIN_DB_PREFIX.'user` u
			INNER JOIN `'.MAIN_DB_PREFIX.'user_extrafields` u2 ON u2.fk_object=u.rowid
			WHERE u.office_phone != "" OR (u2.tel_internal IS NOT NULL AND u2.tel_internal != "")
			ORDER BY u.office_phone, u2.tel_internal, u.dateemployment, u.dateemploymentend';
		//echo $sql;
		$resql = static::$db->query($sql);
		if ($resql) {
			while ($row = static::$db->fetch_assoc($resql)) {
				if ($row['tel2']) {
					$row['tel2'] = '+33'.substr(str_replace(' ', '', $row['tel2']), 1);
					$numbers[$row['tel2']][] = $row['rowid'];
				}
				$numbers[$row['tel']][] = $row['rowid'];
				$users[$row['rowid']] = $row;
			}
		}

		$list = static::callHistory($params);
		foreach($list as $e) {
			//var_dump($e);
			$sql = 'SELECT ac2.fk_object AS rowid
				FROM `'.MAIN_DB_PREFIX.'actioncomm_extrafields` ac2
				WHERE ac2.wildx_id = '.$e['id'];
			//echo $sql; die();
			$resql = static::$db->query($sql);
			if ($resql && static::$db->num_rows($resql)) {
				$obj = static::$db->fetch_object($resql);
				$rowid = $obj->rowid;
				//echo 'DEJA';
			}
			else {
				$rowid = NULL;
			}

			// FROM
			$userid = NULL;
			$date = substr($e['start'], 0, 10);
			if (isset($numbers[$e['from_number']])) {
				$label = 'Appel téléphonique sortant';
				$ext_number = $e['to_number'];
				if (isset($numbers[$ext_number])) {
					//echo 'INTERNE';
					continue;
				}
				$fk_soc = static::getSocByTel($ext_number);
				// Search
				foreach($numbers[$e['from_number']] as $uid) {
					$user = $users[$uid];
					if(!$userid || ((!$user['date_begin'] || $user['date_begin'] <= $date) && (!$user['date_end'] || $date <= $user['date_end']))) {
						$userid = $uid;
					}
				}
			}
			// TO
			elseif (isset($numbers[$e['to_number']])) {
				$label = 'Appel téléphonique entrant';
				$ext_number = $e['from_number'];
				if (isset($numbers[$ext_number])) {
					//echo 'INTERNE';
					continue;
				}
				$fk_soc = static::getSocByTel($ext_number);
				// Search
				foreach($numbers[$e['to_number']] as $uid) {
					$user = $users[$uid];
					if(!$userid || ((!$user['date_begin'] || $user['date_begin'] <= $date) && (!$user['date_end'] || $date <= $user['date_end']))) {
						$userid = $uid;
					}
				}
			}
			// Unknown
			else {
				echo '<p>INCONNU : </p>'."\r\n"; var_dump($e);
				continue;
			}
			
			//var_dump($userid);
			//continue;
			
			if(!isset($users[$userid]['object'])) {
				$users[$userid]['object'] = new User(static::$db);
				$users[$userid]['object']->fetch($userid);
			}

			$actionComm = new ActionComm(static::$db);
			if ($rowid)
				$actionComm->fetch($rowid);

			$actionComm->type_code = 'AC_TEL';
			$actionComm->label = $label;
			$actionComm->note_private = $ext_number;
			$actionComm->authorid = $userid;
			$actionComm->userownerid = $userid;
			$actionComm->datep = strtotime($e['start']);
			$actionComm->datef = strtotime($e['end']);
			$actionComm->userassigned[] = $userid;
			$actionComm->percentage = 100;
			//$actionComm->calling_duration = $e['duration'];
			//$actionComm->duree = $e['duration'];

			// All is well !
			if ($fk_soc) {
				$actionComm->socid = $fk_soc;
			}

			$actionComm->array_options['options_wildx_id'] = $e['id'];
			if ($rowid)
				$res = $actionComm->update($users[$userid]['object']);
			else
				$res = $actionComm->create($users[$userid]['object']);
			//var_dump($actionComm);
			//var_dump($res, $actionComm, $actionComm->error); die();
		}
	}

	/**
	 * Normalise telephone formats 
	 */
	public static function fixtels()
	{
		$tablefields = ['societe'=>['phone', 'fax'], 'socpeople'=>['phone', 'phone_perso', 'phone_mobile', 'fax']];
		foreach($tablefields as $table=>$fields) {
			foreach($fields as $field) {
				// 0000... => nada
				$sql = 'UPDATE `'.MAIN_DB_PREFIX.$table.'`
					SET `'.$field.'` = ""
					WHERE `'.$field.'`REGEXP "^[0]+$"';
				echo $sql;
				$resql = static::$db->query($sql);
				echo static::$db->affected_rows($resql);
				if (false) {
					// 06... => +336...
					$sql = 'UPDATE `'.MAIN_DB_PREFIX.$table.'`
						SET `'.$field.'` = CONCAT("+33", SUBSTRING('.$field.', 2))
						WHERE `'.$field.'` REGEXP "^0[1-9]+[+0-9]+$"';
					echo $sql;
					$resql = static::$db->query($sql);
					echo static::$db->affected_rows($resql);
				}
				// 003306... => +336...
				$sql = 'UPDATE `'.MAIN_DB_PREFIX.$table.'`
					SET `'.$field.'` = CONCAT("+", SUBSTRING('.$field.', 3))
					WHERE `'.$field.'` LIKE "00%"';
				echo $sql;
				$resql = static::$db->query($sql);
				echo static::$db->affected_rows($resql);
				// +33(0)6... => +336...
				$sql = 'UPDATE `'.MAIN_DB_PREFIX.$table.'`
					SET `'.$field.'` = CONCAT(SUBSTRING('.$field.', 1, 3), SUBSTRING('.$field.', 7))
					WHERE SUBSTRING('.$field.', 1, 1)="+" AND SUBSTRING('.$field.', 4, 3)="(0)"';
				echo $sql;
				$resql = static::$db->query($sql);
				echo static::$db->affected_rows($resql);
				// 06 12 ... => 0612... , 06-12... => 0612... , 06.12... => 0612...
				$sql = 'UPDATE `'.MAIN_DB_PREFIX.$table.'`
					SET `'.$field.'` = REPLACE(REPLACE(REPLACE('.$field.', " ", ""), ".", ""), "-", "")
					WHERE `'.$field.'` LIKE "% %" OR '.$field.' LIKE "%.%" OR '.$field.' LIKE "%-%"';
				echo $sql;
				$resql = static::$db->query($sql);
				echo static::$db->affected_rows($resql);
				// +3306... => +336...
					$sql = 'UPDATE `'.MAIN_DB_PREFIX.$table.'`
					SET `'.$field.'` = CONCAT("+33", SUBSTRING('.$field.', 5))
					WHERE `'.$field.'` LIKE "+330%"';
				echo $sql;
				$resql = static::$db->query($sql);
				echo static::$db->affected_rows($resql);
				// Reste
				$sql = 'SELECT rowid, `'.$field.'`
					FROM `'.MAIN_DB_PREFIX.$table.'`
					WHERE `'.$field.'` NOT REGEXP "^[+0-9]+$" AND `'.$field.'` != ""';
				echo $sql;
			}
		}
	}

}

mmi_wildx_sync::__init();
