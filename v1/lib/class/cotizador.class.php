<?php

class cotizadorIls extends general_functions
{
	public $familyPlan = 21;
	public function Quote($data)
	{
		return $this->validPlansToQuote($data);
	}
	public static function truncateFloat($number, $digits = 3)
	{
		$miles = ($digitos == 3) ? '' : $miles;
		$number = preg_replace('/[^0-9.]/', '', $number);
		$number = ($number != 0) ? round($number - 5 * pow(10, -($digits + 1)), $digits) : 0;
		return (float) number_format($number, $digits, '.', '');
	}
	public function GetPlansToQuote($data)
	{
		$query = "SELECT
				plans.id,
				plans.unidad,
				REPLACE(plans.description,'''','') AS description,
				REPLACE(plans.name,'''','') AS name,
				plans.min_tiempo,
				plans.max_tiempo,
				plans.id_currence,
				plans.normal_age,
				plans.max_age,
				plans.min_age,
				plans.overage_factor,
				plans.overage_factor_cost,
				plans.family_plan,
				plans.factor_family,
				plans.factor_family_cost,
				plans.family_plan_cantidad,
				plans.num_pas,
				plans.plan_pareja,
				plans.factor_pareja,
				plans.factor_pareja_cost,
				plans.plan_local,
				restriction.id_restric,
				restriction.dirigido,
				restriction.id_territory_origen,
				restriction.id_territory_destino,
				restriction.id_broker
            FROM plans
				LEFT JOIN restriction ON plans.id = restriction.id_plans";
		$where[] = " plans.activo = '1' ";
		$where[] = " eliminado = '1' ";
		$data['category'] = is_array($data['category']) ? implode("','", $data['category']) : $data['category'];
		//$where[]= " CONCAT(plans.id,'-',plans.prefijo) = '1303-BA' ";
		$where[] = " plans.id_plan_categoria in ('{$data['category']}') ";
		$where[] = " IFNULL(modo_plan,'')<>'W' ";
		$where[] = " ('{$data['days']}' BETWEEN plans.min_tiempo AND plans.max_tiempo) ";
		if (!empty($data['planLocal'])) {
			$where[] = " plans.plan_local =  '{$data['planLocal']}' ";
		}
		// if (!empty($data['prefijo'])) {
		// 	$data['prefijo'] = is_array($data['prefijo']) ? implode("','", $data['prefijo']) : $data['prefijo'];
		// 	$where[] = " plans.prefijo in('{$data['prefijo']}') ";
		// }
		if (!empty($data['multiDays'])) {
			$where[] = " plans.dias_multiviajes  = '{$data['multiDays']}' ";
		}
		$query .= (count($where) > 0 ? " WHERE " . implode(' AND ', $where) : " ");

		$dataCurl = [
			'querys' => $query
		];

		$link 		= $this->selectDynamic(['prefix' => $data['prefijo']], 'clients', "data_activa='si'", ['web'])[0]['web'];
		$linkParam 	= $link . "/app/api/selectDynamic";
		$headers 	= "content-type: application/x-www-form-urlencoded";
		$respons    = $this->curlGeneral($linkParam, json_encode($dataCurl), $headers);
		$response   = json_decode($respons, true);
		for ($i = 0; $i < count($response); $i++) {
			$response[$i]['prefijo'] = $data['prefijo'];
		}
		return $response;
		//return $this->_SQL_tool($this->SELECT, __METHOD__, $query);
	}
	public function getCurrencyPlan($currency)
	{
		$currency = is_array($currency) ? implode("','", $currency) : $currency;
		$query = "SELECT currency.id_currency,currency.value_iso FROM currency WHERE currency.id_currency in('{$currency}')";
		 $response = $this->_SQL_tool($this->SELECT, __METHOD__, $query);
		 foreach ($response as $value) {
			 $response[$value['id_currency']] = $value;
		 }
	}
	public function getBrokerPlan($broker)
	{
		$broker = is_array($broker) ? implode("','", $broker) : $broker;
		$query = "SELECT CONCAT(id_broker,'-',prefijo) ids,broker from broker where CONCAT(id_broker,'-',prefijo) IN('{$broker}')";
		return $this->setValRow('ids')->_SQL_tool($this->SELECT, __METHOD__, $query);
	}
	public function getCountriRestrictionPlan($restrictIdsPref)
	{
		$restrictIdsPref = is_array($restrictIdsPref) ? implode("','", $restrictIdsPref) : $restrictIdsPref;
		$query = "SELECT
				CONCAT(relaciotn_restriction.id_restric,'-',relaciotn_restriction.prefijo) AS IdsPref,
				GROUP_CONCAT(DISTINCT iso_country) AS country
			FROM
				relaciotn_restriction
			WHERE
				CONCAT(relaciotn_restriction.id_restric,'-',relaciotn_restriction.prefijo) 
				in('{$restrictIdsPref}')";
		return $this->setValRow('IdsPref')->_SQL_tool($this->SELECT, __METHOD__, $query);
	}
	public function getTitlePlan($planIdsPref)
	{
		$lenguaje = in_array($_SESSION['lng_id'], ['spa', 'eng']) ? $_SESSION['lng_id'] : "spa";
		$planIdsPref = is_array($planIdsPref) ? implode("','", $planIdsPref) : $planIdsPref;
		$query = "SELECT
				CONCAT(plan_detail.plan_id,'-',plan_detail.prefijo) as IdsPref,
				plan_detail.titulo
			FROM
				plan_detail
			WHERE
			CONCAT(plan_detail.plan_id,'-',plan_detail.prefijo) in('{$planIdsPref}')
			AND plan_detail.language_id = '{$lenguaje}'";
		return $this->setValRow('IdsPref')->_SQL_tool($this->SELECT, __METHOD__, $query);
	}
	public function validPlansToQuote($quote)
	{
		$clients = $this->GetClints($quote['prefijo']);
		$quote['cantidad'] = count($quote['edades']);
		$quote['prefijo'] = $quote['prefijo'] ?: array_keys($clients);
		$rest = $this->GetPlansToQuote($quote);

		//Armamos nuevos arrays para poder $realizar la consulta.
		foreach ($rest as &$value) {
			$planIdsPref[] = $value['planIdPrefix'] = $value['id'] . '-' . $value['prefijo'];
			$restrictIdsPref[] = $value['restrictIdPrefix'] = $value['id_restric'] . '-' . $value['prefijo'];
			$currency[] = $value['id_currence'];
			if (!empty($value['id_broker'])) {
				$broker[] = $value['id_broker'] . '-' . $value['prefijo'];
			}
		}
		$broker = !empty($broker) ? $this->getBrokerPlan($broker) : [];
		$currency = !empty($currency) ? $this->getCurrencyPlan($currency) : [];
		$territory_origen = !empty($restrictIdsPref) ? $this->getCountriRestrictionPlan($restrictIdsPref) : [];
		$planIdsPref = implode("','", $planIdsPref);
		$plan_detail = !empty($planIdsPref) ? $this->getTitlePlan($planIdsPref) : [];
		$planTime = !empty($planIdsPref) ? $this->GetPlanTime($planIdsPref, $quote) : [];
		$destino = $this->setValRow('id')->GetDestino($quote);
		foreach ($rest as &$value) {
			$value['currency_iso'] = $currency[$value['id_currence']]['value_iso'] ?: "US$";
			$value['client'] = $clients[$value['prefijo']];
			$value['title'] = $plan_detail[$value['planIdPrefix']]['titulo'] ?: $value['description'];
			$value['territory_origen'] = !empty($territory_origen[$value['restrictIdPrefix']]['country']) ? explode(',', $territory_origen[$value['restrictIdPrefix']]['country']) : [];
			$value['prima'] = $planTime[$value['planIdPrefix']] ?: [];
			$value['brokerName'] = $broker[$value['id_broker'] . '-' . $value['prefijo']]['broker'] ?: NULL;
			$value['errors'] = [];
			$value['destinoName'] = $destino[$value['id_territory_destino']]['name'];
			$Plan['plan'] = [];
			$this->restriction($value, $quote);
			$this->calculatePremium($value, $quote);
			$this->calculateFinalPrice($value, $quote);
		}
		return $rest;
	}
	public function calculateFinalPrice(&$Plan, $quote)
	{
		$Plan['premium']['totalFinal'] = $Plan['premium']['TotalBase'];
		$Plan['premium']['costFinal'] = $Plan['premium']['CostBase'];
		$Plan['grupalFactor']['familyApplied'] = 'N';
		$Plan['grupalFactor']['coupleApplied'] = 'N';
		if ($Plan['family']['active'] == 'Y' && $quote['cantidad'] >= 3 && $Plan['age']['overAgeYes'] == 0) {
			$Plan['grupalFactor']['familyApplied'] = 'Y';
			if ($Plan['age']['familyAgeOlder'] == 2 && $Plan['family']['maxYounger'] >= $Plan['age']['familyAgeYounger']) {
				$Plan['premium']['totalFinal'] = ($Plan['premium']['TotalBase'] * $Plan['family']['factorTotal']) / $quote['cantidad'];
				$Plan['premium']['costFinal'] = ($Plan['premium']['CostBase'] * $Plan['family']['factorCost']) / $quote['cantidad'];
			}
		}
		if ($Plan['couple']['active'] == 'Y' && $quote['cantidad'] == 2 && $Plan['age']['overAgeYes'] == 0) {
			$Plan['grupalFactor']['coupleApplied'] = 'Y';
			$Plan['premium']['totalFinal'] = ($Plan['premium']['TotalBase'] * $Plan['couple']['factorTotal']) / $quote['cantidad'];
			$Plan['premium']['costFinal'] = ($Plan['premium']['CostBase'] * $Plan['couple']['factorCost']) / $quote['cantidad'];
		}
		$Plan['premium']['totalFinalOverAge'] = $Plan['premium']['totalFinal'];
		$Plan['premium']['costFinalOverAge'] = $Plan['premium']['costFinal'];
		if (!in_array('Y', [$Plan['grupalFactor']['familyApplied'], $Plan['grupalFactor']['coupleApplied']])) {
			$Plan['premium']['totalFinalOverAge'] *= $Plan['overAge']['factorTotal'];
			$Plan['premium']['costFinalOverAge'] *= $Plan['overAge']['factorCost'];
		}
		$Plan['premium']['TotalBase'] = self::truncateFloat($Plan['premium']['TotalBase']);
		$Plan['premium']['CostBase'] = self::truncateFloat($Plan['premium']['CostBase']);

		$Plan['premium']['totalFinal'] = self::truncateFloat($Plan['premium']['totalFinal']);
		$Plan['premium']['costFinal'] = self::truncateFloat($Plan['premium']['costFinal']);

		$Plan['premium']['totalFinalOverAge'] = self::truncateFloat($Plan['premium']['totalFinalOverAge']);
		$Plan['premium']['costFinalOverAge'] = self::truncateFloat($Plan['premium']['costFinalOverAge']);

		$Plan['totalShow'] = ($Plan['premium']['totalFinalOverAge'] * $Plan['age']['overAgeYes']) + ($Plan['premium']['totalFinal'] * $Plan['age']['overAgeNo']);
		$Plan['costShow'] = ($Plan['premium']['costFinalOverAge'] * $Plan['age']['overAgeYes']) + ($Plan['premium']['costFinal'] * $Plan['age']['overAgeNo']);
		$Plan['totalShow'] = self::truncateFloat($Plan['totalShow']);
		$Plan['costShow'] = self::truncateFloat($Plan['costShow']);
	}
	public function calculatePremium(&$Plan, $quote)
	{
		$days = (int) $quote['days'];
		$primaCountry = $Plan['prima'][$quote['origenQuote']] ?: $Plan['prima']['all'];
		foreach ($primaCountry['primaBase'] as $unidad => $prima) {
			if ($Plan['plan']['unidad'] == $unidad) {
				$Plan['premiumUsed'][] = $prima['id'];
				switch ($unidad) {
					case 'dias':
						$Plan['premium']['TotalBase'] = (float) $prima['valor'] * ((int) $prima['tiempo'] == 365 ? 1 : $days);
						$Plan['premium']['CostBase'] = (float) $prima['cost'] * ((int) $prima['tiempo'] == 365 ? 1 : $days);
						$days -= (int) $prima['tiempo'];
						break;
					case 'bloques':
						$Plan['premium']['TotalBase'] = (float) $prima['valor'];
						$Plan['premium']['CostBase'] = (float) $prima['cost'];
						$days -= (int) $prima['tiempo'];
						break;
					case 'meses':
						$Plan['premium']['TotalBase'] = (float) $prima['valor'];
						$Plan['premium']['CostBase'] = (float) $prima['cost'];
						$days -= ((int) $prima['tiempo'] * 30);
						break;
					default:
						$Plan['premium']['TotalBase'] = 'Tipo de calculo no soportado';
						$Plan['premium']['CostBase'] = 'Tipo de calculo no soportado';
						$Plan['errors'][] = "Tipo de calculo no soportado";
						$days = 0;
						break;
				}
			} else {
				$Plan['errors'][] = "Configuracion Plan :{$Plan['plan']['unidad']} - Prima :{$unidad}";
			}
		}
		$premiumAditional = [
			'dias' => ['dias'],
			'bloques' => ['bloques', 'dias', 'semanas'],
			'meses' => ['meses', 'semanas']
		];
		if ($days > 0 && is_numeric($Plan['premium']['TotalBase'])) {
			foreach ($primaCountry['primaAdicional'] as $unidad => $prima) {
				if (in_array($unidad, $premiumAditional[$Plan['plan']['unidad']])) {
					switch ($unidad) {
						case 'dias':
							$cantidad = $days;
							$days = 0;
							break;
						case 'bloques':
							$cantidad = ceil($days / $prima['tiempo']);
							$days = 0;
							break;
						case 'meses':
							$cantidad = floor($days / ($prima['tiempo'] * 30));
							$days -= ($cantidad * 30);
							break;
						case 'semanas':
							$cantidad = ceil($days / ($prima['tiempo'] * 7));
							$days = 0;
							break;
					}
					$Plan['cantidadAdicinal'] = $cantidad;
					if ($cantidad > 0) {
						$Plan['premiumUsed'][] = $prima['id'];
						$Plan['premium']['TotalBase'] += $prima['valor'] * $cantidad;
						$Plan['premium']['CostBase'] += $prima['cost'] * $cantidad;
					}
				} else {
					$Plan['errors'][] = "Prima adicional invalida ({$unidad})";
				}
			}
		}
		if ($days > 0) {
			$Plan['errors'][] = "Dias no cubiertos {$days}";
		}
	}
	public function restriction(&$Plan, $quote)
	{
		global $CORE_lang;
		$restriction = [
			'1' => $CORE_lang['txttodos'],
			'2' => $CORE_lang['txtagentespec'],
			'3' => $CORE_lang['txtonlyagents'],
			'4' => $CORE_lang['txtonlycorp'],
			'5' => $CORE_lang['txtpublicgeneral'],
			'6' => $CORE_lang['txtAgenciaAsociada'],
			'7' => $CORE_lang['txtsoloplataforma'],
		];
		$Plan['plan']['unidad'] = $Plan['unidad'];
		$Plan['plan']['description'] = !empty(str_replace([' ', '.', '-', '_'], '', $Plan['description'])) ? $Plan['description'] : $Plan['name'];
		$Plan['plan']['title'] = $Plan['title'];
		$Plan['plan']['minTime'] = $Plan['min_tiempo'];
		$Plan['plan']['maxTime'] = $Plan['max_tiempo'];
		$Plan['plan']['maxAge'] = $Plan['max_age'];
		$Plan['plan']['minAge'] = $Plan['min_age'];
		$Plan['plan']['maxPas'] = is_numeric($Plan['num_pas']) ? $Plan['num_pas'] : 9;
		$Plan['plan']['local'] = $Plan['plan_local'] ?: 'N';
		$Plan['plan']['currency'] = $Plan['currency_iso'];

		$Plan['restrictions']['id'] = $Plan['id_restric'];
		$Plan['restrictions']['dirigido'] = $Plan['dirigido'];
		$Plan['restrictions']['dirigidoDescription'] = $restriction[$Plan['dirigido']] ?: $restriction[1];
		$Plan['restrictions']['broker'] = $Plan['id_broker'];
		$Plan['restrictions']['brokerName'] = $Plan['brokerName'];
		$Plan['restrictions']['destino'] = is_numeric($Plan['id_territory_destino']) ? $Plan['id_territory_destino'] : 0;
		$Plan['restrictions']['destinoName'] = $Plan['destinoName'];
		$Plan['restrictions']['origen'] = empty($Plan['id_territory_origen']) ? "N" : "Y";
		$Plan['restrictions']['origenCountry'] = $Plan['territory_origen'];


		$Plan['couple']['active'] = $Plan['plan_pareja'] ? $Plan['plan_pareja'] : "N";
		$Plan['couple']['factorTotal'] = self::truncateFloat(((float) $Plan['factor_pareja'] > 1 ? $Plan['factor_pareja'] : 1), 2);
		$Plan['couple']['factorCost'] = self::truncateFloat(((float) $Plan['factor_pareja_cost'] > 1 ? $Plan['factor_pareja_cost'] : 1), 2);

		$Plan['family']['active'] = $Plan['family_plan'] ? $Plan['family_plan'] : "N";
		$Plan['family']['factorTotal'] = self::truncateFloat(((float) $Plan['factor_family'] > 1.5 ? $Plan['factor_family'] : 2.5), 2);
		$Plan['family']['factorCost'] = self::truncateFloat(((float) $Plan['factor_family_cost'] > 1.5 ? $Plan['factor_family_cost'] : 2.5), 2);
		$Plan['family']['maxYounger'] = $Plan['family_plan_cantidad'] ? $Plan['family_plan_cantidad'] : 7;

		$Plan['overAge']['active'] = $Plan['normal_age'] ? "Y" : "N";
		$Plan['overAge']['normal'] = $Plan['normal_age'] ? (int) $Plan['normal_age'] : null;
		$Plan['overAge']['factorTotal'] = self::truncateFloat(((float) $Plan['overage_factor'] > 1 ? $Plan['overage_factor'] : 1.5), 2);
		$Plan['overAge']['factorCost'] = self::truncateFloat(((float) $Plan['overage_factor_cost'] > 1 ? $Plan['overage_factor_cost'] : 1.5), 2);

		unset($Plan['num_pas'],
		$Plan['max_age'],
		$Plan['min_age'],
		$Plan['unidad'],
		$Plan['title'],
		$Plan['min_tiempo'],
		$Plan['max_tiempo'],
		$Plan['plan_local'],
		$Plan['normal_age'],
		$Plan['destinoName'],
		$Plan['description'],
		$Plan['name'],
		$Plan['brokerName'],
		$Plan['id_restric'],
		$Plan['id_currence'],
		$Plan['id_broker'],
		$Plan['currency_iso'],
		$Plan['planIdPrefix'],
		$Plan['restrictIdPrefix'],
		$Plan['id_territory_destino'],
		$Plan['id_territory_origen'],
		$Plan['dirigido'],
		$Plan['territory_origen'],
		$Plan['plan_pareja'],
		$Plan['factor_pareja'],
		$Plan['factor_pareja_cost'],
		$Plan['family_plan'],
		$Plan['factor_family'],
		$Plan['factor_family_cost'],
		$Plan['family_plan_cantidad'],
		$Plan['overage_factor'],
		$Plan['overage_factor_cost']);

		if ($quote['destinoQuote'] == 9 && $Plan['plan']['local'] != "Y") {
			$Plan['errors'][] = "Local Invalido: {$Plan['plan']['local']}";
		}
		if (!empty($Plan['id_territory_destino']) && $quote['destinoQuote'] != $Plan['id_territory_destino']) {
			$Plan['errors'][] = "Destino Invalido: " . $Plan['id_territory_destino'];
		}
		if ($Plan['restrictions']['origen'] == "Y") {
			if (!in_array($quote['origenQuote'], $Plan['restrictions']['origen'])) {
				$Plan['errors'][] = "Origen Invalido: {$quote['origenQuote']}";
			}
		}
		if ($quote['cantidad'] > $Plan['plan']['maxPas']) {
			$Plan['errors'][] = "El limite de pasajeros es: {$Plan['plan']['maxPas']}";
		}
		if (!empty($quote['edades'])) {
			$Plan['age']['overAgeYes'] = 0;
			$Plan['age']['overAgeNo'] = 0;
			$Plan['age']['familyAgeOlder'] = 0;
			$Plan['age']['familyAgeYounger'] = 0;
			foreach ($quote['edades'] as $value) {
				if ($value < $Plan['plan']['minAge'] || $value > $Plan['plan']['maxAge']) $Plan['errors'][] = "Edad Invalida {$value} - min: " . $Plan['plan']['minAge'] . ", max: " . $Plan['plan']['maxAge'];
				if ($value > $Plan['overAge']['normal']) {
					$Plan['age']['overAgeYes']++;
				} else {
					$Plan['age']['overAgeNo']++;
				}
				if ($this->familyPlan >= $value) {
					$Plan['age']['familyAgeOlder']++;
				} else {
					$Plan['age']['familyAgeYounger']++;
				}
			}
		}
	}
	public function GetPlanTime($planIdsPref, $quote)
	{

		$planIdsPref = is_array($planIdsPref) ? implode("','", $planIdsPref) : $planIdsPref;
		$query = "SELECT 
			id,
			CONCAT(plan_times.id_plan,'-',plan_times.prefijo) AS planIdPrefix,
			plan_times.tiempo,
			plan_times.unidad,
			plan_times.valor,
			plan_times.cost,
			IFNULL(plan_times.adicional,0) AS adicional,
			plan_times.id_country,
			plan_times.prefijo
		FROM plan_times JOIN
		(SELECT
			id_plan,
			prefijo,
			MAX(tiempo) tiempoMax,
			MIN(tiempo) tiempoMin,
			unidad,
			id_country
		FROM
			plan_times
		WHERE CONCAT(id_plan,'-',prefijo) in('$planIdsPref') AND
		CASE
			WHEN unidad like 'dias' AND 
			IF((SELECT 1 FROM plan_times sub WHERE 
				sub.id_plan=plan_times.id_plan AND 
				sub.prefijo=plan_times.prefijo AND 
				sub.tiempo >= '{$quote['days']}' LIMIT 1),
				tiempo >= '{$quote['days']}',tiempo <='{$quote['days']}') THEN 1
			WHEN unidad like 'bloques' AND tiempo <= '{$quote['days']}' THEN 1
			WHEN unidad like 'meses' AND (tiempo*30) <= '{$quote['days']}' THEN 1
			ELSE 0
		END AND adicional=0 AND
			id_country in('all','{$quote['origenQuote']}')
		GROUP BY prefijo,id_plan,unidad,id_country) AS pf 
		ON
			plan_times.id_plan = pf.id_plan AND
			plan_times.prefijo = pf.prefijo AND
			plan_times.id_country = pf.id_country AND
		CASE
			WHEN adicional=1 THEN 1
			WHEN pf.unidad like 'dias' AND IF(pf.tiempoMin>='{$quote['days']}',tiempo=pf.tiempoMin,tiempo=pf.tiempoMax) THEN 1
			WHEN pf.unidad like 'bloques' AND tiempo=pf.tiempoMax THEN 1
			WHEN pf.unidad like 'meses' AND tiempo=pf.tiempoMax THEN 1
			ELSE 0
		END";
		foreach ($this->_SQL_tool($this->SELECT, __METHOD__, $query) as $value) {
			$typePrima = ($value['adicional'] == 0) ? 'primaBase' : 'primaAdicional';
			$premium = [
				"id"	=> $value['id'],
				"tiempo" => $value['tiempo'],
				"valor" => $value['valor'],
				"cost"  => $value['cost'],
			];
			if (empty($return[$value['planIdPrefix']][$value['id_country']][$typePrima][$value['unidad']])) {
				$return[$value['planIdPrefix']][$value['id_country']][$typePrima][$value['unidad']] = $premium;
			} else {
				$Plan['errors'][] = "Prima duplicada ID: {$premium} ";
			}
		}
		return $return;
	}
	public function GetClints($quote, $list = true)
	{
		$where = [
			"IFNULL(inactive_platform,0) <> 2",
			"clients.data_activa" => 'SI',
		];
		if (!empty($quote['prefijo'])) {
			$where["prefix"] = $quote['prefijo'];
		}
		$function = $list ? 'Group_List' : 'Get_List';
		return $this->$function("clients", ["prefix", "client"], $where);
	}
	public function GetCountries()
	{
		return $this->Get_List("countries", ["iso_country as iso", "description as name"], ['c_status="Y" order by description asc']);
	}
	public function GetCategory($quote)
	{
		$clients = $quote['prefijo'] ?: $this->GetClints($quote['prefijo']);
		return $this->Get_List("plan_category", [
			"id_plan_categoria AS id",
			"name_plan AS name",
			"GROUP_CONCAT(prefijo)  prefix"
		], [
			'prefijo' => array_keys($clients),
			"EXISTS ( SELECT 1 FROM plans WHERE plans.id_plan_categoria = plan_category.id_plan_categoria AND plans.activo = '1' AND eliminado <>2)",
			'id_status=1 GROUP BY id_plan_categoria'
		]);
	}
	public function GetDestino($quote)
	{
		$clients = $quote['prefijo'] ?: $this->GetClints($quote['prefijo']);
		return $this->Get_List("territory", [
			"id_territory AS id",
			"desc_small AS name",
			"GROUP_CONCAT(prefijo) prefix"
		], [
			'prefijo' => array_keys($clients),
			'id_status=1 GROUP BY id_territory'
		]);
	}
}
$quote = new cotizadorIls();
switch ($GPC['type']) {
	case 'quote':
		$interval = round(abs(strtotime($GPC['date']['start']) - strtotime($GPC['date']['end'])) / 86400) + 1;
		echo json_encode($quote->Quote([
			'category' => $GPC['category'],
			'days' => $interval,
			'destinoQuote' => $GPC['destiny'],
			'origenQuote' => $GPC['country'],
			'edades' => $GPC['ages'],
			'prefijo' => $GPC['client']
		]));
		break;
	case 'category':
		echo json_encode($quote->GetCategory());
		break;
	case 'destiny':
		$destiny = array_merge([['id' => '', 'name' => $CORE_lang['texerrodestino'], 'prefix' => 'ALL']], $quote->GetDestino() ?: []);
		echo json_encode($destiny);
		break;
	case 'client':
		echo json_encode($quote->GetClints('', false));
		break;
	case 'country':
		$country = [['iso' => '', 'name' => $CORE_lang['texerroorigen']]] + ($quote->GetCountries() ?: []);
		echo json_encode($country);
		break;
	default:
		echo json_encode(['status' => 'error', 'mensage' => 'invalid peticion']);
		break;
}
