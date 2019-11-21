<?php

class get_functions extends general_functions
{
	public function get_fuctions($function, $apikey)
	{
		$response   = $this->$function($_GET, $apikey);
		$countResponse  = count($response);
		$this->logsave($_GET, json_encode($response), $function, '4', $apikey);
		($countResponse != 0) ? $this->response($response, '', $format) : $this->getError(9015, '', $format);
	}
	public function getPlans($filters, $api)
	{
		$language	= $filters['lenguaje'];
		$plan       = $filters['plan'];
		$dataValida	= [
			'6021'	=> $language,
			'1030'	=> $this->validLanguage($language)
		];
		$validatEmpty	= $this->validatEmpty($dataValida);
		if (!empty($validatEmpty)) {
			return $validatEmpty;
		}
		$datAgency			= $this->datAgency($api);
		$idAgency			= $datAgency[0]['id_broker'];
		return $this->verifyRestrictionPlan($idAgency, $plan, $language, true);
	}
	public function getCoverages($data, $api)
	{
		$plan		= $data['plan'];
		$prefix		= $data['prefix'];
		$language	= strtolower($data['lenguaje']);
		$dataValida	= [
			'6037'	=> !(empty($plan) and empty($language)),
			'6022'	=> $plan,
			'6021'	=> $language,
			'1030'	=> in_array($language, ['spa', 'eng', 'por', 'fra', 'deu'])
		];
		$validatEmpty	= $this->validatEmpty($dataValida);
		if (!empty($validatEmpty)) {
			return $validatEmpty;
		}
		//$restrictionPlan	= $this->verifyRestrictionPlan('',$plan,$language,false,$api,true);
		// if($restrictionPlan){
		// 	return $restrictionPlan;
		// }
		return $this->dataCoverages($language, $plan, $prefix);
	}
	public function getClients()
	{
		return $this->selectDynamic('', 'clients', "data_activa='si' AND id_status= '1' AND IFNULL(inactive_platform, 0) <> 2 ORDER BY client ASC", ['client', 'id_country', 'img_cliente', 'web', 'urlPrueba', 'prefix', 'type_platform', 'id_broker', 'email', 'colors_platform'], '', '', '', '', '');
	}
	public function getCountries($filters)
	{
		$prefix     = $filters['prefix'];
		$dataValida	= [
			"9092"  => $prefix,
		];

		$this->validatEmpty($dataValida);

		$data = [
			'querys' => "SELECT
				iso_country,
				description
			FROM
				countries
			WHERE
				c_status = 'Y'
			ORDER BY
				description "
		];

		$link 		= $this->selectDynamic(['prefix' => $prefix], 'clients', "data_activa='si'", ['web'])[0]['web'];
		$linkParam 	= $link . "/app/api/selectDynamic";
		$headers 	= "content-type: application/x-www-form-urlencoded";
		$response = $this->curlGeneral($linkParam, json_encode($data), $headers);
		return json_decode($response);
		//return $this->selectDynamic('', 'countries', "c_status='Y'", ['iso_country', 'description'], '', ['min' => '0', 'max' => '349'], ['field' => 'description', 'order' => 'ASC']);
	}

	public function getTerritorios($filters)
	{
		$prefix     = $filters['prefix'];
		$dataValida	= [
			"9092"  => $prefix,
		];

		$this->validatEmpty($dataValida);

		$data = [
			'querys' => "SELECT
				*
			FROM
				territory
			WHERE
				id_status = 1 
			ORDER BY
				desc_small,
				id_territory "
		];

		$link 		= $this->selectDynamic(['prefix' => $prefix], 'clients', "data_activa='si'", ['web'])[0]['web'];
		$linkParam 	= $link . "/app/api/selectDynamic";
		$headers 	= "content-type: application/x-www-form-urlencoded";
		$response = $this->curlGeneral($linkParam, json_encode($data), $headers);
		return json_decode($response);
	}
	public function getStates()
	{
		return $this->selectDynamic('', '', '', '', "SELECT
										iso_country,
										iso_state,
										description,
										nameID
									FROM
										`states`
									ORDER BY
										iso_country ASC", '', '', '', '');
	}
	public function getCitys()
	{
		return $this->selectDynamic('', '', '', '', "SELECT
										iso_city,
										description
									FROM
										`cities`
									ORDER BY
										iso_city ASC", '', '', '', '');
	}
	public function checkVersionApp()
	{
		return $this->selectDynamic('', 'versions_app', "status='Y'", ['version', 'fecha_version'], '', ['min' => '0', 'max' => '1'], ['field' => 'id_version', 'order' => 'DESC']);
	}
	public function checkVersionAppA($filters)
	{
		$prefix    = $filters['prefixApp'] ?: $filters['prefix'];
		$plataforma	   = $filters['plataforma'];
		$versionApp = $filters['versionAppApi'];
		$dataValida	= [
			"9092"  => $prefix,
			"50001"  => $plataforma
		];
		$validatEmpty  = $this->validatEmpty($dataValida);
		if (!empty($filters['prefixApp'] && $versionApp != 'DEV')) { //validacion si se envia la version de la app para responder a esto
			return $this->selectDynamic('', 'versions_app', "status='Y' AND prefijo = '$prefix' AND plataforma= '$plataforma' AND version > '$versionApp' ", ['MAX(version) AS version', 'MAX(fecha_version) AS fecha_version', 'REPLACE (GROUP_CONCAT(CONCAT(descripcion, CHAR(13))),","," ") as descripcion'], '', ['min' => '0', 'max' => '1'], '', '', '');
		} else {
			return $this->selectDynamic('', 'versions_app', "status='Y' AND prefijo = '$prefix' AND plataforma= '$plataforma' ", ['version', 'fecha_version', 'descripcion'], '', ['min' => '0', 'max' => '1'], ['field' => 'id_version', 'order' => 'DESC'], '', '');
		}
	}
	public function getPlan($filters)
	{
		$idPlan    = $filters['idPlan'];
		$prefix	   = $filters['prefix'];
		$dataValida	= [
			"5022"  => $idPlan,
			"9092"  => $prefix
		];
		$validatEmpty  = $this->validatEmpty($dataValida);
		return $this->selectDynamic('', 'plans', "id = $idPlan AND prefijo = '$prefix'", ['id_plans', 'id', 'name', 'description', 'activo', 'id_plan_categoria'], '', '', ['field' => 'name', 'order' => 'ASC'], '', '');
	}
	public function getVendedor($filters)
	{
		$idVendedor    = $filters['idVendedor'];
		$prefix	       = $filters['prefix'];
		$dataValida	= [
			"40094"  => $idVendedor,
			"9092"  => $prefix
		];
		$validatEmpty  = $this->validatEmpty($dataValida);
		return $this->selectDynamic('', 'users_extern', "id = $idVendedor AND prefijo = '$prefix'", ['firstname', 'lastname', 'email'], '', '', '', '', '');
	}
	public function getAgencyParam($filters)
	{
		$prefix	    = $filters['prefix'];
		$dataValida	= [
			"9092"  => $prefix
		];
		$validatEmpty  = $this->validatEmpty($dataValida);
		return $this->selectDynamic('', 'clients', "prefix = '$prefix' AND data_activa = 'si' AND id_status= '1' AND IFNULL(inactive_platform, 0) <> 2", ['client', 'phone1', 'cod_phone2', 'phone2', 'cod_phone3', 'phone3', 'cod_phone4', 'phone4', 'address', 'id_country', 'img_cliente', 'id_country', 'id_city', 'zip_code', 'date_up', 'id_state', 'web', 'urlPrueba', 'prefix', 'type_platform', 'id_broker', 'email', 'colors_platform'], '', '', '', '', '', '');
	}


	public function getOrders($filters, $apikey)
	{
		$document  = $filters['document'];
		$code	   = $filters['code'];
		$name      = $filters['name'];
		$prefix	   = $filters['prefix'];
		$userType  = ($filters['userType']) ? $filters['userType'] : 1;
		$startDate = $filters['startDate'];
		$endDate   = $filters['endDate'];
		$source    = $filters['source'];
		$min	   = ($filters['min'] <= 0 || empty($filters['min'])) ? 0 : $filters['min'];
		$max	   = ($filters['max'] <= 0 || empty($filters['max'] || ($filters['max'] <= $filters['min']))) ? 50 : $filters['max'];
		$status	   = ($filters['status']) ? $filters['status'] : 1;
		$today 	   = date('Y-m-d');
		$id_user   = $filters['id_user'];

		$dataValida	= [
			"9092"  => $prefix,
			"9017"  => !empty($status) ? in_array($status, [1, 2, 3, 4, 5]) : true,
			'3030'	=> (!empty($endDate) && !empty($endDate)) ? !(strtotime($startDate) > strtotime($endDate)) : true,
			'9068'	=> (!empty($endDate) && !empty($endDate)) ? !(strtotime($endDate)	> strtotime($today)) : true,
			'9069'	=> (!empty($endDate) && !empty($endDate)) ? !(strtotime($startDate)	> strtotime($today)) : true,
		];


		$validatEmpty	= $this->validatEmpty($dataValida);
		if (!empty($validatEmpty)) {
			return $validatEmpty;
		}
		$valueOrders = [
			'orders.id',
			'orders.id_orders',
			'codigo',
			'origen',
			'destino',
			'salida',
			'retorno',
			'fecha',
			"DATE_FORMAT(salida,'%d-%m-%Y') as fsalida",
			"DATE_FORMAT(retorno,'%d-%m-%Y') as fretorno",
			"DATE_FORMAT(fecha,'%d-%m-%Y') as ffecha",
			"REPLACE( orders.nombre_contacto,'''','') AS nombre_contacto",
			'email_contacto',
			'telefono_contacto',
			'agencia',
			'nombre_agencia',
			'status',
			'cantidad',
			'referencia',
			'territory',
			'producto',
			'(DATEDIFF(orders.retorno, orders.salida) + 1 ) as diasViaje'
		];

		if ($source != 'public') {
			array_push(
				$valueOrders,
				'family_plan',
				'comentario_medicas',
				'total',
				'vendedor',
				'cupon',
				'codeauto',
				'procesado',
				'response',
				'v_authorizado',
				'neto_prov',
				'tasa_cambio',
				'forma_pago',
				'neto_prov_mlc',
				'total_mlc',
				'compra_minima',
				'pareja_plan',
				'Voucher_Individual',
				'v_authorizado',
				'credito_numero',
				'codeauto'
			);
		}

		$codeWhere = '1';
		$arrWhere = [];
		$idBroker	= $this->getBrokersByApiKey($apikey);
		if (!empty($idBroker) && !in_array($userType, [1, 2, 5, 13])) {
			$arrWhere['agencia'] = $idBroker;
		}
		if (!empty($code)) {
			$codeWhere = "codigo LIKE '%$code%'";
		}

		$arrWhere['orders.prefijo'] = $prefix;
		$arrLimit = ['min' => $min, 'max' => $max];
		if (!empty($name)) {
			$name = trim($name);
			$nameSearch = explode(' ', $name);
			$arrJoin = [
				'table'		=> 'beneficiaries',
				'field'		=> "id_orden AND beneficiaries.prefijo = orders.prefijo AND (concat_ws(' ', TRIM(BOTH ' ' FROM beneficiaries.nombre),
					TRIM(BOTH ' ' FROM beneficiaries.apellido)) LIKE '%$name%'
					OR (TRIM(BOTH ' ' FROM beneficiaries.nombre) LIKE '%$nameSearch[0]%'
					AND TRIM(BOTH ' ' FROM beneficiaries.apellido) LIKE '%$nameSearch[1]%')
					OR (TRIM(BOTH ' ' FROM beneficiaries.nombre) LIKE '%$nameSearch[1]%'
					AND TRIM(BOTH ' ' FROM beneficiaries.apellido) LIKE '%$nameSearch[0]%'))",
				'fieldComp'	=> 'id'
			];
		}

		if (!empty($document)) {

			$arrJoin = [
				'table' => 'beneficiaries',
				'field' => "id_orden AND beneficiaries.prefijo = orders.prefijo AND beneficiaries.documento LIKE '%$document%'",
				'fieldComp' => 'id'
			];
			$arrLimit = ['min' => $min, 'max' => $max];
			array_push($valueOrders, 'beneficiaries.documento');
		}

		if (!empty($startDate) && !empty($endDate)) {
			$between = [
				'start' => $startDate,
				'end'   => $endDate,
				'field' => 'fecha'
			];
		}
		if (!empty($pagination)) {
			$arrPagination  = implode(',', $pagination);
			if (is_array($arrPagination)) { }
		}

		$id_agencia = [];
		$arrBrokers = [];
		$arr;
		if (!empty($userType) &&  in_array($userType, [5, 2])) { ////// usuario tipo 5 broker access solo vera de su agencia y 2 es broker admin su agencia y las debajo de ella
			$id_agencia =  $this->agencyBroker($id_user, $userType, $prefix)[0]["id_associate"];
			$arrWhere['orders.agencia'] = $id_agencia;

			if (in_array($userType, [2])) { ////// usuario tipo 2 broker admin vera vouchers de ella y sus agencias hijas
				$arrWhere['orders.agencia'] = null;
				$broker_nivel = $this->agencysChildren($id_agencia, $prefix);
				if ($broker_nivel) {
					$arrBrokers = array_column($broker_nivel, 'id_broker');
					array_push($arrBrokers, $id_agencia);
					$arrBrokers = array_values($arrBrokers); //agencias hijas y su agencia master
					$arr = implode(',', $arrBrokers);
				} else {
					$arr = $id_agencia;
				}
				$arr = ' AND orders.agencia IN (' . $arr . ') ';
				$codeWhere .= $arr;
			}
		}

		//$arrWhere['status']=$status;
		$dataOrders = $this->selectDynamic(
			$arrWhere,
			'orders',
			"$codeWhere",
			$valueOrders,
			false,
			$arrLimit,
			["field" => "fecha", "order" => "DESC"],
			$between,
			$arrJoin
		);

		$arrBeneficiaries = [];
		$cDataOrders = count($dataOrders);

		for ($i = 0; $i < $cDataOrders; $i++) {
			$idOrder = $dataOrders[$i]['id'];

			$dataOrders[$i]['categoria'] = $this->selectDynamic(
				'',
				'',
				'',
				'',
				"SELECT
				plan_category.name_plan AS categoria
			FROM
				orders
			JOIN plans ON orders.producto = plans.id
			AND orders.prefijo = plans.prefijo
			JOIN plan_category ON plan_category.id_plan_categoria = plans.id_plan_categoria
			AND plan_category.prefijo = plans.prefijo
			WHERE
				orders.id = '$idOrder'
			AND orders.prefijo = '$prefix'",
				'',
				'',
				''
			)[0]['categoria'] ?: 'N/A';

			$dataOrders[$i]['beneficiaries'] = $this->selectDynamic(
				['beneficiaries.prefijo' => $prefix],
				'beneficiaries',
				"id_orden='$idOrder'",
				['id', 'id_orden', "REPLACE(beneficiaries.nombre,'''','') AS nombre ", "REPLACE(beneficiaries.apellido,'''','') AS apellido ", 'documento', 'email', 'nacimiento', 'nacionalidad', 'tipo_doc', 'telefono'],
				'',
				'',
				[
					'field' => 'nombre',
					'order' => 'ASC'
				],
				'',
				''
			);
		}
		return $dataOrders;
	}

