<?php
namespace AlgorithmicCash;

class PaymentUrl {
    const PAYIN_URL = "https://api.algorithmic.cash/request_payin.php";
    const PAYOUT_URL = "https://api.algorithmic.cash/request_payout.php";
    const PAYBALANCE_URL = "https://api.algorithmic.cash/request_balance.php";
    const PAYIN_STATUS_URL = "https://api.algorithmic.cash/request_payin_status.php";
    const PAYOUT_STATUS_URL = "https://payout-api.algorithmic.cash/request_payout_status.php";

    public static function buildPayInStatusUrl(array $params): string
    {
        return self::PAYIN_STATUS_URL . '?' . http_build_query($params);
    }
    public static function buildPayOutStatusUrl(array $params): string
    {
        return self::PAYOUT_STATUS_URL . '?' . http_build_query($params);
    }
    public static function buildPayInUrl(array $params) {
        return self::PAYIN_URL . '?' . http_build_query($params);
    }

    public static function buildPayOutUrl(array $params) {
        return self::PAYOUT_URL . '?' . http_build_query($params);
    }

    public static function buildPayBalanceUrl(array $params) {
        return self::PAYBALANCE_URL . '?' . http_build_query($params);
    }
}
