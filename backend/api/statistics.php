<?php
require_once __DIR__ . '/../controllers/StatisticsController.php';

$controller = new StatisticsController();
$flag = false;

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        switch ($_GET['action']) {
            case 'daily':
                $controller->daily_statistics($_GET);
                break;

            case 'monthly':
                $controller->monthly_statistics($_GET);
                break;

            case 'yearly':
                $controller->yearly_statistics($_GET);
                break;

            default:
                $flag = true;
                break;
        }
        break;

    default:
        $flag = true;
        break;
}

if ($flag) ApiResponse::error('Invalid action or method', 405);