	public function getParamAgencyMaster($filters)
	{
		$id_user = $filters['id_user'];
		$prefix  = $filters['prefix'];
		$agency  = $filters['agency'];

		$dataValida	= [
			"50005" => $id_user,
			"9092"  => $prefix,
			"50006" => $agency
		];

		$this->validatEmpty($dataValida);

		$response = $this->selectDynamic(
			'',
			'',
			'',
			'',
			"SELECT
			*, (
				SELECT
					broker_nivel.nivel
				FROM
					broker_nivel
				WHERE
					broker_nivel.id_broker = broker_parameters.id_broker
				AND broker_nivel.prefijo = broker_parameters.prefijo
				ORDER BY
					id_broker_nivel DESC
				LIMIT 1
			) AS nivel,
			(
				SELECT
					broker.broker
				FROM
					broker
				WHERE
					broker.id_broker = broker_parameters.id_broker
				AND broker.prefijo = broker_parameters.prefijo
				LIMIT 1
			) AS nombreAgencia,
			(
				SELECT
					broker.address
				FROM
					broker
				WHERE
					broker.id_broker = broker_parameters.id_broker
				AND broker.prefijo = broker_parameters.prefijo
			) AS direccion,
			(
				SELECT
					countries.description
				FROM
					broker
				INNER JOIN countries ON countries.iso_country = broker.id_country
				WHERE
					broker.id_broker = broker_parameters.id_broker
				AND broker.prefijo = broker_parameters.prefijo
			) AS pais,
			(
				SELECT
					states.description
				FROM
					broker
				INNER JOIN states ON states.iso_state = broker.id_state
				WHERE
					broker.id_broker = broker_parameters.id_broker
				AND broker.prefijo = broker_parameters.prefijo
			) AS estado,
			(
				SELECT
					cities.description
				FROM
					broker
				INNER JOIN cities ON cities.iso_city = broker.id_city
				WHERE
					broker.id_broker = broker_parameters.id_broker
				AND broker.prefijo = broker_parameters.prefijo
			) AS ciudad
		FROM
			broker_parameters
		WHERE
			broker_parameters.prefijo = '$prefix'
		AND broker_parameters.id_broker = '$agency'
		ORDER BY
			id_broker_parameters DESC
		LIMIT 1",
			'',
			'',
			'',
			''
		);

		if (empty($response)) {
			$respons = $this->selectDynamic(
				'',
				'',
				'',
				'',
				"SELECT
				*, (
					SELECT
						broker_nivel.nivel
					FROM
						broker_nivel
					WHERE
						broker_nivel.id_broker = broker.id_broker
					AND broker_nivel.prefijo = broker.prefijo
					LIMIT 1
				) AS nivel,
				(
					SELECT
						countries.description
					FROM
						countries
					WHERE
						countries.iso_country = broker.id_country
				) AS pais,
				(
					SELECT
						states.description
					FROM
						states
					WHERE
						states.iso_state = broker.id_state
				) AS estado,
				(
					SELECT
						cities.description
					FROM
						cities
					WHERE
						cities.iso_city = broker.id_city
				) AS ciudad
			FROM
				broker
			WHERE
				broker.prefijo = '$prefix'
			AND broker.id_broker = '$agency'
			ORDER BY
				id_new_broker DESC
			LIMIT 1",
				'',
				'',
				'',
				''
			);

			$response = [
				[
					"id_broker" 	   => $respons[0]['id_broker'],
					"dominio"          => $respons[0]['dominio'],
					"email_from"       => $respons[0]['email'],
					"email_cotizacion" => $respons[0]['email'],
					"skype"            => $respons[0]['skype'],
					"whatsapp" 		   => $respons[0]['code_phone1'] . $respons[0]['phone1'],
					"prefijo_ref" 	   => $respons[0]['prefijo_ref'],
					"instagram"        => $respons[0]['instagram'],
					"facebook" 		   => $respons[0]['facebook'],
					"pagina_web"       => $respons[0]['pagina_web'],
					"prefijo"          => $respons[0]['prefijo'],
					"enlace_app_android" => $respons[0]['enlace_app_android'],
					"enlace_app_apple" => $respons[0]['enlace_app_apple'],
					"email_from_name"  => $respons[0]['email_from_name'],
					"nivel"            => $respons[0]['nivel'],
					"nombreAgencia"    => $respons[0]['broker'],
					"direccion"        => $respons[0]['address'],
					"pais"   		   => $respons[0]['pais'],
					"estado"		   => $respons[0]['estado'],
					"ciudad"           => $respons[0]['ciudad']
				]
			];
		}

		if (!empty($response[0]['whatsapp'])) {
			switch (strpos($response[0]['whatsapp'], '+')) {
				case true:
					$response[0]['whatsapp'] = '+' . $response[0]['whatsapp'];
					break;

				default:
					$response[0]['whatsapp'] = $response[0]['whatsapp'];
					break;
			}
		}

		return $response;
	}

