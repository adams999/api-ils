<?php
ob_start();
require_once('../../lib/core.lib.php');
ob_end_clean();

class general_functions extends Model
{
    function __construct()
    {
        $ip = $this->getRemoteIP();
        $this->arrLanguage = [
            'spa',
            'eng',
            'deu',
            'por',
            'spa'
        ];
        $this->shortLang = [
            'spa'   => 'es',
            'eng'   => 'en',
            'deu'   => 'de',
            'por'   => 'pt'
        ];
    }
    public function getColumnsTable($table)
    {
        $query = "SELECT
            COLUMN_NAME AS fields,
			DATA_TYPE AS type
        FROM
            information_schema.COLUMNS
        WHERE
            TABLE_NAME = '$table'
        AND TABLE_SCHEMA = 'ilsbsys_db0'";
        return $this->_SQL_tool($this->SELECT, __METHOD__, $query);
    }
    function verifiedOrderDuplicate($salida = '', $retorno = '', $origen = '', $destino = '')
    {
        $query = "SELECT
                    GROUP_CONCAT(orders.id) ids
                FROM
                    orders
                WHERE status='1'
                AND orders.salida = '$salida'
                AND orders.retorno = '$retorno'
                AND orders.origen = '$origen'";
        if ($destino == 1 || $destino == 2 || $destino == 9) {
            $query .= " AND orders.territory = '$destino'";
        } else {
            $query .= " AND orders.destino = '$destino'";
        }
        return $this->selectDynamic('', '', '', '', $query)[0]['ids'] ?: false;
    }
    function countData($quantity, $vsquantity)
    {
        return (count($quantity) != $vsquantity) ? true : false;
    }
    function verifiedBeneficiariesDuplicate($id_orders, $documento, $nacimiento)
    {
        $documento  = implode("','", $documento);
        for ($i = 0; $i < count($nacimiento); $i++) {
            $nacimientos[] = $this->transformerDate($nacimiento[$i]);
        }
        $nacimientos = implode("','", $nacimientos);
        $query = "SELECT
            id
            FROM  beneficiaries WHERE beneficiaries.id_orden IN ($id_orders)
            AND beneficiaries.documento in('$documento')
            AND beneficiaries.nacimiento in('$nacimientos')";
        $result = $this->selectDynamic('', '', '', '', $query);
        (empty($result)) ?: $this->geterror('6045');
    }
    function insertDynamic($data = array(), $table = null)
    {
        if (empty($table) || count($data) == 0) {
            return false;
        }
        $arrFiels = array();
        $arrValues = array();
        $SQL_functions = array(
            'NOW()'
        );
        foreach ($data as $key => $value) {
            $arrFiels[] = '`' . $key . '`';
            if (in_array(strtoupper($value), $SQL_functions)) {
                $arrValues[] = strtoupper($value);
            } else {
                $arrValues[] = '\'' . $value . '\'';
            }
        }
        $query = "INSERT INTO $table (" . implode(',', $arrFiels) . ") VALUES (" . implode(',', $arrValues) . ")";
        return $this->_SQL_tool($this->INSERT, __METHOD__, $query);
    }
    function updateDynamic($table, $field, $fieldwere, $data)
    {
        (!empty($table)) ?: $table;
        (!empty($field)) ?: $field;
        (!empty($fieldwere)) ?: $fieldwere;
        $query = "UPDATE $table SET ";
        $cadQuery = '';
        foreach ($data as $key => $value) {
            if (!empty($value)) {
                $cadQuery .= $key . '=' . "'" . $value . "'" . ',';
            }
        }
        $query .= mb_strrchr($cadQuery, ',', true);
        $query .= " where $table.$field IN('$fieldwere')";
        $resp = $this->_SQL_tool($this->UPDATE, __METHOD__, $query);
        return ($query) ? true : false;
    }
    public function selectDynamic($filters, $table, $where = '1', $fields, $querys, $limit = 10, $orderby, $between, $innerJoin, $die)
    {
        if (empty($querys)) {
            $fields = !empty($fields) ? implode(',', $fields) : "*";
            $query = "SELECT $fields FROM $table ";
            if (is_array($innerJoin)) {
                $tableJoin = $innerJoin['table'];
                $fieldJoin = $innerJoin['field'];
                $fieldComp = $innerJoin['fieldComp'];
                $query .= " INNER JOIN $tableJoin ON $table.$fieldComp = $tableJoin.$fieldJoin";
            }
            $query .= " WHERE $where ";
            foreach ($filters as $campo => $value) {
                if (!empty($campo) && !empty($value)) {
                    $valor = addslashes($value);
                    $query .= " AND $campo = '$valor'";
                }
            }
            if (is_array($between)) {
                $start   = $between['start'];
                $end     = $between['end'];
                $fieldb  = $between['field'];
                $query .= " AND $fieldb BETWEEN '$start' AND '$end' ";
            }
            if (is_array($orderby)) {
                $fieldOr = $orderby['field'];
                $ordenOr = $orderby['order'];
                $query .= " ORDER BY $fieldOr $ordenOr ";
            }
            if (is_array($limit)) {
                $min = !empty($limit['min']) ? $limit['min'] : 0;
                $max = !empty($limit['max']) ? $limit['max'] : 50;
                $query .= " LIMIT $min,$max ";
            }
        } else {
            $query = $querys;
        }
        if ($die) {
            die($query);
        }
        return $this->_SQL_tool($this->SELECT, __METHOD__, $query);
    }
    public function getBrokersByApiKey($apikey)
    {
        $query = "SELECT
            user_associate.id_associate
        FROM
            users_extern
        INNER JOIN user_associate ON users_extern.id = user_associate.id_user
        WHERE
            api_key = '$apikey' ";
        return $this->_SQL_tool($this->SELECT_SINGLE, __METHOD__, $query)['id_associate'];
    }
    public function validLanguage($lng)
    {
        $lng   = strtolower($lng);
        $arrshortLang = [
            'spa'   => 'es',
            'eng'   => 'en',
            'deu'   => 'de',
            'por'   => 'pt'
        ];
        $shortLang  = $arrshortLang[$lng];
        $query = "SELECT
            languages.id
        FROM
            `languages`
        WHERE
            languages.active = '1'
        AND languages.lg_id = '$lng'
        AND languages.short_name = '$shortLang'";
        $response   = $this->selectDynamic('', '', '', '', $query);
        return count($response);
    }
    public function dataCoverages($language, $idPlan, $prefijo)
    {
        $lenguaje = in_array($language, ['spa', 'eng']) ? $language : 'spa';
        $query = "SELECT DISTINCT
            benefit_plan.id_beneficio,
            (CASE TRUE
                WHEN benefit_plan.valor_$lenguaje <> '' OR benefit_plan.valor_$lenguaje IS NOT NULL THEN
                    benefit_plan.valor_$lenguaje
                ELSE
                    benefit_plan.valor_eng
                END
            ) AS valor,
            benefit_type.`name` AS Type_beneficio,
            benefit.Type_benefit,
            benefit_detail.`name` AS `name`
        FROM
            benefit_plan
            INNER JOIN benefit ON benefit_plan.id_beneficio = benefit.id
                AND benefit_plan.prefijo = benefit.prefijo
                AND benefit.activo =  '1'
            INNER JOIN benefit_detail ON benefit.id = benefit_detail.id_benefit
                AND benefit.prefijo = benefit_detail.prefijo 
                AND benefit_detail.language_id = '$lenguaje'
            INNER JOIN benefit_type ON benefit.Type_benefit = benefit_type.id
                AND benefit.prefijo = benefit_type.prefijo
        WHERE benefit_plan.prefijo = '$prefijo'
            AND benefit_plan.id_plan =  '$idPlan'
            AND (benefit.eliminado IS NULL OR benefit.eliminado <> '2')
            AND (benefit_plan.eliminado IS NULL OR benefit_plan.eliminado <> '2')
            ORDER BY benefit_detail.`name` ASC";
        return $this->_SQL_tool($this->SELECT, __METHOD__, $query);
    }
    function setResponseXML($response)
    {
        $xml = new SimpleXMLElement('<?xml version="1.0"?><response/>');
        //array_walk_recursive($response, array ($xml, 'addChild'));
        for ($i = 0; $i < count($response); $i++) {
            $item = $xml->addChild('item');
            foreach ($response[$i] as $key => $value) {
                $item->addChild($key, $value);
            }
        }
        print $xml->asXML();
    }
    function response($response = "", $code = 200, $format = "JSON")
    {
        http_response_code('200');
        if (!empty($response)) {
            return ($format == 'XML') ? $this->setResponseXML($response) : die(json_encode($response, JSON_PRETTY_PRINT));
        }
        /*$xml = new SimpleXMLElement('<?xml version="1.0"?><main></main>');
        for ($i=0; $i <count($response) ; $i++) { 
            $item=$xml->addChild('item');
            foreach($response[$i] as $key=>$value) {
                $item->addChild($key, $value);
             }
        }*/
    }
    public function logsave($request, $response, $operacion, $procedencia, $apikey = '', $id_error = '', $num_voucher = '', $num_referencia = '')
    {
        $datAgency        = $this->datAgency($apikey);
        $id_user        = $_GET['id_user'];
        $prefijo        = !empty($request['prefix']) ? $request['prefix'] : 'ILS';
        $prefixApp      = $request['prefixApp'];
        $platfApp       = $_GET['platfApp'];
        $versionApp     = $_GET['versionAppApi'];
        $request        = json_encode($request);
        $_response      = mysql_real_escape_string($response);
        $_response      = (strlen($_response) > 10000) ? '' : $_response;
        $data   = [
            'fecha'             => 'NOW()',
            'hora'              => 'NOW()',
            'ip'                => $_SERVER['REMOTE_ADDR'],
            'operacion'         => $operacion,
            'datos'             => $request,
            'respuesta'         => $response,
            'prefijo'           => $prefijo,
            'procedencia'       => $procedencia,
            'apikey'            => $apikey,
            'id_error'          => $id_error,
            'num_voucher'       => '',
            'num_referencia'    => $num_referencia,
            'id_user'           => !empty($id_user) ? $id_user : 0,
            'prefixApp'         => $prefixApp,
            'platfApp'          => $platfApp,
            'versionApp'        => !empty($versionApp) ? $versionApp : 'DEV'
        ];

        $oldGeneralLog = $this->genera_log;
        $this->genera_log = false;

        $return = $this->insertDynamic($data, 'trans_all_webservice');

        $this->genera_log = $oldGeneralLog;

        return $return;
    }
    function getError($error, $code = 422, $format)
    {
        http_response_code($code);
        $query = "SELECT
        trans_errors.`Error Code`,
        trans_errors.message,
        trans_errors.notes
        FROM
            trans_errors
        WHERE
        trans_errors.`Error Code` = '$error'";
        $result = $this->_SQL_tool($this->SELECT, __METHOD__, $query);
        return ($format == 'XML') ? die($this->setResponseXML($result)) : die(json_encode($result, JSON_PRETTY_PRINT));
    }
    function getInputs($object)
    {
        $obj = json_decode(file_get_contents($object));
        return $objArr = (array) $obj;
    }
    function getCountryAgency($apikey)
    {
        $query = "SELECT
               broker.id_country
           FROM
               users
           Inner Join user_associate ON users.id = user_associate.id_user
           Inner Join broker ON user_associate.id_associate = broker.id_broker
           WHERE
               users.api_key =  '$apikey'";
        return $this->_SQL_tool($this->SELECT_SINGLE, __METHOD__, $query)['id_country'];
    }
    function sendEmailNotification($id_user, $nomb_user, $apel_user, $lang_user, $api_key, $email_from)
    {
        $CORE_email = new Email(array('smtpServer' => EMAIL_SERVER_HOST, 'smtpUser' => EMAIL_SERVER_USER, 'smtpPassword' => EMAIL_SERVER_PASS, 'appDomainRoot' => DOMAIN_ROOT, 'skeletonFile' => COREROOT . 'lib/common/email_skeleton.php', 'emailEngine' => EMAIL_ENGINE, 'transGroupID' => EMAIL_TRANSACTIONAL_GROUP_ID), array('debug' => EMAIL_DEBUG_SEND, 'emailDebug' => EMAIL_DEBUG));
        $query = "SELECT parameter_value, logo_empresa FROM parameters WHERE parameter_key = 'SYSTEM_NAME'";
        $empresa = $this->_SQL_tool($this->SELECT_SINGLE, __METHOD__, $query);
        $dirlogo = $empresa['logo_empresa'];
        $cliente = $empresa['parameter_value'];
        if (!empty($dirlogo) && file_exists(CORE_ROOT . "admin/pictures/thumbnail/" . $dirlogo)) {
            $url_logo  = DOMAIN_ROOT . "admin/pictures/thumbnail/" . $dirlogo;
        } else {
            $url_logo = DOMAIN_ROOT . "images/logo.png";
        }
        $variables_email = array(
            "##imagen##" => stripslashes($url_logo),
            "##Nombre##" => stripslashes(strip_tags($nomb_user)),
            "##Apellido##" => stripslashes(strip_tags($apel_user)),
            "##Cliente##" => stripslashes(strip_tags($cliente)),
            "##url##" => DOMAIN_ROOT
        );
        foreach ($variables_email as $varstr => $varvalue) {
            $CORE_email->setVariable($varstr, $varvalue);
        }
        //$this->log_ip($this->ip, $api_key, $id_user);
        $CORE_email->send($from = array('name' => EMAIL_FROM_NAME, 'email' => EMAIL_FROM), $to = array($email_from), 'WARNING_IP', $lang_user);
    }
    public function log_ip($ip, $apikey, $user)
    {
        $data   = [
            'ip' => $ip,
            'usuario' => $user,
            'apikey' => $apikey,
            'hora' => 'NOW()',
            'fecha' => 'NOW()'
        ];
        return $this->insertDynamic($data, 'user_ip');
    }
    function get_languages()
    {
        $query = "SELECT
                languages.id,
                languages.lg_id,
                languages.name,
                languages.short_name
            FROM `languages`
            WHERE
                languages.active = '1'";
        return $this->_SQL_tool($this->SELECT, __METHOD__, $query);
    }
    public function getremoteip()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        return $_SERVER['REMOTE_ADDR'];
    }
    public function cryppass($password)
    {
        $salt = "1NsT3pD3veL0p3R$";
        $password = hash('sha256', $salt . $password);
        return $password;
    }
    public function random_numeric_string($length = 12)
    {
        $chr = "0123456789ABCDEFGHIJKML";
        $str = "";
        while (strlen($str) < $length) {
            $str .= substr($chr, mt_rand(0, (strlen($chr))), 1);
        }
        return ($str);
    }
    public function checkapiKey($apikey)
    {
        $ip = $this->getremoteip();
        $query = "SELECT id FROM `users_extern` WHERE users_extern.api_key = '$apikey' ";
        $user = $this->_SQL_tool($this->SELECT_SINGLE, __METHOD__, $query);
        if ($user) {
            $this->apikey = $apikey;
            return true;
        } else {
            return false;
        }
    }
    function validatEmpty($parametros)
    {
        $array_keys = array_keys(array_filter($parametros, function ($key) {
            return empty($key);
        }));
        return (!empty($array_keys[0])) ? $this->getError($array_keys[0]) : false;
    }
    function timePerProduct($plan, $daysByPeople)
    {
        $unity = $this->selectDynamic('', 'plan_times', "id_plan='$plan'", array("unidad"))[0]['plan_times'];
        $time = ($unity == 'dias' or $unity == 'bloques') ?: $daysByPeople;
        $time = ($unity[0]['unidad'] == 'meses') ?: $time = $daysByPeople / 30;
        $time = ($unity[0]['unidad'] == 'semanas') ?: $time = $daysByPeople / 7;
        $getPlanTimes = $this->selectDynamic("tiempo='$time'", 'plan_times', "id_plan='$plan'", array("id"))[0]['id'];
        if (empty($getPlanTimes)) {
            $timeValue = $getPlanTimes;
        } else {
            $getPlanTimes = $this->selectDynamic("tiempo<'$time'", 'plan_times', "id_plan='$plan'", array("id"))[0]['id'];
            if ($getPlanTimes) {
                $timeValue = $getPlanTimes;
            } else {
                $getPlanTimes = $this->selectDynamic("", 'plan_times', "id_plan='$plan'", array("id"))[0]['id'];
                $timeValue = $getPlanTimes;
            }
        }
    }
    function add_beneficiaries($numero_documento, $nacimientos_aux, $nombre, $apellido, $telefono, $email, $id_orden, $status_ben, $precios, $costo, $observacion, $precio_local, $costo_local, $tax_beneficiario, $tax_local_beneficiario)
    {
        $data = [
            'documento' => $numero_documento,
            'nacimiento' => $nacimientos_aux,
            'nombre' => $nombre,
            'apellido' => $apellido,
            'telefono' => $telefono,
            'email' => $email,
            'id_orden' => $id_orden,
            'ben_status' => '1',
            'precio_vta' => $precios,
            'precio_cost' => $costo,
            'condicion_medica' => $observacion,
            'precio_vta_mlc' => $precio_local,
            'precio_cost_mlc' => $costo_local,
            'tax_total' => $tax_beneficiario,
            'tax_total_mlc' => $tax_local_beneficiario,
        ];
        return $this->insertDynamic($data, 'beneficiaries');
    }
    public function validatePlans($plan, $agency, $origin, $destination, $daysByPeople, $prefix)
    {
        $arrayValidate = [];
        if (!empty($agency)) {
            $arrayValidate[] = $this->verifyRestrictionPlan($agency, $plan, '', false, '', '', $prefix);
        }
        if (!empty($destination)) {
            $arrayValidate[] = $this->verifyRestrictionDestination($destination, $plan, $prefix);
        }
        if (!empty($origin)) {
            $arrayValidate[] = $this->verifyRestrictionOrigin($origin, $plan, $prefix);
        }
        if (!empty($daysByPeople)) {
            $arrayValidate[] = $this->verifyDaysPlan($daysByPeople, $plan, $prefix);
        }
        if (!empty($arrayValidate[0])) {
            return  $arrayValidate[0];
        }
    }
    function datAgency($apikey)
    {
        $query = "SELECT
        users.id AS user_id,
        broker.broker,
        broker.id_broker,
        users.id_country
        FROM
            users
        INNER JOIN user_associate ON users.id = user_associate.id_user
        INNER JOIN broker ON user_associate.id_associate = broker.id_broker
        WHERE
            users.api_key = '$apikey'";
        return $this->selectDynamic('', '', '', '', $query);
    }
    public function verifyRestrictionDestination($destination, $plan, $prefix)
    {
        $restrictionTerritory = $this->selectDynamic('', 'restriction', "id_plans='$plan'", array("id_territory_destino"))[0]['id_territory_destino'];
        if ($restrictionTerritory) {
            if ($restrictionTerritory != '0') {
                $query = "SELECT
                    territory.id_territory
                FROM
                    restriction
                    INNER JOIN territory ON restriction.id_territory_destino = territory.id_territory
                WHERE
                    restriction.id_plans = '$plan'
                    AND territory.id_territory = '$destination'
                    AND restriction.prefijo = '$prefix' ";
                $response = $this->selectDynamic('', '', '', '', $query);
                if (!$response) {
                    return $this->getError('1081');
                }
            }
        }
    }
    public function verifyRestrictionOrigin($origin, $plan, $prefix)
    {
        $query = "SELECT
        relaciotn_restriction.iso_country,
        countries.description
        FROM
            relaciotn_restriction
        INNER JOIN restriction ON relaciotn_restriction.id_restric = restriction.id_restric
        INNER JOIN countries ON relaciotn_restriction.iso_country = countries.iso_country
        WHERE
        restriction.id_plans = '$plan'
        AND countries.iso_country = '$origin'
        AND restriction.prefijo = '$prefix' ";
        $response = $this->selectDynamic('', '', '', '', $query);
        if ($response) {
            return $this->getError('1091');
        }
    }
    function transformerDate($date, $type = 1)
    {
        if ($type == '1') {
            $date = str_replace('/', '-', $date);
            $fecha = DateTime::createFromFormat('d-m-Y', $date);
            return $fecha ? $fecha->format('Y-m-d') : $date;
        } elseif ($type == '2') {
            $fecha = DateTime::createFromFormat('Y-m-d', $date);
            return $fecha ? $fecha->format('d/m/Y') : $date;
        }
    }
    function verifyMail($parametros)
    {
        if (is_array($parametros)) {
            return array_reduce($parametros, function ($resp, $value) {
                return $resp = filter_var($value, FILTER_VALIDATE_EMAIL) ? $resp : false;
            }, true);
        } else {
            return filter_var($parametros, FILTER_VALIDATE_EMAIL);
        }
    }
    public function verifyBenefits($dataQuoteGeneral)
    {
        $benefits    = [
            '1246'    => $dataQuoteGeneral[0]['error_age'],
            '1100'    => $dataQuoteGeneral[0]['error_broker'],
            '5003'    => $dataQuoteGeneral[0]['error_cant_passenger'],
            '1090'    => $dataQuoteGeneral[0]['error_country'],
            '1080'    => $dataQuoteGeneral[0]['error_territory'],
            '1247'    => $dataQuoteGeneral[0]['error_time']
        ];
        $filter = array_filter($benefits, function ($var) {
            return ($var == '0');
        });
        $filter = array_keys($filter);
        return !empty($filter[0]) ? $this->geterror($filter[0]) : false;
    }
    function calculateAge($birthDayPassenger, $isoCountry)
    {
        $this->setTimeZone($isoCountry);
        $birthDayPassenger = new DateTime($birthDayPassenger);
        $today = new DateTime();
        $difference = $today->diff($birthDayPassenger);
        return $difference->y;
    }
    function setAges($birthDayPassenger, $isoCountry)
    {
        foreach ($birthDayPassenger as $value) {
            $transformateValue = $this->transformerDate($value);
            $calculate[] = $this->calculateAge($transformateValue, $isoCountry);
        }
        return implode(',', $calculate);
    }
    function validateDateOrder($arrival, $departure, $isoCountry)
    {
        $this->setTimeZone($isoCountry);
        $implArrival = explode('-', $arrival);
        $implDeparture = explode('-', $departure);
        $checkArrival   = (checkdate($implArrival[1], $implArrival[2], $implArrival[0]));
        $checkDeparture = (checkdate($implDeparture[1], $implDeparture[2], $implDeparture[0]));
        $today = date('Y-m-d');
        if (!$checkArrival or empty($arrival)) {
            $this->getError('2001');
        } elseif (!$checkDeparture or empty($departure)) {
            $this->getError('2002');
        } elseif ($departure < $today or $arrival < $today) {
            $this->getError('2004');
        } elseif (!$checkArrival or !$checkDeparture) {
            $this->getError('3020');
        } elseif ($arrival == $departure || $departure > $arrival) {
            $this->getError('3030');
        }
    }
    function getDaysByPeople($departure, $arrival)
    {
        $query = "select DATEDIFF('$arrival','$departure') + 1 as dias";
        return $this->selectDynamic('', '', '', '', $query)[0]['dias'];
    }
    public function verifyDaysPlan($daysByPeople, $plan, $prefix)
    {
        $daysConfigPlan  = $this->selectDynamic(['prefijo' => $prefix], 'plans', "id='$plan'", array("min_tiempo", "max_tiempo"));
        if ($daysByPeople < $daysConfigPlan[0]['min_tiempo']) {
            return $this->getError('1248');
        }
        if ($daysByPeople > $daysConfigPlan[0]['max_tiempo']) {
            return $this->getError('1247');
        }
    }
    public function verifyRestrictionPlan($agency, $plan, $languaje, $details = false, $api, $simple, $prefix)
    {
        $agency     = (!empty($agency)) ? $agency : $this->datAgency($api)[0]['id_broker'];
        $choicePlan = $this->selectDynamic('', 'broker', "id_broker='$agency'", array("opcion_plan"))[0]['opcion_plan'];
        $query = "SELECT
            plans.id ";
        if ($details) {
            $query .= ", plan_detail.titulo,
                plan_detail.description,
                plan_detail.language_id,
                plan_detail.plan_id,
                plans.id_plan_categoria,
                plans.num_pas,
                plans.min_tiempo,
                plans.max_tiempo,
                plans.id_currence,
                plans.family_plan,
                plans.min_age,
                plans.max_age,
                plans.normal_age,
                plans.plan_local,
                plans.modo_plan,
                plans.original_id ";
        }
        $query .= " FROM
            plans
            INNER JOIN plan_detail ON plans.id = plan_detail.plan_id
            INNER JOIN restriction ON plans.id = restriction.id_plans
        ";
        ($details) ?: $where[] = " plans.id = '$plan'";
        (!$details && (empty($languaje))) ?: $where[] = " plan_detail.language_id = '$languaje'";
        $where[] = " plans.activo = '1' ";
        $where[] = " plans.eliminado = '1' ";
        $where[] = " plans.prefijo = '$prefix' ";
        $where[] = "(
                plans.modo_plan = 'W'
        )";
        if ($choicePlan == '1') {
            $where[] =
                "(
                restriction.dirigido = 1
                OR (restriction.dirigido = 2 AND restriction.id_broker = $agency)
                OR (restriction.dirigido = 6 AND restriction.id_broker = $agency)
            )";
        } else if ($choicePlan == '2') {
            $where[] =
                "(
                (restriction.dirigido = 2 AND restriction.id_broker = $agency)
                OR (restriction.dirigido = 6 AND restriction.id_broker = $agency)
            )";
        }
        $query .= (count($where) > 0 ? " WHERE " . implode(' AND ', $where) : " ");
        $response = $this->selectDynamic('', '', '', '', $query);
        if (!$response) {
            return $this->getError('1050');
        } elseif ($details) {
            return $response;
        }
    }
    public function dataBeneficiaries($idOrden, $status = 1, $document)
    {
        $query = "SELECT               
            beneficiaries.id,
            beneficiaries.id_orden,
            beneficiaries.nombre,
            beneficiaries.apellido,
            beneficiaries.email,
            beneficiaries.telefono,
            beneficiaries.nacimiento,
            beneficiaries.documento,
            beneficiaries.condicion_medica,
            beneficiaries.precio_vta,
            beneficiaries.precio_cost,
            beneficiaries.ben_status,
            IFNULL(beneficiaries.id_rider,'N/A') as raider
        FROM
            beneficiaries
            INNER JOIN orders ON orders.id = beneficiaries.id_orden
        where orders.codigo ='$idOrden'";
        if (!empty($status)) {
            $query .= " AND ben_status = '$status' ";
        }
        if (!empty($document)) {
            $query .= " AND documento IN ($document) ";
        }
        $response = $this->_SQL_tool($this->SELECT, __METHOD__, $query);
        return ($response) ? $response : $this->getError('9028');
    }
    public function getDataOders($code, $idUser)
    {
        $query = "SELECT
        orders.id,
        orders.origen,
        orders.destino,
        orders.salida,
        orders.retorno,
        orders.programaplan,
        orders.nombre_contacto,
        orders.email_contacto,
        orders.comentarios,
        orders.telefono_contacto,
        orders.producto,
        orders.agencia,
        orders.nombre_agencia,
        orders.total,
        orders.codigo,
        orders.fecha,
        orders.vendedor,
        orders.cantidad,
        orders.status,
        orders.es_emision_corp,
        orders.origin_ip,
        orders.alter_cur,
        orders.tasa_cambio,
        orders.family_plan,
        orders.referencia
        FROM
            orders
        WHERE
            orders.codigo ='$code'
        AND orders.vendedor ='$idUser'";
        return $this->_SQL_tool($this->SELECT_SINGLE, __METHOD__, $query);
    }
    public function getTermsMaster($plan, $language, $idAgency)
    {
        $filters   = [
            'id_status'     => '1',
            'type_document' => '1',
            'language_id'   => $language
        ];
        $termsPlan              = $this->selectDynamic($filters, 'plans_wording', "id_plan='$plan'", array("url_document"))[0]["url_document"];
        $termsAgency            = $this->selectDynamic("language_id='$language'", 'broker_parameters_detail', "id_broker='$idAgency'", array("imagen"))[0]["imagen"];
        $termsInsurance         = $this->selectDynamic("language_id='$language'", 'wording_parameter', "id_status='1'", array("url_document"))[0]["url_document"];
        $typeBroker             = $this->selectDynamic('', 'broker', "id_broker='$agency'", array("type_broker"))[0]["type_broker"];
        if (!empty($termsPlan)) {
            return  DOMAIN_APP . "/admin/server/php/files/" . $termsPlan;
        } elseif (!empty($termsAgency) && $typeBroker == "1") {
            return  DOMAIN_APP . "upload_files/broker_parameters/" . $agency . "/condicionados/" . $termsAgency;
        } elseif (!empty($termsInsurance)) {
            return  DOMAIN_APP . "/admin/server/php/files/" . $termsInsurance;
        }
    }
    public function verifyVoucher($code, $idUser, $isoCountry, $procedencia = 'ADD', $onlySelect, $skypCancel = true)
    {
        $this->setTimeZone($isoCountry);
        $query = "SELECT
               orders.status,
               orders.salida,
               orders.vendedor,
               orders.procedencia_funcion
           FROM
               orders
           where
               codigo ='$code'";
        $response = $this->_SQL_tool($this->SELECT_SINGLE, __METHOD__, $query);
        $dataValida        = [
            '1020'        => count($response),
            '1021'        => !($response['status'] == 5 && $response['procedencia_funcion'] == 0),
            '9018'      => ($response['vendedor'] == $idUser),
            '9019'      => !($response['procedencia_funcion'] == '0' && $procedencia == 'REPORT'),
            '4001'      => !(strtotime($response['salida']) <= strtotime(date('Y-m-d')))
        ];
        $validatEmpty    = $this->validatEmpty($dataValida);
        if (!empty($validatEmpty)) {
            return $validatEmpty;
        }
    }
    public function setTimeZone($isoCountry)
    {
        $timeZone   = $this->selectDynamic('', 'cities', "iso_country='$isoCountry'", array("Timezone"))[0]['Timezone'];
        $timeZone   = !empty($timeZone) ? $timeZone : 'America/Lima';
        ini_set('date.timezone', $timeZone);
    }
    public function getApiKey($user, $password)
    {
        $ip                    = $_SERVER['REMOTE_ADDR'];
        $dataValida            = [
            '6037'    => !(empty($login) and empty($password)),
            '6040'    => $user,
            '6041'    => $password
        ];
        $validatEmpty    = $this->validatEmpty($dataValida);
        if (!empty($validatEmpty)) {
            return $validatEmpty;
        }
        $passwordEncript     = $this->encriptKey($password);
        $data                = [
            "firstname",
            "lastname",
            "email",
            "api_key",
            "id_country",
            "ip_remote",
            "id",
            "language_id"
        ];
        $dataUser            = $this->selectDynamic(['users' => $user, 'id_status' => '1', 'user_type' => '9'], 'users_extern', "password='$passwordEncript'", $data);
        if (!$dataUser) {
            return $this->getError('1023');
        }
        if (empty($dataUser[0]['api_key'])) {
            $apiKey = $this->valueRandom(16);
            $data    = [
                'api_key'        => $apiKey,
                'ip_remote'        => $ip,
            ];
            $actDataOrder = $this->updateDynamic('users_extern', 'users', $user, $data, ["field" => "password", "value" => $passwordEncript]);
            $this->sendEmailNotification($dataUser[0]['id'], $dataUser[0]['firstname'], $dataUser[0]['lastname'], $dataUser[0]['language_id'], $api_key, $dataUser[0]['email']);
        } else {
            $apiKey = $dataUser[0]['api_key'];
        }
        return [
            'status'    => 'OK',
            'api_key'     => $apiKey,
            'country'     => $dataUser[0]['id_country']
        ];
    }
    public function encriptKey($password)
    {
        $salt       = "1NsT3pD3veL0p3R$";
        $password   = hash('sha256', $salt . $password);
        return $password;
    }
    public function sendMailCancel($code, $agency, $language, $templateMail = 'VOUCHER_CANCEL')
    {
        global $CORE_email;
        $logo               = $this->getLogoMaster($agency);
        $dataPassenger      = $this->getBeneficiariesByVoucher($code);
        $emailPassenger     = array_map(function ($value) {
            return $value['email'];
        }, $dataPassenger);
        $today              = date("d-m-Y");
        $variables_email    = [
            "##voucher##"   => stripslashes(strip_tags($code)),
            "##hoy##"       => stripslashes(strip_tags($today)),
            "##system##"    => stripslashes(strip_tags(SYSTEM_NAME)),
            "##logo##"      => $logo
        ];
        foreach ($variables_email as $varstr => $varvalue) {
            $CORE_email->setVariable($varstr, $varvalue);
        }
        $from = [
            'name'  => EMAIL_FROM_NAME,
            'email' => EMAIL_FROM
        ];
        $CORE_email->send($from, $to = $emailPassenger, $templateMail, $language);
    }
    public function getLogoMaster($agency)
    {
        $logoAgencyMaster   = $this->selectDynamic('', 'broker_parameters', "id_broker='$agency'", array("logo_empresa"))[0]["logo_empresa"];
        $logoInsurance      = $this->selectDynamic('', 'parameters', "parameter_key='SYSTEM_NAME'", array("logo_empresa"))[0]["logo_empresa"];
        $datAgency          = $this->selectDynamic('', 'broker', "id_broker='$agency'", array("img_broker", "type_broker", "logo_mostrar"));
        $logoAgency         = $datAgency[0]["img_broker"];
        if (!empty($logoAgency) && $datAgency[0]["logo_mostrar"] == "1") {
            return  DOMAIN_APP . "upload_files/logo_Agencia/" . $logoAgency;
        } elseif (!empty($logoAgencyMaster) && $datAgency[0]["type_broker"] == "1") {
            return  DOMAIN_APP . "upload_files/broker_parameters/" . $agency . "/logos/" . $logoAgencyMaster;
        } elseif (!empty($logoInsurance)) {
            return  DOMAIN_APP . "/admin/pictures/thumbnail/" . $logoInsurance;
        } else {
            return  DOMAIN_APP . "/admin/pictures/thumbnail/logo.png";
        }
    }
    public function getBeneficiariesByVoucher($voucher)
    {
        $query = "SELECT
            id,
            nacimiento,
            nombre,
            apellido,
            email
        FROM
            beneficiaries
        WHERE
            id_orden IN (
                SELECT
                    id
                FROM
                    orders
                WHERE
                    codigo = '$voucher'
            )
        AND beneficiaries.ben_status = '1'";
        return $this->_SQL_tool($this->SELECT, __METHOD__, $query);
    }
    public function checkDates($date)
    {
        if (is_array($date)) {
            foreach ($date as $value) {
                $date   = explode('/', $value);
                return (checkdate($date[1], $date[0], $date[2]));
            }
        } else {
            $date   = explode('/', $date);
            return (checkdate($date[1], $date[0], $date[2]));
        }
    }
    public function betweenDates($start, $end, $type)
    {
        $startdate ? date('Y-m-d') : $startdate;
        switch ($type) {
            case 'years':
                if (is_array($start) || is_array($end)) {
                    foreach ($end as $value) {
                        $query        = "SELECT timestampdiff(YEAR,'$value', '$start') as year";
                        $response     = $this->selectDynamic('', '', '', '', $query)[0]['year'];
                        return ($response < 0) ? $this->getError('1062') : false;
                    }
                } else {
                    $query      = "SELECT timestampdiff(YEAR,'$value', '$start') as year";
                    return      $this->selectDynamic('', '', '', '', $query)[0]['year'];
                }
                break;
            default:
                $query = "SELECT DATEDIFF('$end', '$start') + 1 AS dias";
                return $this->selectDynamic('', '', '', '', $query)[0]['dias'];
                break;
        }
    }
    public function curlGeneral($url, $data, $headers, $method = "POST")
    {
        $curl = curl_init();
        $url = ($method == "GET") ? $url . '?' . http_build_query($data) : $url;
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => array(
                $headers
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        return $response;
    }
    public function verifyOrigin($origin)
    {
        $response = $this->selectDynamic('', 'countries', "iso_country='$origin'");
        return ($response) ? true : false;
    }
    public function dataUpgrades($plan, $language, $price, $daysByPeople, $numberPassengers, $upgrade, $pricePassengers, $prefix)
    {
        $query = "SELECT
        raiders.id_raider,
        raiders_detail.name_raider,
        raiders.type_raider,
        raiders.value_raider,
        raiders.rd_calc_type,
        CASE
        WHEN raiders.rd_calc_type= 1 THEN 
         IF(
             raiders.type_raider     = 1,
             ROUND(raiders.value_raider,2),
             ROUND(((raiders.value_raider / 100) * '$price'),2)
         )
         WHEN raiders.rd_calc_type   = 4 THEN 
         IF(
            raiders.type_raider  = 1,
            ROUND(raiders.value_raider * '$daysByPeople',2),
            ROUND((raiders.value_raider / 100) * '$price' * '$daysByPeople',2)
         )
         WHEN raiders.rd_calc_type   = 5 THEN 
         IF(
            raiders.type_raider = 1,
            ROUND((raiders.value_raider * '$daysByPeople' * '$numberPassengers'),2),
            ROUND(((raiders.value_raider / 100) * '$price') * '$daysByPeople'  * '$numberPassengers',2)
         )
         WHEN raiders.rd_calc_type   = 2 THEN 
         IF(
            raiders.type_raider = 1,
            ROUND(raiders.value_raider + '$pricePassengers',2),
            ROUND((raiders.value_raider / 100) * '$pricePassengers',2)
         )
         ELSE 'Precio No disponible'
         END AS price_upgrade
         FROM raiders
             INNER JOIN raiders_detail ON raiders_detail.id_raider = raiders.id_raider
             INNER JOIN plan_raider ON raiders.id_raider = plan_raider.id_raider
         WHERE
             plan_raider.id_plan = '$plan'
         AND 
            plan_raider.prefijo = '$prefix'
         AND 
             raiders_detail.language_id  = '$language' ";
        if (!empty($upgrade)) {
            $query .= "AND 
             raiders.id_raider IN ($upgrade) ";
        }
        $response   = $this->selectDynamic('', '', '', '', $query);
        if ($response) {
            return $response;
        } else {
            return [
                "status"   => "No hay resultados",
                "message"  => "No hay upgrades asociados a éste Plan"
            ];
        }
    }
    public function validateDataPassenger($quantity, $namePassenger, $lastNamePassenger, $birthDayPassenger, $documentPassenger, $emailPassenger, $phonePassenger, $medicalConditionsPassenger, $skipBirthDay = true)
    {
        $dataBithDay    = [];
        $dataValidate   = [
            '4005'  => count($namePassenger),
            '4007'  => count($lastNamePassenger),
            '4006'  => count($documentPassenger),
            '5012'  => count($emailPassenger),
            '4008'  => count($phonePassenger),
            '5006'  => count($medicalConditionsPassenger),
            '4010'    => (!$this->verifyMail($emailPassenger)) ? 0 : 1
        ];
        if ($skipBirthDay) {
            $dataBithDay    = [
                '5005'  => $birthDayPassenger
            ];
        }
        $dataValidate   = $dataValidate + $dataBithDay;
        $validatEmpty   = $this->validatEmpty($dataValidate);
        if ($validatEmpty) {
            return $validatEmpty;
        }
        for ($i = 0; $i < $quantity; $i++) {
            if (!preg_match('(^[a-zA-Z ]*$)', $namePassenger[$i])) {
                return $this->getError('9032');
            }
            if (!preg_match('(^[a-zA-Z ]*$)', $lastNamePassenger[$i])) {
                return $this->getError('9035');
            }
            if (!is_numeric($documentPassenger[$i])) {
                return $this->getError('9033');
            }
            if (!is_numeric($phonePassenger[$i])) {
                return $this->getError('9034');
            }
            $today = date('Y-m-d');
            $birthDayPassengerTrans[$i] = $this->transformerDate($birthDayPassenger[$i]);
            if ($skipBirthDay) {
                if (!($this->checkDates($birthDayPassenger[$i])) || (strtotime($birthDayPassengerTrans[$i]) > strtotime($today))) {
                    return $this->getError('1062');
                }
            }
        }
    }
    public function getOrderData($code)
    {
        $query = "SELECT
            id,
            producto,
            retorno,
            salida,
            territory,
            agencia,
            total,
            neto_prov,
            vendedor,
            tasa_cambio,
            cantidad
        FROM
            orders
        WHERE
            codigo = '$code'";
        return $this->_SQL_tool($this->SELECT_SINGLE, __METHOD__, $query);
    }
    public function valUpgrades($plan, $upgrades)
    {
        $query = "SELECT
                raiders.id_raider,
                raiders.type_raider
             FROM
                raiders
                INNER JOIN plan_raider ON raiders.id_raider = plan_raider.id_raider
             WHERE   
                plan_raider.id_plan = '$plan' AND
                raiders.id_raider IN ($upgrades)";
        return $this->_SQL_tool($this->SELECT_SINGLE, __METHOD__, $query)['type_raider'];
    }
    public function addBeneficiares($documentPassenger, $birthDayPassenger, $namePassenger, $lastNamePassenger, $phonePassenger, $emailPassenger, $idOrden, $status_ben, $price, $cost, $observacion, $precio_local, $costo_local, $tax_beneficiario, $tax_local_beneficiario)
    {
        $data   =
            [
                'documento'         => $documentPassenger,
                'nacimiento'        => $birthDayPassenger,
                'nombre'            => $namePassenger,
                'apellido'          => $lastNamePassenger,
                'telefono'          => $phonePassenger,
                'email'             => $emailPassenger,
                'id_orden'          => $idOrden,
                'ben_status'        => '1',
                'precio_vta'        => $price,
                'precio_cost'       => $cost,
                'condicion_medica'  => $observacion,
                'precio_vta_mlc'    => $precio_local,
                'precio_cost_mlc'   => $costo_local,
                'tax_total'         => $tax_beneficiario,
                'tax_total_mlc'     => $tax_local_beneficiario,
            ];
        return $this->insertDynamic($data, 'beneficiaries');
    }
    public function addCommission($idAgency, $idPlanCategory, $price, $idOrden)
    {
        $agencyLevel           = $this->Get_Broker_Nivel($idAgency);
        $porcentageCommission  = 0;
        for ($i = $agencyLevel['nivel']; $i > 0; $i--) {
            $byCommission           = $this->AgenciaNivelCategoriaComision($idAgency, $idPlanCategory);
            $porcentage             = $byCommission - $porcentageCommission;
            $valueCommission        = ($porcentage > 0) ? (($porcentage / 100) * $price) : 0;
            $this->Add_order_Comision($idOrden, $idAgency, $porcentage, $valueCommission);
            $porcentageCommission   = $byCommission;
            $agencyLevel            = $this->Get_Broker_Nivel($idAgency);
            $idAgency               = $agencyLevel['parent'];
        }
    }
    public function Enviar_orden($email, $id_orden, $lg_id, $lang)
    {
        $post_url = LINK_EMAIL . '?';
        $post_values = array(
            "id_orden" => $id_orden,
            "email"    => $email,
            "lang"     => $lg_id,
            "short"    => $lang,
            "broker_sesion" => $this->getBrokerSesion($id_orden),
            "selectLanguage" => $lang
        );
        $post_string = "";
        foreach ($post_values as $key => $value) {
            $post_string .= "$key=" . urlencode($value) . "&";
        }
        $post_string = rtrim($post_string, "& ");
        $request = curl_init($post_url);
        curl_setopt($request, CURLOPT_HEADER, 0);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($request, CURLOPT_POSTFIELDS, $post_string);
        curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE);
        $post_response = curl_exec($request);
        curl_close($request);
    }
    public function Get_Broker_Nivel($idbroker)
    {
        $query = "SELECT broker_nivel.id, broker_nivel.id_broker,  broker_nivel.nivel, broker_nivel.parent FROM broker_nivel WHERE id_broker ='$idbroker'";
        $response = $this->_SQL_tool($this->SELECT_SINGLE, __METHOD__, $query);
        if ($response) {
            $arrResult['id'] = $response['id'];
            $arrResult['nivel'] = $response['nivel'];
            $arrResult['parent'] = $response['parent'];
            $arrResult['id_broker'] = $response['id_broker'];
        }
        return ($arrResult);
    }
    public function AgenciaNivelCategoriaComision($id_broker, $categoria)
    {
        $query = "SELECT
                porcentaje
            FROM
                commissions
            WHERE
                commissions.id_categoria = '$categoria'
            AND id_agencia = '$id_broker'";
        $response = $this->_SQL_tool($this->SELECT_SINGLE, __METHOD__, $query);
        return isset($response['porcentaje']) ? $response['porcentaje'] : 0;
    }
    public function Add_order_Comision($idorden, $idbroker, $porcentaje, $montocomision)
    {
        $data   = [
            'id_order'          => $idorden,
            'id_broker'         => $idbroker,
            'porcentage'        => $porcentaje,
            'monto_comision'    => $montocomision,
            'tr_date'           => 'NOW()'
        ];
        return $this->insertDynamic($data, 'order_comision');
    }
    public function getBrokerSesion($id)
    {
        $query = "SELECT agencia FROM orders WHERE id='$id'";
        return $this->_SQL_tool($this->SELECT, __METHOD__, $query)[0]['agencia'];
    }
    public function valueRandom($length = 12)
    {
        $chr = "0123456789ABCDEFGHIJKML";
        $str = "";
        while (strlen($str) < $length) {
            $str .= substr($chr, mt_rand(0, (strlen($chr))), 1);
        }
        return ($str);
    }
    public function addOrderUpgrades($idOrden, $idUpgrade, $priceUpgrade, $costUpgrade, $netPriceUpgrade, $idBenefit)
    {
        $idBenefit = $idBenefit ?: 0;
        $data       = [
            'id_orden'      => $idOrden,
            'id_raider'     => $idUpgrade,
            'value_raider'  => $priceUpgrade,
            'id_beneft'     => $idBenefit,
            'cost_raider'   => $costUpgrade,
            'neta_raider'   => $netPriceUpgrade
        ];
        return $this->insertDynamic($data, 'orders_raider');
    }
    public function updateUpgradeOrder($codigo_voucher, $total, $totaCost)
    {
        $data   = [
            'total'         => $total,
            'neto_prov'     => $totaCost,
        ];
        return $this->updateDynamic('orders', 'codigo', $codigo_voucher, $data);
    }
    public function dataCountryRestricted($plan)
    {
        $query = "SELECT 
            countries.iso_country,
            countries.description 
        FROM 
            countries
        INNER JOIN relaciotn_restriction ON relaciotn_restriction.iso_country = countries.iso_country
        INNER JOIN restriction ON relaciotn_restriction.id_restric = restriction.id_restric
        INNER JOIN plans ON restriction.id_plans = plans.id
        WHERE plans.id = '$plan'";
        return $this->selectDynamic('', '', '', '', $query);
    }
    public function deleteUpgradeOrder($idorden, $idraider)
    {
        $query = "DELETE 
            FROM
                orders_raider
            WHERE
                id_orden    = '$idorden'
            AND id_raider   = '$idraider'";
        return $this->_SQL_tool($this->DELETE, __METHOD__, $query);
    }
    public function verifiedBeneficiariesByVoucher($code, $idPassenger)
    {
        $query = "SELECT
        beneficiaries.id,
        beneficiaries.ben_status
        FROM
            beneficiaries
        WHERE
            beneficiaries.id_orden IN (
                SELECT
                    orders.id
                FROM
                    orders
                WHERE
                    orders.codigo = '$code'
            )
        AND beneficiaries.id = '$idPassenger' ";
        $response     = $this->_SQL_tool($this->SELECT_SINGLE, __METHOD__, $query);
        if (empty($response)) {
            return $this->geterror('9028');
        }
        if ($response['ben_status'] == '2') {
            return $this->geterror('9029');
        }
    }
    public function dataUpgradesPlan($plan, $language)
    {
        $query = "SELECT
            raiders.id_raider,
            raiders_detail.name_raider,
            raiders.type_raider,
            raiders.value_raider,
            raiders.cost_raider,
            raiders.rd_calc_type
        FROM
            raiders
            INNER JOIN raiders_detail ON raiders_detail.id_raider = raiders.id_raider
            INNER JOIN plan_raider ON raiders.id_raider = plan_raider.id_raider
        WHERE
            plan_raider.id_plan = '$plan' 
        AND raiders_detail.language_id='$language'";
        return $this->selectDynamic('', '', '', '', $query);
    }
    public function shortUrl($url)
    {
        $arrData = [
            'login'    => 'o_2icvb72cce',
            'apiKey' => 'R_5633378002a147d2b9c03fde3a244b65',
            'uri'    => $url,
            'format' => 'txt'
        ];
        $parameters = http_build_query($arrData);
        $url = "http://api.bit.ly/v3/shorten?" . $parameters;
        return file_get_contents($url);
    }
    public function dataExchangeRate($isoCountry)
    {
        $query = "SELECT
        countries.description,
        countries.iso_country,
        countries.currencyname,
        currency.usd_exchange
         FROM
        countries
        INNER JOIN currency ON countries.currencycode = currency.value_iso
        WHERE currency.usd_exchange != '0'";
        if ($isoCountry) {
            $query .= "AND countries.iso_country = '$isoCountry'";
        }
        return $this->_SQL_tool($this->SELECT, __METHOD__, $query);
    }
    public function traer_ids_beneficiarios($codigo)
    {
        $query = "SELECT
		     beneficiaries.id
		FROM beneficiaries
			 Inner Join orders ON orders.id = beneficiaries.id_orden
	    where
	         orders.codigo ='$codigo'";
        $response = $this->_SQL_tool($this->SELECT, __METHOD__, $query);
        if ($response) {
            return $response;
        } else {
            return $this->getError('1051');
        }
    }
    public function dataCountryRegion()
    {
        $query = "SELECT
			territory.id_territory,
			countries.iso_country
		FROM
			territory
		INNER JOIN countries ON countries.id_territory = territory.id_territory
		WHERE
			countries.c_status = 'Y'
		AND territory.id_status = '1'";
        return $this->selectDynamic('', '', '', '', $query);
    }
    public function dataPlanCategory($language)
    {
        $query = "SELECT
                    plan_categoria_detail.name_plan,
                    plan_categoria_detail.id_plan_categoria
                FROM
                    plan_categoria_detail
                INNER JOIN plan_category ON plan_categoria_detail.id_plan_categoria = plan_category.id_plan_categoria
                WHERE
                    plan_categoria_detail.language_id = '$language' and plan_category.id_status = 1";
        return $this->selectDynamic('', '', '', '', $query);
    }
    public function dataCategories($prefix)
    {
        $query = "SELECT
        id_plan_categoria,
        name_plan
        FROM
            plan_category
        WHERE
            prefijo IN (
                SELECT
                    clients.prefix
                FROM
                    clients
                WHERE
                    data_activa = 'si'
        ) and plan_category.id_status='1' ";
        if ($prefix) {
            $query .= " AND prefijo = '$prefix'
            AND EXISTS (
                SELECT
                    prefijo,
                    id_plan_categoria,
                    activo,
                    eliminado
                FROM
                    plans
                WHERE
                    plans.prefijo = plan_category.prefijo
                AND plan_category.id_plan_categoria = plans.id_plan_categoria
                AND plans.activo = 1
                AND plans.eliminado <> 2
            )
            ORDER BY
                name_plan ASC";
        }
        return $this->selectDynamic('', '', '', '', $query);
    }

    public function agencyBroker($idUser, $userType, $prefix)
    {
        $sql = ['querys' => "SELECT
                                user_associate.id_associate,
                                broker.broker
                            FROM
                                users
                            INNER JOIN user_associate ON users.id = user_associate.id_user
                            INNER JOIN broker ON user_associate.id_associate = broker.id_broker
                            WHERE
                                users.id = '$idUser' 
                                AND users.id_status = '1' 
                            ORDER BY
                                user_associate.modified DESC
                            LIMIT 1"];

        $link         = $this->selectDynamic(['prefix' => $prefix], 'clients', "data_activa='si'", ['web'])[0]['web'];
        $linkParam     = $link . "/app/api/selectDynamic";
        $headers     = "content-type: application/x-www-form-urlencoded";
        $response = $this->curlGeneral($linkParam, json_encode($sql), $headers);
        return json_decode($response, true);
    }

    public function agencysChildren($idBroker, $prefix)
    {
        $sql = ['querys' => "SELECT broker_nivel.id, 
                broker_nivel.id_broker, 
                broker_nivel.nivel, 
                broker_nivel.parent, 
                broker_nivel.master 
            FROM broker_nivel 
            WHERE parent = '$idBroker' "];

        $link         = $this->selectDynamic(['prefix' => $prefix], 'clients', "data_activa='si'", ['web'])[0]['web'];
        $linkParam     = $link . "/app/api/selectDynamic";
        $headers     = "content-type: application/x-www-form-urlencoded";
        $response = $this->curlGeneral($linkParam, json_encode($sql), $headers);
        return json_decode($response, true);
    }

    public function getAgencyMaster($prefix, $idAgency)
    {
        return  $this->selectDynamic('', '', '', '', "SELECT * FROM broker_nivel WHERE prefijo = '$prefix' AND id_broker = '$idAgency' ORDER BY id_broker_nivel DESC LIMIT 1", '', '', '');
    }
}