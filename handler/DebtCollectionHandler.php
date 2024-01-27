<?php
/*
 * Copyright (c) 2024. Jakub Turczyński
 *
 * Wszelkie prawa zastrzeżone. Poniższy kod źródłowy (zwany także programem komputerowym lub krótko - programem), zarówno w jego części twórczej jak i całości,  podlega ochronie na mocy prawa autorskiego jako utwór.
 * Użytkownikowi zezwala się na dostęp do kodu źródłowego oraz na jego użytkowanie w sposób w jaki program został do tego przeznaczony. Kopiowanie, powielanie czy edytowanie całości lub części kodu źródłowego programu bez zgody jego autora jest zabronione.
 */

set_time_limit(3600); //1 hour
ini_set('memory_limit', '2G');

require('../classes/DB.php');
$config = include('../app/config/parameters.php');

$dev = false;

$db = new Dc\Classes\DB($config['db_type'], $config['db_location'], $config['db_name'], $config['db_user'], $config['db_password']);

function main()
{
    global $dev;

    if (!headers_sent()) {
        header('Content-Type: application/json');
    }

    if ( !isset($_POST['dc']) ) {
        exit;
    }

    if ( isset($_POST['get_debt_data']) ) {
        $result = dcGetDebtData();
        echo json_encode( $result );
        exit;
    } else if ( isset($_POST['get_contractor_data']) && isset($_POST['company_id']) && is_numeric($_POST['company_id'])) {
        $result = dcGetContractorData($_POST['company_id']);
        echo json_encode( $result );
        exit;
    }
}

main();

// **** -------------------------------------------------------- ****
// **** ------------------- DEBT COLLECTION -------------------- ****
// **** -------------------------------------------------------- ****

function dcGetDebtData()
{
    global $db;

    $debtData = $db->query('SELECT * FROM debtcollect.invoice_summary where payment_date is null order by payment_due_date desc;');

    return ['status' => "OK", 'data' => $debtData];
}

function dcGetContractorData($companyId)
{
    global $db;

    $contractorData = $db->query("SELECT * FROM debtcollect.client_information where company_id = '{$companyId}';", true);

    return ['status' => "OK", 'data' => $contractorData];
}



// **** -------------------------------------------------------- ****
// **** ------------------ UTILITY FUNCTIONS ------------------- ****
// **** -------------------------------------------------------- ****

function reArrayFiles($filePost)
{
    $fileArr = array();
    $fileCount = count($filePost['name']);
    $fileKeys = array_keys($filePost);

    for ($i = 0; $i < $fileCount; $i++) {
        foreach ($fileKeys as $key) {
            $fileArr[$i][$key] = $filePost[$key][$i];
        }
    }

    return $fileArr;
}

function getSelArrayFromAkwArray($akwArray)
{
    $selArray = [];

    foreach ($akwArray as $akw) {
        $sel = match (intval($akw)) {
            1, 2, 3, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 51, 52 => "MGM",
            101 => "RZM",
            102 => "STM",
            103 => "MCM",
            104 => "JNM",
            105 => "TNM",
            106 => "KRM",
            107 => "RMM",
            108 => "CDM",
            109 => "KNM",
            110 => "TGM",
            111 => "NDM",
            112 => "SNM",
            default => null
        };

        if ($sel !== null && !in_array($sel, $selArray)) {
            array_push($selArray, $sel);
        }
    }
    if (in_array("STM", $selArray)) {
        array_push($selArray, "SWM");
    }

    return $selArray;
}

function getAkwEmail($akw)
{
    return (new DB("hermes"))->query("select first 1 e_mail from mat_akw where id = '{$akw}';", true, true);
}

function parsePrice($price)
{
    return number_format($price, 2, ',', '');
}

function parseDate($date)
{
    return substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2);
}

function array_unset_recursive(&$array, $remove)
{
    $remove = (array)$remove;
    foreach ($array as $key => &$value) {
        if (in_array($value, $remove)) {
            unset($array[$key]);
        } elseif (is_array($value)) {
            array_unset_recursive($value, $remove);
        }
    }
}

function recur_ksort(&$array)
{
    foreach ($array as &$value) {
        if (is_array($value))
            recur_ksort($value);
    }
    ksort($array);
}

function print_rr($var)
{
    echo "<pre>";
    print_r($var);
    echo "</pre>";
}