<?php 
class get_functions extends general_functions{
	public function get_fuctions($function,$apikey)
	{
        $response   = $this->$function($_GET,$apikey);
        $countResponse  = count($response); 
        $this->logsave(json_encode($filters),json_encode($response),$function,'4',$apikey);
        ($countResponse!=0)?$this->response($response,'',$format):$this->getError(9015,'',$format);
    }
	public function getPlans($filters,$api)
	{
		$language	= $filters['lenguaje'];
        $plan       = $filters['plan'];
		$dataValida	= [
			'6021'	=> $language,
			'1030'	=> $this->validLanguage($language)
		];
		$validatEmpty	= $this->validatEmpty($dataValida);
		if(!empty($validatEmpty)){
			return $validatEmpty;
		}
		$datAgency			= $this->datAgency($api); 
		$idAgency			= $datAgency[0]['id_broker'];
		return $this->verifyRestrictionPlan($idAgency,$plan,$language,true);
    }
	public function getCoverages($data,$api)
	{
		$plan		= $data['plan'];
		$prefix		= $data['prefix'];
		$language	= strtolower($data['lenguaje']);
		$dataValida	= [
			'6037'	=> !(empty($plan) AND empty($language)),
			'6022'	=> $plan,
			'6021'	=> $language,
			'1030'	=> in_array($language,['spa','eng','por','fra','deu'])
		];
		$validatEmpty	= $this->validatEmpty($dataValida);
		if(!empty($validatEmpty)){
			return $validatEmpty;
		}
		//$restrictionPlan	= $this->verifyRestrictionPlan('',$plan,$language,false,$api,true);
		// if($restrictionPlan){
		// 	return $restrictionPlan;
		// }
		return $this->dataCoverages($language,$plan,$prefix);
	}
	public function getClients()
	{
		return $this->selectDynamic('','clients',"data_activa='si' AND id_status= '1' AND IFNULL(inactive_platform, 0) <> 2 ORDER BY client ASC",['client','id_country','img_cliente','web','urlPrueba','prefix','type_platform','id_broker','email','colors_platform'],'','','','','');
	}
	public function getCountries()
	{
		return $this->selectDynamic('','countries',"c_status='Y'",['iso_country','description'],'',['min'=>'0','max'=>'349'],['field'=>'description','order'=>'ASC']);
	}
	public function getStates()
	{
		return $this->selectDynamic('','','','',"SELECT
										iso_country,
										iso_state,
										description,
										nameID
									FROM
										`states`
									ORDER BY
										iso_country ASC",'','','','');
	}
	public function getCitys()
	{
		return $this->selectDynamic('','','','',"SELECT
										iso_city,
										description
									FROM
										`cities`
									ORDER BY
										iso_city ASC",'','','','');
	}
	public function checkVersionApp()
	{
		return $this->selectDynamic('','versions_app',"status='Y'",['version','fecha_version'],'',['min'=>'0','max'=>'1'],['field'=>'id_version','order'=>'DESC']);
	}
	public function checkVersionAppA($filters)
	{
		$prefix    = $filters['prefix'];
		$plataforma	   = $filters['plataforma'];
		$dataValida	= [
			"9092"  => $prefix,
			"50001"  => $plataforma
		];
		$validatEmpty  = $this->validatEmpty($dataValida);
		return $this->selectDynamic('','versions_app',"status='Y' AND prefijo = '$prefix' AND plataforma= '$plataforma'",['version','fecha_version','descripcion'],'',['min'=>'0','max'=>'1'],['field'=>'id_version','order'=>'DESC'],'','');
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
		return $this->selectDynamic('','plans',"id = $idPlan AND prefijo = '$prefix'",['id_plans','id','name','description','activo','id_plan_categoria'],'','',['field'=>'name','order'=>'ASC'],'','') ;
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
		return $this->selectDynamic('','users_extern',"id = $idVendedor AND prefijo = '$prefix'",['firstname','lastname','email'],'','','','','') ;
	}
	public function getAgencyParam($filters)
	{
		$prefix	    = $filters['prefix'];
		$dataValida	= [
			"9092"  => $prefix
		];
		$validatEmpty  = $this->validatEmpty($dataValida);
		return $this->selectDynamic('','clients',"prefix = '$prefix' AND data_activa = 'si' AND id_status= '1' AND IFNULL(inactive_platform, 0) <> 2",['client','phone1','cod_phone2','phone2','cod_phone3','phone3','cod_phone4','phone4','address','id_country','img_cliente','id_country','id_city','zip_code','date_up','id_state','web','urlPrueba','prefix','type_platform','id_broker','email','colors_platform'],'','','','','','') ;
	}


	public function getOrders($filters,$apikey)
	{
		$document  = $filters['document'];
		$code	   = $filters['code'];
		$name      = $filters['name'];
		$prefix	   = $filters['prefix'];
		$userType  = ($filters['userType'])?$filters['userType']:1;
		$startDate = $filters['startDate'];
		$endDate   = $filters['endDate'];
		$source    = $filters['source'];
		$min	   = ($filters['min']<=0 || empty($filters['min']))?0:$filters['min'];
		$max	   = ($filters['max']<=0 || empty($filters['max'] || ($filters['max']<=$filters['min'])) )?50:$filters['max'];
		$status	   = ($filters['status'])?$filters['status']:1;
		$today 	   = date('Y-m-d');

		$dataValida	= [
			"9092"  => $prefix,
			"9017"  => !empty($status)?in_array($status,[1,2,3,4,5]):true,
			'3030'	=> (!empty($endDate)&&!empty($endDate))?!(strtotime($startDate)>strtotime($endDate)):true,
			'9068'	=> (!empty($endDate)&&!empty($endDate))?!(strtotime($endDate)	> strtotime($today)):true,
			'9069'	=> (!empty($endDate)&&!empty($endDate))?!(strtotime($startDate)	> strtotime($today)):true,
		];


		$validatEmpty	= $this->validatEmpty($dataValida);
		if(!empty($validatEmpty)){
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
			'nombre_contacto',
			'email_contacto',
			'telefono_contacto',
			'nombre_agencia',
			'status',
			'cantidad',
			'referencia',
			'territory',
			'producto'
		];    
		
		if($source !='public'){
			array_push($valueOrders,
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
		if(!empty($idBroker) && !in_array($userType,[1,13])){
			$arrWhere ['agencia']= $idBroker;
		}
		if(!empty($code)){
			$codeWhere="codigo LIKE '%$code%'";
		}

		$arrWhere ['orders.prefijo']= $prefix;
		$arrLimit = ['min'=>$min,'max'=>$max];
		if(!empty($name)){
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

		if(!empty($document)){
			
			$arrJoin = [
				'table'=>'beneficiaries',
				'field'=>"id_orden AND beneficiaries.prefijo = orders.prefijo AND beneficiaries.documento LIKE '%$document%'",
				'fieldComp'=>'id'
			];
			$arrLimit = ['min'=>$min,'max'=>$max];
			array_push($valueOrders, 'beneficiaries.documento');
		}

		if(!empty($startDate) && !empty($endDate)){
			$between = [
				'start' => $startDate,
				'end'   => $endDate,
				'field' => 'fecha'
			];
		}
		if(!empty($pagination)){
			$arrPagination  = implode(',',$pagination);
			if(is_array($arrPagination)){

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
			["field"=>"fecha","order"=>"DESC"],
			$between,
			$arrJoin
		);
 
		$arrBeneficiaries = [];
		$cDataOrders = count($dataOrders);

		for ($i=0; $i < $cDataOrders; $i++) { 
			$idOrder = $dataOrders[$i]['id'];

			$dataOrders[$i]['categoria'] = $this->selectDynamic('','','','', 
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
			AND orders.prefijo = '$prefix'")[0]['categoria']?:'N/A';

			$dataOrders[$i]['beneficiaries'] = $this->selectDynamic(
				['beneficiaries.prefijo'=>$prefix],
				'beneficiaries',
				"id_orden='$idOrder'",
				['id','id_orden','nombre','apellido','documento','email','nacimiento','nacionalidad','tipo_doc','telefono'],
				'',
				'',
				[
					'field'=>'nombre',
					'order'=>'ASC'
				],
				'',
				''
			);
		}
		return $dataOrders;
	}

	public function getCategories($filters)
	{
		$quote	    = NEW quoteils();
		$prefix 	= $filters['prefix'];
		$dataValida	= [
			"9092"  => $prefix
		];
		$validatEmpty	= $this->validatEmpty($dataValida);
		$data = [
			'querys'=>"SELECT
				id_plan_categoria,
				name_plan
			FROM
				plan_category
			WHERE
				plan_category.id_status = 1
			AND EXISTS (
				SELECT
					id_plan_categoria,
					activo,
					eliminado
				FROM
					plans
				WHERE
					plan_category.id_plan_categoria = plans.id_plan_categoria
				AND NOT EXISTS (
					SELECT
						id_plans
					FROM
						restriction
					WHERE
						plans.id = restriction.id_plans
					AND id_broker > 0
				)
				AND plans.activo = 1
				AND plans.eliminado = 1
			)
			AND plan_category.vision_id = 1
			ORDER BY
				name_plan ASC"
		];
		$link 		= $this->selectDynamic(['prefix'=>$prefix],'clients',"data_activa='si'",['web'])[0]['web'];
		$linkParam 	= $link."/app/api/selectDynamic";
		$headers 	= "content-type: application/x-www-form-urlencoded";
		$response = $this->curlGeneral($linkParam,json_encode($data),$headers);
		return json_decode($response);
		//return $this->dataCategories($prefix);
	}
	public function getPrices($filters,$apikey)
	{
		$quote	   = NEW quoteils();
		$prefix	   = $filters['prefix'];
		$origin	   = $filters['origin'];
		$startDate = $filters['startDate'];
		$endDate   = $filters['endDate'];
		$destiny   = $filters['destiny'];
		$category  = $filters['category'];
		$ages	   = explode(',',$filters['ages']);
		$today 	   = date('Y-m-d');
		$dataValida	= [
			"9092"  => $prefix,
			"6027"  => $origin,
			"1080"  => !empty($destiny)?in_array($destiny,[1,2,9]):true,
			"6029"  => $startDate,
			"9094"  => $category,
			"6030"  => $endDate,
			'2001'	=> $this->checkDates($startDate),
			'2002'	=> $this->checkDates($endDate),
			'9095'	=> is_array($ages)
		];
		if(!empty($startDate) && !empty($endDate) ){
			$startDate  = $this->transformerDate($startDate);
			$endDate 	= $this->transformerDate($endDate);
		}
		$arrVerifyDate = [
			'3030'	=> (strtotime($startDate)  < strtotime($endDate)),
			'9068'	=> (strtotime($endDate)	> strtotime($today)),
			'9069'	=> (strtotime($startDate)	>= strtotime($today)),
		];
		$validatEmpty	= $this->validatEmpty($dataValida+$arrVerifyDate);
		$interval = $this->betweenDates($startDate,$endDate);
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
		return($dataPrices)?$dataPrices:$this->getError(1060);		
	}
	function get_cod_phones(){
		$query = "SELECT
                    iso_country,
                    description,
                    phone AS cod_phone
                FROM
                    countries
                WHERE
                    phone IS NOT NULL ";
        $query.=" AND c_status = 'Y'";
        if(!empty($code_phone)) $query.=" AND iso_numeric IS NOT NULL";
		$query.=" ORDER BY description ASC ";
		$this->selectDynamic('','','','',$query,'','','','');
        $result = $this->_SQL_tool($this->SELECT, METHOD, $query);
        return array_reduce($result,function($response,$element){
            $split = array_map(function($value)use($element){
                return [
                    'cod_phone'=>'+'.preg_replace('/[^0-9]+/', '', $value),
                    'iso_country'=>$element['iso_country'],
                    'description'=>$element['description'],
                ];
            },explode("and",$element['cod_phone']));
            return array_merge($response,$split);
        },[]);
        return $arrCountries;
	}
	public function getInformIls(){
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
	 	return $this->selectDynamic('','','','',$query,'','','','');
	}
	////////////////////////////////////////////// GRAFICOS DE TODAS LAS AGENCIAS ////////////////////
	public function getGrafGenAgen($filters){
		$startDate  = $filters['startDate'];
		$endDate   	= $filters['endDate'];
		$today 	   	= date('Y-m-d');
		$year       = date('Y');
		$dataValida	= [
			'3030'	=> (!empty($endDate)&&!empty($endDate))?!(strtotime($startDate)>strtotime($endDate)):true,
			'9068'	=> (!empty($endDate)&&!empty($endDate))?!(strtotime($endDate)	> strtotime($today)):true,
			'9069'	=> (!empty($endDate)&&!empty($endDate))?!(strtotime($startDate)	> strtotime($today)):true,
		];
		$validatEmpty  = $this->validatEmpty($dataValida);
		//GRAFICA DRILLDOWN DE VENTAS DIARIAS DE TODAS LAS AGENCIAS
		$query1 ="SELECT
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
		$respGraf1 = $this->selectDynamic('','','','',$query1,'','','','');
		foreach ($respGraf1 as $element) {
            $sumatori[$element['prefijo']]+=(int)$element['cantidad']?:0;
            $clientsName[$element['prefijo']]=$element['client'];
            $drilldownRaw[$element['prefijo']][]=[$element['name_plan'],(int)$element['cantidad']];
        }
        foreach ($clientsName as $key => $val) {
            $series[]=[
                'name'=>$val,
                'y'=>(int)$sumatori[$key],
                'drilldown'=>$key,
            ];
            $drilldown[]=[
                'name'=>$val,
                'id'=>$key,
                'data'=>$drilldownRaw[$key],
            ];
		}
		//GRAFICA CANTIDAD DE VOUCHERS VENDIDOS ANUALES DE HOME ILS 
		$query2="SELECT
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
			mes";
		$respGraf2 = $this->selectDynamic('','','','',$query2,'','','','');
		$mountDesc=[
			'01'=>'Enero',
			'02'=>'Febrero',
			'03'=>'Marzo',
			'04'=>'Abril',
			'05'=>'Mayo',
			'06'=>'Junio',
			'07'=>'Julio',
			'08'=>'Agosto',
			'09'=>'Septiembre',
			'10'=>'Octubre',
			'11'=>'Noviembre',
			'12'=>'Diciembre',
		];
		foreach ( $respGraf2 as $element) {
            $clientsAnual[$element['prefijo']][(int)$element['mes']]=(int)$element['cantidad']?:0;
            $clientsAnual[$element['prefijo']]['description']=$element['description'];
		}
		foreach ($clientsAnual as $key1 => $val) {
            $seriesAnual=[];
            foreach ($mountDesc as $key2 =>$value) {
				if ($key2 <= date('m')) {
					$seriesAnual[] = (int)$val[(int)$key2]?:0;
				}
            }
            $clientAnual[] = [
                'name' => $val['description'],
                'data' => $seriesAnual,
            ];
		}
		//VENTAS NETAS TODAS LAS AGENCIAS DEL AÑO ACTUAL
		$query3="SELECT
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
			mes";
		$respGraf3= $this->selectDynamic('','','','',$query3);
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
		$respMeses = $this->selectDynamic('','','','',$meses);
		foreach ( $respMeses as $element) {
            $Months[(int)$element['mes']]=$element['nameMes'];
        }
		foreach ( $respGraf3 as $element) {
            $SalInt[$element['prefijo']]['ventas'][(int)$element['mes']]=(float)$element['neto']?:0;
            $SalInt[$element['prefijo']]['description']=$element['description'];
        }
        foreach ($SalInt as $val) {
            $setSal=[];
            foreach ($Months as $key2 => $value ) {
                $setSal[] = (float)$val['ventas'][(int)$key2]?:0;
            }
            $AnSales[] = [
                'name' => $val['description'],
                'data' => $setSal,
            ];
		}
		return [[$series,$drilldown],[$clientAnual],[$AnSales]];
	}
////////////////////////GRAFICOS PARA LA SEGUNDA PESTAÑA DE GENERAL/TOTAL HOME ILS
	public function getGrafGenAgenGeneral($filters){
		$yearBus  = $filters['yearBus'];
		$mesBus   = $filters['mesBus'];
		$yearActual=date('Y');
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
		$respMeses = $this->selectDynamic('','','','',$queryMeses,'','','','');
		foreach ( $respMeses as $element) {
            $Months[(int)$element['mes']]=$element['nameMes'];
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
				$query1.="AND YEAR(orders.fecha) = '$yearBus'";
			}else{
			$query1.="AND YEAR (orders.fecha) = '$yearBus'
			AND MONTH (orders.fecha) = '$mesBus'";
			}
			$query1.="AND IFNULL(inactive_platform, 0) <> 2
			GROUP BY
				orders.prefijo,
				category
			ORDER BY
				description ASC";
		$respGraf1 = $this->selectDynamic('','','','',$query1,'','','','');
		$VouchCant = [];
        foreach ($respGraf1 as $element) {
            $VouchCant[$element['description']]+=(int)$element['cantidad']?:0;
            $VouchNet[$element['description']]=$element['category'];
            $drillVouch[$element['description']][]=[$element['category'],(int)$element['cantidad']];
        }
        $seriesVouch = [];
        $drilldownVouch = [];
        foreach ($VouchNet as $key => $val) {
            $seriesVouch[]=[
                'name'=>$key,
                'y'=>(float)$VouchCant[$key],
                'drilldown'=>$key,
            ];
            $drilldownVouch[]=[
                'name'=>$key,
                'id'=>$key,
                'data'=>$drillVouch[$key],
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
				$query2.="AND YEAR(orders.fecha) = '$yearBus'";
			}else{
				$query2.="AND YEAR (orders.fecha) = '$yearBus'
				AND MONTH (orders.fecha) = '$mesBus'";
			}
			$query2.="AND IFNULL(inactive_platform, 0) <> 2
			GROUP BY
				orders.prefijo,
				category, mes
			ORDER BY
				description ASC";
		$respGraf2 = $this->selectDynamic('','','','',$query2,'','','','');
		$VouchNum = [];
        foreach ($respGraf2 as $element) {
            $VouchNum[$element['description']]+=(float)$element['neto']?:0;
            $VouchNeto[$element['description']]=$element['category'];
            $drillNet[$element['description']][]=[$element['category'],(float)$element['neto']];
        }
        $seriesNeto = [];
        $drilldownNeto = [];
        foreach ($VouchNeto as $key => $val) {
            $seriesNeto[]=[
                'name'=>$key,
                'y'=>(float)$VouchNum[$key],
                'drilldown'=>$key,
            ];
            $drilldownNeto[]=[
                'name'=>$key,
                'id'=>$key,
                'data'=>$drillNet[$key],
            ];
        }
		////////grafico de origenes
		$query3 ="SELECT
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
			$query3.="AND YEAR(orders.fecha) = '$yearBus'";
		}else{
			$query3.="AND YEAR (orders.fecha) = '$yearBus'
			AND MONTH (orders.fecha) = '$mesBus'";
		}
		$query3.="AND IFNULL(inactive_platform, 0) <> 2
		GROUP BY
			prefijo,origen
		ORDER BY
			country ASC";
		$respGraf3 = $this->selectDynamic('','','','',$query3,'','','','');
		$OrigNum = [];
        foreach ($respGraf3 as $element) {
            $OrigNum[$element['client']]+=(int)$element['cantidad']?:0;
            $OrigClient[$element['client']]=$element['country'];
            $drillOrig[$element['client']][]=[$element['country'],(int)$element['cantidad']];
        }
        $seriesOrig = [];
        $drilldownOrig = [];
        foreach ( $OrigClient as $key => $val) {
            $seriesOrig[]=[
                'name'=>$key,
                'y'=>(int)$OrigNum[$key],
                'drilldown'=>$key,
            ];
            $drilldownOrig[]=[
                'name'=>$key,
                'id'=>$key,
                'data'=> $drillOrig[$key],
            ];
        }
		//////////grafica de cantidad de ventas por mes grafico de columna
		$query4 ="SELECT
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
			$query4.="AND YEAR(orders.fecha) = '$yearBus'";
		}else{
			$query4.="AND YEAR (orders.fecha) = '$yearBus'
			AND MONTH (orders.fecha) = '$mesBus'";
		}
		$query4.="GROUP BY
			orders.prefijo,
			mes
		ORDER BY
			mes,
			client ASC";
		$respGraf4 = $this->selectDynamic('','','','',$query4,'','','','');
		$mountDesc=[
			'01'=>'Enero',
			'02'=>'Febrero',
			'03'=>'Marzo',
			'04'=>'Abril',
			'05'=>'Mayo',
			'06'=>'Junio',
			'07'=>'Julio',
			'08'=>'Agosto',
			'09'=>'Septiembre',
			'10'=>'Octubre',
			'11'=>'Noviembre',
			'12'=>'Diciembre',
		];
		$monts1 = [];
        foreach ( $respGraf4 as $element) {
            $timeInt[$element['prefijo']]['ventas'][$element['nameMes']]=(int)$element['cantidad']?:0;
            $timeInt[$element['prefijo']]['description']=$element['description'];
            $monts1[$element['mes']]=$element['nameMes'];
        }
        foreach ($timeInt as $val) {
            $setMonth = [];
             foreach ($val as $key2 => $value ) {
                foreach ($value as $key3 => $value2 ) {
                    $setMonth[] = (int)$val['ventas'][$key3]?:0;
                }
            }
            $MonthInt[] = [
                'name' => $val['description'],
                'data' => $setMonth,
            ];
        }
		///////////////////GRAFICO DE COLUMNAS DE NETO DE VENTAS 
		$query5="SELECT
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
			$query5.="AND YEAR(orders.fecha) = '$yearBus'";
		}else{
			$query5.="AND YEAR (orders.fecha) = '$yearBus'
			AND MONTH (orders.fecha) = '$mesBus'";
		}
		$query5.="GROUP BY
			orders.prefijo,
			mes
		ORDER BY
			mes,
			client ASC";
		$respGraf5 = $this->selectDynamic('','','','',$query5,'','','','');
		$monts = [];
       	foreach ( $respGraf5 as $element) {
            $SalInt[$element['prefijo']]['ventas'][$element['nameMes']]=(float)$element['neto']?:0;
			$SalInt[$element['prefijo']]['description']=$element['description'];
			$monts[$element['mes']]=$element['nameMes'];
		}
        foreach ($SalInt as $val) {
            $setSal=[];
            foreach ($val as $key2 => $value ) {
                foreach ($value as $key3 => $value2 ) {
                    $setSal[] = (float)$val['ventas'][$key3]?:0;
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
		$respClientes = $this->selectDynamic('','','','',$queryClientes,'','','','');
		$query6="SELECT
			orders.prefijo,
			beneficiaries.sexo AS sexo,
			TIMESTAMPDIFF(
				YEAR,
				beneficiaries.nacimiento,
				orders.retorno
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
			$query6.="AND YEAR(orders.fecha) = '$yearBus'";
		}else{
			$query6.="AND YEAR (orders.fecha) = '$yearBus'
			AND MONTH (orders.fecha) = '$mesBus'";
		}
		$query6.="GROUP BY
			prefijo,
			sexo,
			edad
		ORDER BY
			prefijo,
			edad ASC";
		$respGraf6 = $this->selectDynamic('','','','',$query6,'','','','');
		foreach ($respGraf6 as &$element) {
            statistics::EdadResult($BarD[$element['prefijo']],$element['edad'],$element['sexo'],$element['cant']);
            statistics::EdadResult($BarA,$element['edad'],$element['sexo'],$element['cant']);
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
            foreach ( $IntEdad  as  $key2 => $values) {
                $SexEdad[] = [
                    'name' => $values,
                    'y' => (int)$edad[$values]?:0,
                    'drilldown' => $sexo.'-'.$values,
                ];
            }
            $SerEd[] =[
                'name' => $sexo,
                'data' => $SexEdad,
            ];                    
        }
		$dataEdad = [];
        foreach ($BarD as $prefix => $element) {
            foreach ($element as $sex => $val) {
                foreach ( $val  as $edad => $values) {
					foreach ($respClientes as $key3 ) {
						if ($prefix == $key3['prefix']) {
							$dataEdad[$sex.'-'.$edad][] = [
								$key3['client'],(int)$values?:0
							];
						}
					}
                }
            }
        }
        $DrillEd = [];
        foreach($dataEdad as $key => $value)
        {
            $DrillEd[] = [
                'id'=> $key,
                'data'=> $value,
            ];
        }
		////////////////// grafica de ventas de a;os anteriores columnas
		$yearInicio = 2017;///array para filtrar solo del 2017 al a;o actual
		$yearsBus='';
		for ($i=$yearInicio; $i <= $yearActual; $i++) { 
			$yearsBus.=$i.',';
		}
		$yearsBus = substr($yearsBus, 0, -1);///aqui suprimo la ultima coma
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
		$respQuery7 = $this->selectDynamic('','','','',$query7,'','','','');
		foreach ( $respQuery7 as $element) {
            $temp[$element['anio']][$element['prefijo']]=(int)$element['cantidad']?:0;
		}
        foreach ( $temp as $anio => $element) {
			$clAn1=[];
            foreach ($respClientes as $key) {
                $clAn1[]=(int)$element[$key['prefix']]?:0;
                $platName[]=$element['client']?:0;      
            }
            $final[] = [
                'name' => $anio,
                'data' => $clAn1
            ]; 
        }
        $platName=[];
        foreach ( $respClientes as $element) {
            $platName[]=$element['client']?:0;         
        }
		return [
			[$seriesVouch,$drilldownVouch],
			[$seriesNeto,$drilldownNeto],
			[$seriesOrig,$drilldownOrig],
			[$MonthInt,array_values($monts1)],
			[$AnSales,array_values($monts)],
			[$SerEd,$DrillEd],
			[$final,$platName],
			'YEAR' => $yearBus
		];
	}
	////////////////////////////////////////////// GRAFICOS PARA CADA AGENCIA ////////////////////
	public function getChartVouchersPie($filters){
		$prefix	    = $filters['prefix'];
		$startDate  = $filters['startDate'];
		$endDate   	= $filters['endDate'];
		$today 	   	= date('Y-m-d');
		$dataValida	= [
			"9092"  => $prefix,
			'3030'	=> (!empty($endDate)&&!empty($endDate))?!(strtotime($startDate)>strtotime($endDate)):true,
			'9068'	=> (!empty($endDate)&&!empty($endDate))?!(strtotime($endDate)	> strtotime($today)):true,
			'9069'	=> (!empty($endDate)&&!empty($endDate))?!(strtotime($startDate)	> strtotime($today)):true,
		];
		$validatEmpty  = $this->validatEmpty($dataValida);
		//    GRAFICA VOUCHERS CATEGORIAS 
		$query1 ="SELECT
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
		$respGraf1 = $this->selectDynamic('','','','',$query1,'','','','');
		$respGraf1 = array_reduce( $respGraf1, 
			function($response,$value){
				$response[] = [
					'name' => $value['category'],
					'y' => (int)$value['cantidad'],
				];
			return $response;
		},[]);
		//GRAFICA 2 NETOS VOUCHERS
		$query2 ="SELECT
		 SUM(orders.neto_prov) AS neto,
			(
				SELECT
					plans.`name`
				FROM
					plans
				WHERE
					orders.producto = plans.id
				AND orders.prefijo = plans.prefijo
				LIMIT 1
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
		$respGraf2 = $this->selectDynamic('','','','',$query2,'','','','');
		$respGraf2 = array_reduce( $respGraf2, 
			function($response,$value){
				$response[] = [
					'name' => $value['name_plan'],
					'y' => (float)$value['neto'],
				];
			return $response;
		},[]);
		//GRAFICO 3 DE PAISES PARA VOUCHERS
		$query3="SELECT
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
		$respGraf3 = $this->selectDynamic('','','','',$query3,'','','','');
		$respGraf3 = array_reduce($respGraf3, 
            function($response,$value){
                $response[] = [
                    'name'=> $value['country'],
                    'y' => (int)$value['cantidad'],
                ];
            return $response;
			},[]);
		//GRAFICA DE LINEAS EDAD Y SEXO
		$query4="SELECT
		orders.prefijo,
		beneficiaries.sexo AS sexo,
			TIMESTAMPDIFF(
				YEAR,
				beneficiaries.nacimiento,
				orders.retorno
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
		$respGraf4 = $this->selectDynamic('','','','',$query4,'','','','');
		$BarA=[];
		$BarD=[];
		foreach ($respGraf4 as &$element) {
			statistics::EdadResult($BarD[$element['prefijo']],$element['edad'],$element['sexo'],$element['cant']);
			statistics::EdadResult($BarA,$element['edad'],$element['sexo'],$element['cant']);
		}
		$SexEdad = [];
		$data = [];
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
		foreach ( $BarD  as $key => $val) {
			foreach ( $val  as $key1 => $value) {
				$data = [];
				foreach ( $IntEdad  as  $key2 => $values) {
					$data[] = (int)$value[$values]?:0;
				}
				$SexEdad[] = [
					'name' => $key1,
					'data' => $data,
				];
			}
		}
		return [$respGraf1, $respGraf2, $respGraf3, $SexEdad];
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
