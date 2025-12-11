<?php

return [
    // Câte zile lăsăm o comandă B2C neplătită înainte să o anulăm automat
    'b2c_unpaid_auto_cancel_days' => env('B2C_UNPAID_AUTO_CANCEL_DAYS', 3),
];
