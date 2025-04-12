<?php
require_once '../models/Revenue.php';

class RevenueController {
    private $revenueModel;

    public function __construct($db) {
        $this->revenueModel = new Revenue($db);
    }

    public function getRevenueReport() {
        $result = $this->revenueModel->getRevenueReport();
        echo json_encode([
            'status' => 'success',
            'data' => $result
        ]);
    }
}