	public function getDatosUser($filters)
	{
		$prefix  = $filters['prefix'];
		$id_user = $filters['id_user'];

		$dataValida	= [
			"50005" => $id_user,
			"9092"  => $prefix
		];

		$this->validatEmpty($dataValida);

		$select = [
			"id",
			"firstname",
			"lastname",
			"s_first",
			"email",
			"code_phone",
			"phone",
			"user_type",
			"(
				SELECT
					countries.description
				FROM
					countries
				WHERE
					users_extern.id_country = countries.iso_country
				LIMIT 1
			) AS pais",
			"users_extern.prefijo"
		];

		$where = [
			'id'        => $id_user,
			'prefijo'   => $prefix,
			'id_status' => 1
		];

		$order = [
			"field" => "id_users",
			"order" => "DESC"
		];

		$response = $this->selectDynamic($where, 'users_extern', '1', $select, false, '', $order, '', '');

		switch ($response[0]['user_type']) {
			case '1':
				$response[0]['user_type'] = 'MASTER';
				break;

			case '2':
				$response[0]['user_type'] = 'BROKER ADMIN';
				break;

			case '5':
				$response[0]['user_type'] = 'BROKER ACCESS';
				break;

			case '13':
				$response[0]['user_type'] = 'ADMIN';
				break;

			default:
				$response[0]['user_type'] = $response[0]['user_type'];
				break;
		}

