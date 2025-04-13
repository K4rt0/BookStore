<?php
require_once __DIR__ . '/../models/Order.php';

class StatisticsController {
    private $order;

    public function __construct() {
        $this->order = new Order();
    }
    public function daily_statistics($params) {
        $date = $params['date'] ?? date('Y-m-d');
        $statistics = $this->order->get_detailed_statistics('daily', $date);

        ApiResponse::success("Số liệu thống kê theo ngày !", 200, [
            'date' => $date,
            'total_orders' => $statistics['total_orders'],
            'total_revenue' => $statistics['total_revenue'],
            'details' => $statistics['details']
        ]);
    }

    public function monthly_statistics($params) {
        $month = $params['month'] ?? date('m');
        $year = $params['year'] ?? date('Y');
        $statistics = $this->order->get_detailed_statistics('monthly', null, $month, $year);

        ApiResponse::success("Số liệu thống kê theo tháng !", 200, [
            'month' => $month,
            'year' => $year,
            'total_orders' => $statistics['total_orders'],
            'total_revenue' => $statistics['total_revenue'],
            'details' => $statistics['details']
        ]);
    }

    public function yearly_statistics($params) {
        $year = $params['year'] ?? date('Y');
        $statistics = $this->order->get_detailed_statistics('yearly', null, null, $year);

        ApiResponse::success("Số liệu thống kê theo năm !", 200, [
            'year' => $year,
            'total_orders' => $statistics['total_orders'],
            'total_revenue' => $statistics['total_revenue'],
            'details' => $statistics['details']
        ]);
    }
}