		return $response;
	}

	public function getCategories($filters)
	{
		$prefix 	= $filters['prefix'];
		$agencia    = $filters['agencyMaster'];
		$dataValida	= [
			"9092"  => $prefix
		];

		$this->validatEmpty($dataValida);

		$query = "SELECT
			REPLACE( plan_categoria_detail.name_plan,'''','') as name_plan ,
			plan_categoria_detail.id_plan_categoria ,
			REPLACE( plan_categoria_detail.description_plan,'''','') AS description_plan,
			plan_category.img AS img
		FROM
			plans
		INNER JOIN plan_category ON plan_category.id_plan_categoria = plans.id_plan_categoria
		AND plans.eliminado = 1
		AND plans.activo = 1
		INNER JOIN plan_categoria_detail ON plan_category.id_plan_categoria = plan_categoria_detail.id_plan_categoria
		LEFT JOIN restriction ON plans.id = restriction.id_plans
		WHERE
			plan_categoria_detail.language_id = 'spa'
		AND plan_category.vision_id = 1
		AND plan_category.id_status = 1";
		if ($agencia != 'N/A' && !empty($agencia)) {
			$query .= " AND ((restriction.id_broker in (" . $agencia . ") and restriction.dirigido='6') or (restriction.dirigido='2' and restriction.id_broker in (" . $agencia . "))) ";
		} else {
			$query .= " AND (
				restriction.dirigido = '0'
				OR restriction.dirigido IS NULL
				OR restriction.dirigido = '1'
			) ";
		}
		$query .= " AND (
			modo_plan = 'N'
			OR modo_plan = 'W'
			OR modo_plan = 'T'
			OR modo_plan IS NULL
		)
		GROUP BY
			plan_category.id_plan_categoria
		ORDER BY
			plan_categoria_detail.name_plan ASC";

		$data = [
			'querys' => $query
		];

		$link 		= $this->selectDynamic(['prefix' => $prefix], 'clients', "data_activa='si'", ['web'])[0]['web'];
		$linkParam 	= $link . "/app/api/selectDynamic";
		$headers 	= "content-type: application/x-www-form-urlencoded";
		$response = $this->curlGeneral($linkParam, json_encode($data), $headers);
		$respuesta = json_decode($response, true);
		for ($i = 0; $i < count($respuesta); $i++) {
			if (!empty($respuesta[$i]['img'])) {
				$respuesta[$i]['img'] = $link . '/app/images/images/' . $respuesta[$i]['img'];
				$respuesta[$i]['description_plan'] = strip_tags($respuesta[$i]['description_plan']);
			} else {
				$respuesta[$i]['img'] = '';
				$respuesta[$i]['description_plan'] = strip_tags($respuesta[$i]['description_plan']);
			}
		}
		return $respuesta;
		//return $this->dataCategories($prefix);
	}

	public function getIntervaloFechas($filters)
	{
		$idCategory = $filters['idCategory'];
		$paisOrigen = $filters['paisOrigen'];
		$prefix     = $filters['prefix'];

		$dataValida	= [
			"9092"  => $prefix,
			"9094"  => $idCategory,
			"6027"  => $paisOrigen
		];

		$this->validatEmpty($dataValida);

		$response = $this->intervaloDias($prefix, $idCategory, $paisOrigen);
		switch ($response[0]['type_category']) {
			case 'MULTI_TRIP':
				$bloquesMultiViaje = $this->bloquesMultiViajes($prefix);

				return $resp = [
					[
						'dias_min'          	=> (int) '365',
						'dias_max'          	=> (int) '365',
						'id_plan_categoria' 	=> (int) $response[0]['id_plan_categoria'],
						'type_category'     	=> $response[0]['type_category'],
						'bloques_multi_viajes' 	=> $bloquesMultiViaje
					]
				];
				break;
			default:
				return $resp = [
					[
						'dias_min'          	=> (int) $response[0]['dias_min'],
						'dias_max'          	=> (int) $response[0]['dias_max'],
						'id_plan_categoria' 	=> (int) $response[0]['id_plan_categoria'],
						'type_category'     	=> $response[0]['type_category'],
					]
				];
				break;
		}
	}

	public function getIntervaloDeEdades($filters)
	{
		$idCategory = $filters['idCategory'];
		$country    = $filters['country'];
		$idPlan     = $filters['idPlan'];
		$prefix     = $filters['prefix'];

		$dataValida	= [
			"9092"  => $prefix,
			"9094"  => $idCategory,
			"6027"  => $country
		];

		$this->validatEmpty($dataValida);

		$intervalos = $this->plans_intervalos_edades($idCategory, $country, '', $prefix);

		for ($i = 0; $i < $intervalos[0]['cantidad']; $i++) {
			$interval[$i] = $intervalos[$i]['min'] . " - " . $intervalos[$i]['max'];
			$indice[$i] = $intervalos[$i]['max'];
		}
		//$this->plans_intervalos_edades($idCategory, $country, '', $prefix);
		return [$intervalos, $interval, $indice];
	}

	public function GetPricesApiQuoteGeneral($filters, $apikey)
	{
		$quote	   = new cotizadorIls();
		$prefix	   = $filters['prefix'];
		$origin	   = $filters['origin'];
		$startDate = $filters['startDate'];
		$endDate   = $filters['endDate'];
		$destiny   = $filters['destiny'];
		$category  = $filters['category'];
		$id_broker = ($filters['agency'] != 'N/A' && !empty($filters['agency'])) ? $filters['agency'] : 118;
		$ages	   = explode(',', $filters['ages']);
		$bloque    = $filters['bloque'];
		$today 	   = date('Y-m-d');
		$dataValida	= [
			"9092"  => $prefix,
			"6027"  => $origin,
			"1080"  => !empty($destiny) ?: true,
			"6029"  => $startDate,
			"9094"  => $category,
			"6030"  => $endDate,
			'2001'	=> $this->checkDates($startDate),
			'2002'	=> $this->checkDates($endDate),
			'9095'	=> is_array($ages)
		];
		if (!empty($startDate) && !empty($endDate)) {
			$startDate  = $this->transformerDate($startDate);
			$endDate 	= $this->transformerDate($endDate);
		}
		$arrVerifyDate = [
			'3030'	=> !(strtotime($startDate) > strtotime($endDate)),
			'50009'	=> !(strtotime($endDate)   < strtotime($today)),
			'50008'	=> !(strtotime($startDate) < strtotime($today)),
		];
		$this->validatEmpty($dataValida + $arrVerifyDate);
		$interval = $this->betweenDates($startDate, $endDate, '');
		$ages = implode(',', $ages) . ',';

		$dataCurl = [
			'plan_tipo'         =>  $category,
			'dias'              =>  $interval,
			'id_country_broker' =>  '',
			'destino'           =>  $destiny,
			'origen'            =>  $origin,
			'edades'            =>  $ages,
			'salida'            =>  $startDate,
			'llegada'           =>  $endDate,
			'id_broker'         =>  $id_broker,
			'PlanSel'           =>  '',
			'min_days'          =>  $bloque
		];

		$link 		= $this->selectDynamic(['prefix' => $prefix], 'clients', "data_activa='si'", ['web'])[0]['web'];
		$linkParam 	= $link . "/app/api/quotePlansApp";
		$headers 	= "content-type: application/x-www-form-urlencoded";
		$respons    = $this->curlGeneral($linkParam, json_encode($dataCurl), $headers);
		$response   = json_decode($respons, true);

		foreach ($response as $key => $value) {
			if (empty($value['total'])) {
				if ($value['error_age'] == 0) {
					return $this->getError(50010);
				} elseif ($value['error_broker'] == 0) {
					return $this->getError(50011);
				} elseif ($value['error_country'] == 0) {
					return $this->getError(50012);
				} elseif ($value['error_time'] == 0) {
					return $this->getError(50013);
				} elseif ($value['error_territory'] == 0) {
					return $this->getError(50014);
				} elseif ($value['error_cant_passenger'] == 0) {
					return $this->getError(50015);
				} elseif ($value['error_local_plans'] == 0) {
					return $this->getError(50016);
				} elseif (count($response) == 0) {
					return $this->getError(1060);
				}
			} else {
				return $response;
			}
		}
	}

	public function getPrices($filters, $apikey)
	{
		$quote	   = new quoteils();
		$prefix	   = $filters['prefix'];
		$origin	   = $filters['origin'];
		$startDate = $filters['startDate'];
		$endDate   = $filters['endDate'];
		$destiny   = $filters['destiny'];
		$category  = $filters['category'];
		$ages	   = explode(',', $filters['ages']);
		$today 	   = date('Y-m-d');
		$dataValida	= [
			"9092"  => $prefix,
			"6027"  => $origin,
			"1080"  => !empty($destiny) ?: true,
			"6029"  => $startDate,
			"9094"  => $category,
			"6030"  => $endDate,
			'2001'	=> $this->checkDates($startDate),
			'2002'	=> $this->checkDates($endDate),
			'9095'	=> is_array($ages)
		];
		if (!empty($startDate) && !empty($endDate)) {
			$startDate  = $this->transformerDate($startDate);
			$endDate 	= $this->transformerDate($endDate);
		}
		$arrVerifyDate = [
			'3030'	=> !(strtotime($startDate) > strtotime($endDate)),
			'50009'	=> !(strtotime($endDate)   < strtotime($today)),
			'50008'	=> !(strtotime($startDate) < strtotime($today)),
		];
		$this->validatEmpty($dataValida + $arrVerifyDate);
		$interval = $this->betweenDates($startDate, $endDate);
		$dataQuote = $quote->Quote([
			'category'		=> $category,
			'days'			=> $interval,
			'destinoQuote'	=> $destiny,
			'origenQuote'	=> $origin,
			'edades'		=> $ages,
			'prefijo'		=> $prefix
		]);
		$dataPrices = [];
		foreach ($dataQuote as $key => $value) {
			$dataPrices[] = [
				'idPlan'        => $value['id'],
				'Price'			=> $value['totalShow'],
				'Nombre'		=> $value['title'],
				'Description'	=> $value['description'],
				'Couple'		=> $value['couple']["active"],
				'Family'		=> $value['family']["active"]
			];
		}
		return ($dataPrices) ? $dataPrices : $this->getError(1060);
	}
	function get_cod_phones()
	{
		$query = "SELECT
                    iso_country,
                    description,
                    phone AS cod_phone
                FROM
                    countries
                WHERE
                    phone IS NOT NULL ";
		$query .= " AND c_status = 'Y'";
		if (!empty($code_phone)) $query .= " AND iso_numeric IS NOT NULL";
		$query .= " ORDER BY description ASC ";
		$this->selectDynamic('', '', '', '', $query, '', '', '', '');
		$result = $this->_SQL_tool($this->SELECT, METHOD, $query);
		return array_reduce($result, function ($response, $element) {
			$split = array_map(function ($value) use ($element) {
				return [
					'cod_phone' => '+' . preg_replace('/[^0-9]+/', '', $value),
					'iso_country' => $element['iso_country'],
					'description' => $element['description'],
				];
			}, explode("and", $element['cod_phone']));
			return array_merge($response, $split);
		}, []);
		return $arrCountries;
	}
	public function getInformIls()
	{
		$query = "SELECT
					parameter_key,
					parameter_value,
					NAME,
					id_country,
					(
						SELECT
							description
						FROM
							states
						WHERE
							iso_state = parameters.id_state
					) AS id_state,
					IFNULL((
						SELECT
							description
						FROM
							cities
						WHERE
							iso_city = parameters.id_city AND iso_city>0
					LIMIT 1
					),'N/A') AS id_city,
					address_parameter,
					zip_code,
					phone1,
					phone2,
					phone3,
					phone4
				FROM
					parameters
				WHERE
					parameter_key IN (
						'SYSTEM_NAME',
						'EMAIL_FROM',
				'ID_WHATSAPP'
				)
				ORDER BY
					parameter_key ASC";
		return $this->selectDynamic('', '', '', '', $query, '', '', '', '');
	}
	////////////////////////////////////////////// GRAFICOS DE TODAS LAS AGENCIAS ////////////////////
	public function getGrafGenAgen($filters)
	{
		$startDate  = $filters['startDate'];
		$endDate   	= $filters['endDate'];
		$today 	   	= date('Y-m-d');
		$year       = date('Y');
		$dataValida	= [
			'3030'	=> (!empty($endDate) && !empty($endDate)) ? !(strtotime($startDate) > strtotime($endDate)) : true,
			'9068'	=> (!empty($endDate) && !empty($endDate)) ? !(strtotime($endDate)	> strtotime($today)) : true,
			'9069'	=> (!empty($endDate) && !empty($endDate)) ? !(strtotime($startDate)	> strtotime($today)) : true,
		];
		$validatEmpty  = $this->validatEmpty($dataValida);
		//GRAFICA DRILLDOWN DE VENTAS DIARIAS DE TODAS LAS AGENCIAS
		$query1 = "SELECT
			prefijo,
			producto,
			IFNULL((
				SELECT
					plans. NAME
				FROM
					plans
				WHERE
					orders.producto = plans.id
				AND orders.prefijo = plans.prefijo
				LIMIT 1
			),'N/A') AS name_plan,
			client,
			COUNT(cantidad) AS cantidad
		FROM
			orders
		JOIN clients ON clients.prefix = orders.prefijo
		WHERE
			IFNULL(inactive_platform, 0) <> 2
		AND DATE(orders.fecha) = '$today'
		AND orders. STATUS IN (1, 3)
		AND clients.data_activa = 'SI'
		GROUP BY
			prefijo,
			producto
		ORDER BY client";
		$respGraf1 = $this->selectDynamic('', '', '', '', $query1, '', '', '', '');
		foreach ($respGraf1 as $element) {
			$sumatori[$element['prefijo']] += (int) $element['cantidad'] ?: 0;
			$clientsName[$element['prefijo']] = $element['client'];
			$drilldownRaw[$element['prefijo']][] = [$element['name_plan'], (int) $element['cantidad']];
		}
		foreach ($clientsName as $key => $val) {
			$series[] = [
				'name' => $val,
				'y' => (int) $sumatori[$key],
				'drilldown' => $key,
			];
			$drilldown[] = [
				'name' => $val,
				'id' => $key,
				'data' => $drilldownRaw[$key],
			];
		}
		//GRAFICA CANTIDAD DE VOUCHERS VENDIDOS ANUALES DE HOME ILS 
		$query2 = "SELECT
			orders.prefijo,
			MONTH (orders.fecha) AS mes,
			COUNT(orders.cantidad) AS cantidad,
			clients.client AS description
		FROM
			orders
		JOIN clients ON clients.prefix = orders.prefijo
		WHERE
			orders. STATUS IN (1, 3)
		AND YEAR (orders.fecha) = '$year'
		AND IFNULL(inactive_platform, 0) <> 2
		AND clients.data_activa = 'SI'
		GROUP BY
			orders.prefijo,
			mes
		ORDER BY description asc";
		$respGraf2 = $this->selectDynamic('', '', '', '', $query2, '', '', '', '');
		$mountDesc = [
			'01' => 'Enero',
			'02' => 'Febrero',
			'03' => 'Marzo',
			'04' => 'Abril',
			'05' => 'Mayo',
			'06' => 'Junio',
			'07' => 'Julio',
			'08' => 'Agosto',
			'09' => 'Septiembre',
			'10' => 'Octubre',
			'11' => 'Noviembre',
			'12' => 'Diciembre',
		];
		foreach ($respGraf2 as $element) {
			$clientsAnual[$element['prefijo']][(int) $element['mes']] = (int) $element['cantidad'] ?: 0;
			$clientsAnual[$element['prefijo']]['description'] = $element['description'];
		}
		foreach ($clientsAnual as $key1 => $val) {
			$seriesAnual = [];
			foreach ($mountDesc as $key2 => $value) {
				if ($key2 <= date('m')) {
					$seriesAnual[] = (int) $val[(int) $key2] ?: 0;
				}
			}
			$clientAnual[] = [
				'name' => $val['description'],
				'data' => $seriesAnual,
			];
		}
		//VENTAS NETAS TODAS LAS AGENCIAS DEL AÑO ACTUAL
		$query3 = "SELECT
			orders.prefijo,
			MONTH (orders.fecha) AS mes,
			SUM(orders.neto_prov) AS neto,
			clients.client AS description
		FROM
			orders
		JOIN clients ON clients.prefix = orders.prefijo
		WHERE
			orders. STATUS IN (1, 3)
		AND IFNULL(inactive_platform, 0) <> 2
		AND clients.data_activa = 'SI'
		AND orders.fecha BETWEEN '$year-01-01'
		AND NOW()
		GROUP BY
			orders.prefijo,
			mes
		ORDER BY description ASC";
		$respGraf3 = $this->selectDynamic('', '', '', '', $query3);
		//AQUI SE CONSULTA LOS MESES TRANSCURRIDOS EN VENTAS PARA MOSTRAR LOS MESES DINAMICAMENTE EN LAS GRAFICAS
		$meses = "SELECT DISTINCT
			MONTH (orders.fecha) AS mes,
			DATE_FORMAT(orders.fecha, '%M') AS nameMes
		FROM
			orders
		JOIN clients ON clients.prefix = orders.prefijo
		WHERE
			orders. STATUS IN (1, 3)
		AND IFNULL(inactive_platform, 0) <> 2
		AND YEAR (orders.fecha) = '$year'
		AND clients.data_activa = 'SI'
		ORDER BY
			MONTH (orders.fecha) ASC";
		$respMeses = $this->selectDynamic('', '', '', '', $meses);
		foreach ($respMeses as $element) {
			$Months[(int) $element['mes']] = $element['nameMes'];
		}
		foreach ($respGraf3 as $element) {
			$SalInt[$element['prefijo']]['ventas'][(int) $element['mes']] = (float) $element['neto'] ?: 0;
			$SalInt[$element['prefijo']]['description'] = $element['description'];
		}
		foreach ($SalInt as $val) {
			$setSal = [];
			foreach ($Months as $key2 => $value) {
				$setSal[] = (float) $val['ventas'][(int) $key2] ?: 0;
			}
			$AnSales[] = [
				'name' => $val['description'],
				'data' => $setSal,
			];
		}
		return [[$series, $drilldown], [$clientAnual], [$AnSales]];
	}
	////////////////////////GRAFICOS PARA LA SEGUNDA PESTAÑA DE GENERAL/TOTAL HOME ILS
	public function getGrafGenAgenGeneral($filters)
	{
		$yearBus  = $filters['yearBus'];
		$mesBus   = $filters['mesBus'];
		$yearActual = date('Y');
		$dataValida	= [
			'50002'	=> $yearBus,
			'50003'	=> $mesBus,
		];
		$validatEmpty  = $this->validatEmpty($dataValida);
		/////////////////query para consultar meses del a;o y poder graficar bien las graficas de columnas de higcharts
		$queryMeses = "SELECT DISTINCT
			MONTH (orders.fecha) AS mes,
			DATE_FORMAT(orders.fecha, '%M') AS nameMes
		FROM
			orders
		JOIN clients ON clients.prefix = orders.prefijo
		WHERE
			orders. STATUS IN (1, 3)
		AND IFNULL(inactive_platform, 0) <> 2
		AND YEAR (orders.fecha) = '2019'
		AND clients.data_activa = 'SI'
		ORDER BY
			MONTH (orders.fecha) ASC";
		$respMeses = $this->selectDynamic('', '', '', '', $queryMeses, '', '', '', '');
		foreach ($respMeses as $element) {
			$Months[(int) $element['mes']] = $element['nameMes'];
		}
		////////////////////GRAFICA DE TORTA DRILLDOWN DE AGENCIAS VOUCHERS ACTIVOS GENERAL/TOTAL
		$query1 = "SELECT
			orders.prefijo,
			COUNT(orders. STATUS) AS cantidad,
			clients.client AS description,
				(
					SELECT
						plan_category.name_plan
					FROM
						plans
					JOIN plan_category ON plan_category.id_plan_categoria = plans.id_plan_categoria
					AND plan_category.prefijo = plans.prefijo
					WHERE
						orders.producto = plans.id
					AND orders.prefijo = plans.prefijo
					LIMIT 1
				) AS category
			FROM
				orders
			JOIN clients ON clients.prefix = orders.prefijo
			WHERE
				clients.data_activa = 'SI'
			AND orders.`status` IN (1, 3)";
		if ($mesBus == 'ALL') {
			$query1 .= "AND YEAR(orders.fecha) = '$yearBus'";
		} else {
			$query1 .= "AND YEAR (orders.fecha) = '$yearBus'
			AND MONTH (orders.fecha) = '$mesBus'";
		}
		$query1 .= "AND IFNULL(inactive_platform, 0) <> 2
			GROUP BY
				orders.prefijo,
				category
			ORDER BY
				description ASC";
		$respGraf1 = $this->selectDynamic('', '', '', '', $query1, '', '', '', '');
		$VouchCant = [];
		foreach ($respGraf1 as $element) {
			$VouchCant[$element['description']] += (int) $element['cantidad'] ?: 0;
			$VouchNet[$element['description']] = $element['category'];
			$drillVouch[$element['description']][] = [$element['category'], (int) $element['cantidad']];
		}
		$seriesVouch = [];
		$drilldownVouch = [];
		foreach ($VouchNet as $key => $val) {
			$seriesVouch[] = [
				'name' => $key,
				'y' => (float) $VouchCant[$key],
				'drilldown' => $key,
			];
			$drilldownVouch[] = [
				'name' => $key,
				'id' => $key,
				'data' => $drillVouch[$key],
			];
		}
		///////////grafica de vouchers activos de netos 
		$query2 = "SELECT
			orders.prefijo,
			clients.client AS description,
			MONTH (orders.fecha) AS mes,
			SUM(orders.neto_prov) AS neto,
				(
					SELECT
						plan_category.name_plan
					FROM
						plans
					JOIN plan_category ON plan_category.id_plan_categoria = plans.id_plan_categoria
					AND plan_category.prefijo = plans.prefijo
					WHERE
						orders.producto = plans.id
					AND orders.prefijo = plans.prefijo
					LIMIT 1
				) AS category
			FROM
				orders
			JOIN clients ON clients.prefix = orders.prefijo
			WHERE
				clients.data_activa = 'SI'
			AND orders.`status` IN (1, 3)";
		if ($mesBus == 'ALL') {
			$query2 .= "AND YEAR(orders.fecha) = '$yearBus'";
		} else {
			$query2 .= "AND YEAR (orders.fecha) = '$yearBus'
				AND MONTH (orders.fecha) = '$mesBus'";
		}
		$query2 .= "AND IFNULL(inactive_platform, 0) <> 2
			GROUP BY
				orders.prefijo,
				category, mes
			ORDER BY
				description ASC";
		$respGraf2 = $this->selectDynamic('', '', '', '', $query2, '', '', '', '');
		$VouchNum = [];
		foreach ($respGraf2 as $element) {
			$VouchNum[$element['description']] += (float) $element['neto'] ?: 0;
			$VouchNeto[$element['description']] = $element['category'];
			$drillNet[$element['description']][] = [$element['category'], (float) $element['neto']];
		}
		$seriesNeto = [];
		$drilldownNeto = [];
		foreach ($VouchNeto as $key => $val) {
			$seriesNeto[] = [
				'name' => $key,
				'y' => (float) $VouchNum[$key],
				'drilldown' => $key,
			];
			$drilldownNeto[] = [
				'name' => $key,
				'id' => $key,
				'data' => $drillNet[$key],
			];
		}
		////////grafico de origenes
		$query3 = "SELECT
			orders.prefijo,
			clients.client,
			orders.origen,
			COUNT(orders.cantidad) AS cantidad,
			(
				SELECT
					description
				FROM
					countries
				WHERE
					iso_country = orders.origen
				LIMIT 1
			) AS country
		FROM
			orders
		JOIN clients ON clients.prefix = orders.prefijo
		WHERE
			clients.data_activa = 'SI'
		AND orders.`status` IN (1, 3)";
		if ($mesBus == 'ALL') {
			$query3 .= "AND YEAR(orders.fecha) = '$yearBus'";
		} else {
			$query3 .= "AND YEAR (orders.fecha) = '$yearBus'
			AND MONTH (orders.fecha) = '$mesBus'";
		}
		$query3 .= "AND IFNULL(inactive_platform, 0) <> 2
		GROUP BY
			prefijo,origen
		ORDER BY
			country ASC";
		$respGraf3 = $this->selectDynamic('', '', '', '', $query3, '', '', '', '');
		$OrigNum = [];
		foreach ($respGraf3 as $element) {
			$OrigNum[$element['client']] += (int) $element['cantidad'] ?: 0;
			$OrigClient[$element['client']] = $element['country'];
			$drillOrig[$element['client']][] = [$element['country'], (int) $element['cantidad']];
		}
		$seriesOrig = [];
		$drilldownOrig = [];
		foreach ($OrigClient as $key => $val) {
			$seriesOrig[] = [
				'name' => $key,
				'y' => (int) $OrigNum[$key],
				'drilldown' => $key,
			];
			$drilldownOrig[] = [
				'name' => $key,
				'id' => $key,
				'data' => $drillOrig[$key],
			];
		}
		//////////grafica de cantidad de ventas por mes grafico de columna
		$query4 = "SELECT
			orders.prefijo,
			MONTH (orders.fecha) AS mes,
			COUNT(orders.cantidad) AS cantidad,
			clients.client AS description,
			DATE_FORMAT(orders.fecha, '%M') AS nameMes
		FROM
			orders
		JOIN clients ON clients.prefix = orders.prefijo
		WHERE
			orders. STATUS IN (1, 3)
		AND IFNULL(inactive_platform, 0) <> 2
		AND clients.data_activa = 'SI'";
		if ($mesBus == 'ALL') {
			$query4 .= "AND YEAR(orders.fecha) = '$yearBus'";
		} else {
			$query4 .= "AND YEAR (orders.fecha) = '$yearBus'
			AND MONTH (orders.fecha) = '$mesBus'";
		}
		$query4 .= " GROUP BY
			orders.prefijo,
			mes
		ORDER BY
			mes,
			client ASC";
		$respGraf4 = $this->selectDynamic('', '', '', '', $query4, '', '', '', '');
		$mountDesc = [
			'01' => 'Enero',
			'02' => 'Febrero',
			'03' => 'Marzo',
			'04' => 'Abril',
			'05' => 'Mayo',
			'06' => 'Junio',
			'07' => 'Julio',
			'08' => 'Agosto',
			'09' => 'Septiembre',
			'10' => 'Octubre',
			'11' => 'Noviembre',
			'12' => 'Diciembre',
		];
		$monts1 = [];
		foreach ($respGraf4 as $element) {
			$timeInt[$element['prefijo']]['ventas'][$element['nameMes']] = (int) $element['cantidad'] ?: 0;
			$timeInt[$element['prefijo']]['description'] = $element['description'];
			$monts1[$element['mes']] = $element['nameMes'];
		}
		foreach ($timeInt as $val) {
			$setMonth = [];
			foreach ($val as $key2 => $value) {
				foreach ($value as $key3 => $value2) {
					$setMonth[] = (int) $val['ventas'][$key3] ?: 0;
				}
			}
			$MonthInt[] = [
				'name' => $val['description'],
				'data' => $setMonth,
			];
		}
		///////////////////GRAFICO DE COLUMNAS DE NETO DE VENTAS 
		$query5 = "SELECT
			orders.prefijo,
			MONTH (orders.fecha) AS mes,
			SUM(orders.neto_prov) AS neto,
			clients.client AS description,
			DATE_FORMAT(orders.fecha, '%M') AS nameMes
		FROM
			orders
		JOIN clients ON clients.prefix = orders.prefijo
		WHERE
			orders. STATUS IN (1, 3)
		AND IFNULL(inactive_platform, 0) <> 2
		AND clients.data_activa = 'SI'";
		if ($mesBus == 'ALL') {
			$query5 .= "AND YEAR(orders.fecha) = '$yearBus'";
		} else {
			$query5 .= "AND YEAR (orders.fecha) = '$yearBus'
			AND MONTH (orders.fecha) = '$mesBus'";
		}
		$query5 .= " GROUP BY
			orders.prefijo,
			mes
		ORDER BY
			mes,
			client ASC";
		$respGraf5 = $this->selectDynamic('', '', '', '', $query5, '', '', '', '');
		$monts = [];
		foreach ($respGraf5 as $element) {
			$SalInt[$element['prefijo']]['ventas'][$element['nameMes']] = (float) $element['neto'] ?: 0;
			$SalInt[$element['prefijo']]['description'] = $element['description'];
			$monts[$element['mes']] = $element['nameMes'];
		}
		foreach ($SalInt as $val) {
			$setSal = [];
			foreach ($val as $key2 => $value) {
				foreach ($value as $key3 => $value2) {
					$setSal[] = (float) $val['ventas'][$key3] ?: 0;
				}
			}
			$AnSales[] = [
				'name' => $val['description'],
				'data' => $setSal,
			];
		}
		/////////////GRAFICA DRILL DOWN COLUMNAS DE EDADES CON FILTRO DE FECHA
		$queryClientes = "SELECT
			client,
			id_status,
			prefix
		FROM
			clients
		WHERE
			IFNULL(inactive_platform, 0) <> 2
		AND id_status = '1'
		AND data_activa = 'SI'
		ORDER BY
			client ASC";
		$respClientes = $this->selectDynamic('', '', '', '', $queryClientes, '', '', '', '');
		$query6 = "SELECT
			orders.prefijo,
			beneficiaries.sexo AS sexo,
			TIMESTAMPDIFF(
				YEAR,
				beneficiaries.nacimiento,
				orders.fecha
			) AS edad,
			COUNT(*) AS cant
		FROM
			orders
		JOIN clients ON clients.prefix = orders.prefijo
		JOIN beneficiaries ON beneficiaries.id_orden = orders.id
		AND orders.prefijo = beneficiaries.prefijo
		WHERE
			orders. STATUS IN (1, 3)
		AND IFNULL(inactive_platform, 0) <> 2
		AND clients.data_activa = 'SI'";
		if ($mesBus == 'ALL') {
			$query6 .= "AND YEAR(orders.fecha) = '$yearBus'";
		} else {
			$query6 .= "AND YEAR (orders.fecha) = '$yearBus'
			AND MONTH (orders.fecha) = '$mesBus'";
		}
		$query6 .= "GROUP BY
			prefijo,
			sexo,
			edad
		ORDER BY
			prefijo,
			edad ASC";
		$respGraf6 = $this->selectDynamic('', '', '', '', $query6, '', '', '', '');
		foreach ($respGraf6 as &$element) {
			statistics::EdadResult($BarD[$element['prefijo']], $element['edad'], $element['sexo'], $element['cant']);
			statistics::EdadResult($BarA, $element['edad'], $element['sexo'], $element['cant']);
		}
		$IntEdad = [
			'0-10',
			'11-20',
			'21-30',
			'31-40',
			'41-50',
			'51-60',
			'61-70',
			'71-75',
			'76-84',
			'85+',
		];
		$sex  = [
			"F",
			"M",
			"N/A",
		];
		$SerEd = [];
		foreach ($BarA as $sexo => $edad) {
			$SexEdad = [];
			$dataEd = [];
			foreach ($IntEdad  as  $key2 => $values) {
				$SexEdad[] = [
					'name' => $values,
					'y' => (int) $edad[$values] ?: 0,
					'drilldown' => $sexo . '-' . $values,
				];
			}
			$SerEd[] = [
				'name' => $sexo,
				'data' => $SexEdad,
			];
		}
		$dataEdad = [];
		foreach ($BarD as $prefix => $element) {
			foreach ($element as $sex => $val) {
				foreach ($val  as $edad => $values) {
					foreach ($respClientes as $key3) {
						if ($prefix == $key3['prefix']) {
							$dataEdad[$sex . '-' . $edad][] = [
								$key3['client'], (int) $values ?: 0
							];
						}
					}
				}
			}
		}
		$DrillEd = [];
		foreach ($dataEdad as $key => $value) {
			$DrillEd[] = [
				'id' => $key,
				'data' => $value,
			];
		}
		////////////////// grafica de ventas de a;os anteriores columnas
		$yearInicio = 2017; ///array para filtrar solo del 2017 al a;o actual
		$yearsBus = '';
		for ($i = $yearInicio; $i <= $yearActual; $i++) {
			$yearsBus .= $i . ',';
		}
		$yearsBus = substr($yearsBus, 0, -1); ///aqui suprimo la ultima coma
		$query7 = "SELECT
			orders.prefijo,
			YEAR (orders.fecha) AS anio,
			COUNT(orders.cantidad) AS cantidad,
			clients.client AS description
		FROM
			orders
		JOIN clients ON clients.prefix = orders.prefijo
		WHERE
			orders. STATUS IN (1, 3)
		AND YEAR (orders.fecha) IN ($yearsBus)
		AND IFNULL(inactive_platform, 0) <> 2
		AND clients.data_activa = 'SI'
		GROUP BY
			orders.prefijo,
			anio
		ORDER BY
			orders.prefijo ASC,
			anio DESC";
		$respQuery7 = $this->selectDynamic('', '', '', '', $query7, '', '', '', '');
		foreach ($respQuery7 as $element) {
			$temp[$element['anio']][$element['prefijo']] = (int) $element['cantidad'] ?: 0;
		}
		foreach ($temp as $anio => $element) {
			$clAn1 = [];
			foreach ($respClientes as $key) {
				$clAn1[] = (int) $element[$key['prefix']] ?: 0;
				$platName[] = $element['client'] ?: 0;
			}
			$final[] = [
				'name' => $anio,
				'data' => $clAn1
			];
		}
		$platName = [];
		foreach ($respClientes as $element) {
			$platName[] = $element['client'] ?: 0;
		}
		return [
			[$seriesVouch, $drilldownVouch],
			[$seriesNeto, $drilldownNeto],
			[$seriesOrig, $drilldownOrig],
			[$MonthInt, array_values($monts1)],
			[$AnSales, array_values($monts)],
			[$SerEd, $DrillEd],
			[$final, $platName],
			'YEAR' => $yearBus
		];
	}

	////////////////////////////////////////////// GRAFICOS PARA CADA AGENCIA ////////////////////
	public function getChartVouchersPie($filters)
	{
		$prefix	    = $filters['prefix'];
		$startDate  = $filters['startDate'];
		$endDate   	= $filters['endDate'];
		$today 	   	= date('Y-m-d');
		$dataValida	= [
			"9092"  => $prefix,
			'3030'	=> (!empty($endDate) && !empty($endDate)) ? !(strtotime($startDate) > strtotime($endDate)) : true,
			'9068'	=> (!empty($endDate) && !empty($endDate)) ? !(strtotime($endDate)	> strtotime($today)) : true,
			'9069'	=> (!empty($endDate) && !empty($endDate)) ? !(strtotime($startDate)	> strtotime($today)) : true,
		];
		$this->validatEmpty($dataValida);

		$IntEdad = [
			'0-10',
			'11-20',
			'21-30',
			'31-40',
			'41-50',
			'51-60',
			'61-70',
			'71-75',
			'76-84',
			'85+',
		];
		//    GRAFICA VOUCHERS CATEGORIAS 
		$query1 = "SELECT
		COUNT(orders.cantidad) AS cantidad,
		(
			SELECT
				plan_category.name_plan
			FROM
				plans
			JOIN plan_category ON plan_category.id_plan_categoria = plans.id_plan_categoria
			AND plan_category.prefijo = plans.prefijo
			WHERE
				orders.producto = plans.id
			AND orders.prefijo = plans.prefijo
			LIMIT 1
		) AS category
		FROM
			orders
		JOIN clients ON clients.prefix = orders.prefijo
		WHERE
			orders. STATUS IN (1, 3)
		AND DATE(orders.fecha) BETWEEN '$startDate'
		AND '$endDate'
		AND IFNULL(inactive_platform, 0) <> 2
		AND orders.prefijo = '$prefix'
		AND clients.data_activa = 'SI'
		GROUP BY
			category
		ORDER BY
			category";
		$respGraf1 = $this->selectDynamic('', '', '', '', $query1, '', '', '', '');
		$respGraf1 = array_reduce(
			$respGraf1,
			function ($response, $value) {
				$response[] = [
					'name' => $value['category'],
					'y' => (int) $value['cantidad'],
				];
				return $response;
			},
			[]
		);
		//GRAFICA 2 NETOS VOUCHERS
		$query2 = "SELECT 
		SUM(orders.neto_prov) AS neto,
		REPLACE (
			(
				SELECT
					plans.`name`
				FROM
					plans
				WHERE
					orders.producto = plans.id
				AND orders.prefijo = plans.prefijo
				LIMIT 1
			),
			'''',
			''
		) AS name_plan
		FROM
			orders
		JOIN clients ON clients.prefix = orders.prefijo
		WHERE
			orders. STATUS IN (1, 3)
		AND DATE(orders.fecha) BETWEEN '$startDate'
		AND '$endDate'
		AND IFNULL(inactive_platform, 0) <> 2
		AND orders.prefijo = '$prefix'
		AND clients.data_activa = 'SI'
		GROUP BY
			name_plan
		ORDER BY
			name_plan ASC";
		$respGraf2 = $this->selectDynamic('', '', '', '', $query2, '', '', '', '');
		$respGraf2 = array_reduce(
			$respGraf2,
			function ($response, $value) {
				$response[] = [
					'name' => $value['name_plan'],
					'y' =>  (float) $value['neto']
				];
				return $response;
			},
			[]
		);
		//GRAFICO 3 DE PAISES PARA VOUCHERS
		$query3 = "SELECT
		orders.origen,
		COUNT(orders.cantidad) AS cantidad,
			(
				SELECT
					description
				FROM
					countries
				WHERE
					iso_country = orders.origen
				LIMIT 1
			) AS country
		FROM
			orders
		JOIN clients ON clients.prefix = orders.prefijo
		WHERE
			orders. STATUS IN (1, 3)
		AND DATE( orders.fecha) BETWEEN '$startDate'
		AND '$endDate'
		AND IFNULL(inactive_platform, 0) <> 2
		AND orders.prefijo = '$prefix'
		AND clients.data_activa = 'SI'
		GROUP BY
			orders.origen
		ORDER BY
			country ASC";
		$respGraf3 = $this->selectDynamic('', '', '', '', $query3, '', '', '', '');
		$respGraf3 = array_reduce(
			$respGraf3,
			function ($response, $value) {
				$response[] = [
					'name' => $value['country'],
					'y' => (int) $value['cantidad'],
				];
				return $response;
			},
			[]
		);
		//GRAFICA DE LINEAS EDAD Y SEXO
		$query4 = "SELECT
		orders.prefijo,
		beneficiaries.sexo AS sexo,
			TIMESTAMPDIFF(
				YEAR,
				beneficiaries.nacimiento,
				orders.fecha
			) AS edad,
			COUNT(*) AS cant
		FROM
			orders
		JOIN clients ON clients.prefix = orders.prefijo
		JOIN beneficiaries ON beneficiaries.id_orden = orders.id
		AND orders.prefijo = beneficiaries.prefijo
		AND clients.data_activa = 'SI'
		AND orders. STATUS IN (1, 3)
		AND DATE(orders.fecha) BETWEEN DATE('$startDate')
		AND DATE('$endDate')
		AND IFNULL(inactive_platform, 0) <> 2
		AND orders.prefijo = '$prefix'
		GROUP BY
			prefijo,
			sexo,
			edad
		ORDER BY
			prefijo,
			edad ASC";

		$respGraf4 = $this->selectDynamic('', '', '', '', $query4, '', '', '', '');

		$IntEdad2 = [
			'S-E',
			'0-10',
			'11-20',
			'21-30',
			'31-40',
			'41-50',
			'51-60',
			'61-70',
			'71-75',
			'76-84',
			'85+',
		];

		$BarA = [];
		$BarD = [];
		foreach ($respGraf4 as &$element) {
			statistics::EdadResult($BarD[$element['prefijo']], $element['edad'], $element['sexo'], $element['cant']);
			statistics::EdadResult($BarA, $element['edad'], $element['sexo'], $element['cant']);
		}
		$SexEdad = [];
		$data = [];

		foreach ($BarD  as $key => $val) {
			foreach ($val  as $key1 => $value) {
				$data = [];
				foreach ($IntEdad  as  $key2 => $values) {
					$data[] = (int) $value[$values] ?: 0;
				}
				$SexEdad[] = [
					'name' => $key1,
					'data' => $data,
				];
			}
		}

		//////GRAFICAS PLATAFORMA de agencias
		$sqlBroker = "SELECT
			REPLACE( broker.broker,'''','') AS broker,
			COUNT(orders.id) AS cant,
			SUM(orders.total) AS monto,
			broker.id_broker AS id_broker
		FROM
			broker
		INNER JOIN orders ON orders.agencia = broker.id_broker
		AND broker.prefijo = orders.prefijo
		WHERE
			DATE(orders.fecha) BETWEEN DATE('$startDate')
		AND DATE('$endDate')
		AND orders. STATUS IN (1, 3)
		AND orders.prefijo = '$prefix'
		GROUP BY
			orders.prefijo,
			broker
		ORDER BY
			cant DESC";

		$arrCounterData = $this->selectDynamic('', '', '', '', $sqlBroker, '', '', '', '');
		$cntCounterData = count($arrCounterData);

		$sqlBrokerMonto = "SELECT
			REPLACE( broker.broker,'''','') AS broker,
			COUNT(orders.id) AS cant,
			SUM(orders.total) AS monto,
			broker.id_broker AS id_broker
		FROM
			broker
		INNER JOIN orders ON orders.agencia = broker.id_broker
		AND broker.prefijo = orders.prefijo
		WHERE
			DATE(orders.fecha) BETWEEN DATE('$startDate')
		AND DATE('$endDate')
		AND orders. STATUS IN (1, 3)
		AND orders.prefijo = '$prefix'
		GROUP BY
			orders.prefijo,
			broker
		ORDER BY
			cant DESC";

		$arrAmountData = $this->selectDynamic('', '', '', '', $sqlBrokerMonto, '', '', '', '');

		$cntData = $cntCounterData;
		$arrAmount  = [];
		$arrCount   = [];
		$sortAmount = [];
		$sortCount  = [];
		$totalAmount = [0, 0];
		$totalConter = [0, 0];
		$arrDataCnt  = [
			[
				'id' => 1,
				'name' => '20 Primeros',
				'data' => []
			]
		];
		if ($cntData > 20) {
			$arrDataCnt[1] = [
				'id' => 2,
				'name' => 'Otros',
				'data' => []
			];
		}
		$arrDataAmn = [
			[
				'id' => 1,
				'name' => '20 Primeros',
				'data' => []
			]
		];
		if ($cntData > 20) {
			$arrDataAmn[1] = [
				'id' => 2,
				'name' => 'Otros',
				'data' => []
			];
		}

		for ($i = 0; $i < $cntData; $i++) {
			$group = ($i < 20) ? 0 : 1;
			$totalCount = $arrCounterData[$i]['cant'];
			$totalPrice = $arrAmountData[$i]['monto'];

			settype($totalCount, 'integer');
			settype($totalPrice, 'float');

			$totalAmount[$group] += $totalPrice;
			$totalConter[$group] += $totalCount;

			$sortCount[$group][] = $totalCount;
			$sortAmount[$group][] = $totalPrice;

			$arrCount[$group][] = [$arrCounterData[$i]['broker'], $totalCount];
			$arrAmount[$group][] = [$arrAmountData[$i]['broker'], $totalPrice];
		}

		$arrDataCnt[0]['data'] = $arrCount[0];
		$arrDataCnt[1]['data'] = $arrCount[1];
		$arrDataAmn[0]['data'] = $arrAmount[0];
		$arrDataAmn[1]['data'] = $arrAmount[1];

		$seriesCnt = [
			[
				'y' => $totalConter[0],
				'drilldown' => 1,
				'name' => '20 Primeros',
			]
		];
		if ($cntData > 10) {
			$seriesCnt[1] = [
				'y' => $totalConter[1],
				'drilldown' => 2,
				'name' => 'Otros',
			];
		}
		$seriesAmn = [
			[
				'y' => $totalAmount[0],
				'drilldown' => 1,
				'name' => '20 Primeros',
			]
		];
		if ($cntData > 10) {
			$seriesAmn[1] = [
				'y' => $totalAmount[1],
				'drilldown' => 2,
				'name' => 'Otros',
			];
		}

		///////GRAFICAS EDADES CANTIDAD
		$grafEdCantidad = $this->grafVentEdCantidad($prefix, $startDate, $endDate);

		$BarAPlatf2 = [];
		$BarDPlatf2 = [];
		foreach ($grafEdCantidad as &$element) {
			statistics::EdadResult($BarDPlatf2[$element['prefijo']], $element['edad'], $element['sexo'],  $element['cant']);
			statistics::EdadResult($BarAPlatf2, $element['edad'], $element['sexo'],  $element['cant']);
		}
		$SexEdad2 = [];
		$data3 = [];

		foreach ($BarDPlatf2  as $key => $val) {
			foreach ($val  as $key1 => $value) {
				$data3 = [];
				foreach ($IntEdad2  as  $key2 => $values) {
					$data3[] = (float) $value[$values] ?: 0;
				}
				$SexEdad2[] = [
					'name' => $key1,
					'data' => $data3,
				];
			}
		}

		/////GRAFICAS PLATAFORMA DE EDADES / VENTAS MONTO
		$grafEdMonto = $this->grafVentEdMonto($prefix, $startDate, $endDate);

		$BarAPlatf3 = [];
		$BarDPlatf3 = [];
		foreach ($grafEdMonto as &$element) {
			statistics::EdadResult($BarDPlatf3[$element['prefijo']], $element['edad'], $element['sexo'],  $element['neto']);
			statistics::EdadResult($BarAPlatf3, $element['edad'], $element['sexo'],  $element['neto']);
		}
		$SexEdadPlatf3 = [];
		$data3 = [];

		foreach ($BarDPlatf3  as $key => $val) {
			foreach ($val  as $key1 => $value) {
				$data3 = [];
				foreach ($IntEdad2  as  $key2 => $values) {
					$data3[] = (float) $value[$values] ?: 0;
				}
				$SexEdadPlatf3[] = [
					'name' => $key1,
					'data' => $data3,
				];
			}
		}

		return [
			$respGraf1,
			$respGraf2,
			$respGraf3,
			$SexEdad,
			array([
				'arrDataCnt' => $arrDataCnt,
				'arrDataAmn' => $arrDataAmn,
				'seriesCnt' => $seriesCnt,
				'seriesAmn' => $seriesAmn
			]),
			$SexEdad2,
			$SexEdadPlatf3,
			$this->grafTipoVenta($prefix, $startDate, $endDate, false),
			$this->grafTipoVenta($prefix, '', '', true),
		];
	}

	/*  public function getBrokers($filters,$limit){
        $fields =
        [
            "id_broker",
            "broker",
            "id_status",
            "id_country",
            "id_state",
            "id_city",
            "zip_code",
            "credito_base",
            "credito_actual",
            "id_bmi",
            "type_broker",
            "prefijo",
            "forma_pago",
            "opcion_plan"
        ];
        return $this->selectDynamic($filters,'broker','1',$fields);
	} 
	public function getVouchers($data,$api){
		$code		= $data['codigo'];
		$language	= $data['lenguaje'];
		$dataValida	= [
			'1020'	=> $code,
			'1030'	=> !empty($language)?$this->validLanguage($language):true
		];
		$validatEmpty	= $this->validatEmpty($dataValida);
		if(!empty($validatEmpty)){
			return $validatEmpty;
		}
		$datAgency 		= $this->datAgency($api);
		$idUser			= $datAgency[0]['user_id'];
		$getDataOders	= $this->getDataOders($code, $idUser);
		$passenger 		= $this->dataBeneficiaries($code);
		$agency			= $getDataOders['agencia'];
		$language		= ($language=='spa' || empty($language))?'es':'en';
		if ($getDataOders){
			if ($getDataOders['status']!= 1) {
				return $this->getError('1021');
			}else{
				$dato['id'] 				= $getDataOders['id'];
				$dato['origen'] 			= $getDataOders['origen'];
				$dato['destino'] 			= $getDataOders['destino'];
				$dato['salida'] 			= $getDataOders['salida'];
				$dato['retorno'] 			= $getDataOders['retorno'];
				$dato['programaplan'] 		= $getDataOders['programaplan'];
				$dato['nombre_contacto'] 	= $getDataOders['nombre_contacto'];
				$dato['email_contacto'] 	= $getDataOders['email_contacto'];
				$dato['comentarios'] 		= $getDataOders['comentarios'];
				$dato['telefono_contacto'] 	= $getDataOders['telefono_contacto'];
				$dato['producto'] 			= $getDataOders['producto'];
				$dato['agencia'] 			= $getDataOders['agencia'];
				$dato['nombre_agencia'] 	= $getDataOders['nombre_agencia'];
				$dato['total'] 				= $getDataOders['total'];
				$dato['codigo'] 			= $getDataOders['codigo'];
				$dato['fecha'] 				= $getDataOders['fecha'];
				$dato['vendedor'] 			= $getDataOders['vendedor'];
				$dato['cantidad'] 			= $getDataOders['cantidad'];
				$dato['status'] 			= $getDataOders['status'];
				$dato['origin_ip'] 			= $getDataOders['origin_ip'];
				$dato['tasa_cambio'] 		= $getDataOders['tasa_cambio'];
				$dato['family_plan'] 		= $getDataOders['family_plan'];
				$dato['referencia'] 		= $getDataOders['referencia'];
				$dato['link'] 				= LINK_REPORTE_VENTAS.$getDataOders['codigo']."&selectLanguage=$language&broker_session=$agency";
				$dato['beneficiaries'] 		= $passenger;
				return	$dato;
			}
		}else{
			return $this->getError('1020');
		}
    }
    public function getPvpPrice($data,$api){
		$plan		= $data['plan'];
		$country	= $data['pais'];
		$prefix		= $data['prefix'];
		$dataValida	= [
			'9092'	=> $prefix,
			'6037'	=> !(empty($plan) AND empty($country)),
			'6022'	=> $plan
		];
		$validatEmpty	= $this->validatEmpty($dataValida);
		if(!empty($validatEmpty)){
			return $validatEmpty;
		}
		$restrictionPlan	= $this->verifyRestrictionPlan('',$plan,'',false,$api,true);
		if($restrictionPlan){
			return $restrictionPlan;
		}
		$verifyRestrictionOrigin	= $this->verifyRestrictionOrigin($plan,$country,$prefix);
		if(!empty($country)){
			if($verifyRestrictionOrigin){
				return $verifyRestrictionOrigin;
			}
		}else{
			$country	= 'all';
		}
		$data	= [
			'valor',
			'age_min',
			'age_max'
		];
		$dataPvpPriceBandas	= $this->selectDynamic('','plan_band_age',"id_plan='$plan'",$data);
		if(!empty($dataPvpPriceBandas)){
			return $dataPvpPriceBandas;
		}else{
			$filters = [
				'id_country' => $country
			];
			$dataPvpPrice	= $this->selectDynamic($filters,'plan_times',"id_plan='$plan'",['unidad','tiempo','valor']);
			if(!empty($dataPvpPrice)){
				return $dataPvpPrice;
			}else{
				return $this->getError('1060');
			}
		}
    }
    public function getTerms($data,$api){
		$plan							= $data['plan'];
		$language						= $data['lenguaje'];
		$dataValida	= [
			'6022'	=> $plan,
			'6021'	=> $language,
			'1030'	=> $this->validLanguage($language)
		];
		$validatEmpty	= $this->validatEmpty($dataValida);
		if(!empty($validatEmpty)){
			return $validatEmpty;
		}
		$restrictionPlan		= $this->verifyRestrictionPlan('',$plan,'',false,$api,true);
		if($restrictionPlan){
			return $restrictionPlan;
		}
		$dataPlan			= $this->selectDynamic('','plans',"id='$plan'",array("name","description"));
		$datAgency			= $this->datAgency($api);
		$namePlan			= $dataPlan[0]['name'];
		$descriptionPlan	= $dataPlan[0]['description'];
		$idAgency			= $datAgency[0]['id_broker'];
		return [
			'id'			=> $plan,
			'name'			=> $namePlan,
			'description'	=> $descriptionPlan,
			'terms'			=> $this->getTermsMaster($plan,$language,$idAgency)
		];
    }
    public function getBeneficiariesByCode($data,$api){
		$code			= $data['code'];
		$dataValida		= [
			'6023'	=> $code
		];
		$validatEmpty	= $this->validatEmpty($dataValida);
		if(!empty($validatEmpty)){
			return $validatEmpty;
		}
		$datAgency	= $this->datAgency($api);
		$id_broker 	= $datAgency[0]['id_broker'];
		$idUser 	= $datAgency[0]['user_id'];
		$isoCountry = $datAgency[0]['id_country'];
		$verifyVoucher 		= $this->verifyVoucher($code,$idUser,$isoCountry,'ADD');
		if($verifyVoucher){
			return $verifyVoucher;
		}
		return $this->dataBeneficiaries($code,'1');
	}
	public function getCountryRestricted($data,$api){
		$plan			= $data['plan'];
		$dataValida		= [
			'6022'	=> $plan
		];
		$validatEmpty	= $this->validatEmpty($dataValida);
		if(!empty($validatEmpty)){
			return $validatEmpty;
		}
		$restrictionPlan		= $this->verifyRestrictionPlan('',$plan,'',false,$api,true);
		if($restrictionPlan){
			return $restrictionPlan;
		}
		$dataCountryRestricted = $this->dataCountryRestricted($plan);
		if(!empty($dataCountryRestricted)){
			return $dataCountryRestricted;
		}else{
			return [
				'status'	=> "No hay resultados",
				'message'	=> "No hay restricción de países para el plan seleccionado"
			];
		}
	}
	public function getUpgrades($data,$api){
		$plan 		= $data['plan'];
		$language	= $data['lenguaje'];
		$dataValida	= [
			'6037'	=> !(empty($plan) AND empty($language)),
			'9024'	=> $plan,
			'6021'	=> $language,
			'1030'	=> $this->validLanguage($language)
		];
		$validatEmpty	= $this->validatEmpty($dataValida);
		if(!empty($validatEmpty)){
			return $validatEmpty;
		}
		$restrictionPlan		= $this->verifyRestrictionPlan('',$plan,'',false,$api,true);
		if($restrictionPlan){
			return $restrictionPlan;
		}
		$arrTypeUpgrades	= [
			'1' =>[
				'type_raider' => 'Valor' ,
				'rd_calc_type'=> 'Comprobante'
			],
			'2' =>[
				'type_raider' => 'Porcentage %' ,
				'rd_calc_type'=> 'Pasajero Especifico'
			],
			'3' =>[
				'type_raider' => 'Valor' ,
				'rd_calc_type'=> 'Pasajero General'
			],
			'4' =>[
				'type_raider' => 'Valor' ,
				'rd_calc_type'=> 'Por dia por Voucher'
			],
			'5' =>[
				'type_raider' => 'Valor' ,
				'rd_calc_type'=> 'Por dia por Pasajero'
			]
		];
		$result = $this->dataUpgradesPlan($plan,$language);
		if ($result){
			foreach ($result as $i => $value) {
				$arrResult[]['type_raider']		= $arrTypeUpgrades[$value['type_raider']]['type_raider'];
				$arrResult[$i]['rd_calc_type']	= $arrTypeUpgrades[$value['rd_calc_type']]['rd_calc_type'];
				$arrResult[$i]['id_raider']		= $value['id_raider'];
				$arrResult[$i]['cost_raider']	= $value['cost_raider'];
				$arrResult[$i]['name_raider']	= $value['name_raider'];
				$arrResult[$i]['value_raider']	= $value['value_raider'];
			}
			return $arrResult;
		}else{
			return $this->getError('5017');
		}
	}
	public function getSalesReport($data,$api){
		$startDate	= $data["desde"];
		$endDate	= $data["hasta"];
		$status		= $data["estatus"];
		$format		= $data["formato"];
		$startDateTrans = $this->transformerDate($startDate);
		$endDateTrans	= $this->transformerDate($endDate);
		$today 	= date('Y-m-d');
		$format	= strtolower($format);
		$ArrayValida	= [
			'6037'	=> !(empty($startDate) AND empty($endDate) AND empty($status)),
			'9063'	=> $startDate,
			'9064'	=> $endDate,
			'9065'	=> $status,
			'9017'	=> (in_array($status,[1,2,3])),
			'9066'	=> (in_array($format,['','json','excel'])),
			'3020'	=> $this->checkDates($startDate),
			'9067'	=> $this->checkDates($endDate),
			'3030'	=> !(strtotime($startDateTrans)	> strtotime($endDateTrans)),
			'9068'	=> !(strtotime($endDateTrans)	> strtotime($today)),
			'9069'	=> !(strtotime($startDateTrans)	> strtotime($today)),
		];
		$valid	= $this->validatEmpty($ArrayValida);
		if(!empty($valid)){
			return $valid;
		}
		switch ($status) {
			case 2:
				$statusEx = 5;
				break;
			case 3:
				$statusEx = 6;
				$status	= '';	
			break;
			default:
				$statusEx = 1;
				break;
		}
		$dataAgency	= $this->datAgency($api);
		$idUser		= $dataAgency['user_id'];
		$arrParameters 	= [
			'wbs'	=> 1,
			'desde'	=> $startDateTrans,
			'hasta'	=> $endDateTrans,
			'id_status'	=> $statusEx,
			'rangofecha'=> 1,
			'IdUser'	=> $idUser
		];
		$parameters = http_build_query($arrParameters);
		$urReport	= 'https://ilsbsys.com/app/reports/rpt_xls_reporte_ils.php?';
		$urlShort	= $this->shortUrl($urReport.$parameters);
		switch ($format) {
			case 'json':
				$response   =  $this->GetOrderdetallada_ils('',$startDateTrans,$endDateTrans,'',1, $status, '', '', 'orden','', $idUser,'spa',1);
				break;
			default:
				$response	= [
					'status' => 'OK',
					'Enlace de Descarga'=> trim($urlShort)
				];
				break;
		}
		if(empty($response)){
			$response = $this->getError('9015');
		}
		return $response;
	}
	public function getExchangeRate($data){
		$isoCountry = $data['iso_country'];
		$dataExchangeRate	= $this->dataExchangeRate($isoCountry);
		if(!empty($dataExchangeRate)){
			return $dataExchangeRate;
		}else{
			return [
				'status'	=> "No hay resultados",
				'message'	=> "No hay tasa de cambio asociada a éste país"
			];
		}
	}
	public function getRegionCountry(){
		return $this->dataCountryRegion();
	}
	public function getPlansCategory($data,$api){
		$language	= $data['lenguaje'];
		$dataValida	= [
			'6021'	=> $language,
			'1030'	=> $this->validLanguage($language)
		];
		$validatEmpty	= $this->validatEmpty($dataValida);
		if(!empty($validatEmpty)){
			return $validatEmpty;
		}
		return $this->dataPlanCategory($language);
	}
	public function getLanguages(){
		return $this->selectDynamic('','languages',"languages.active = '1'",['id','lg_id','name','short_name']);
	}
	public function getCurrencies(){
		return $this->selectDynamic('','currency',"id_status='1'",['id_currency','value_iso','desc_small'],'','',11);
	}
	public function getCountries(){
		return $this->selectDynamic('','countries',"c_status='Y'",['iso_country','description'],'','',260);
	}
	public function getRegions(){
		return $this->selectDynamic('','territory',"id_status='1'",['id_territory','desc_small']);
	}
*/
}
