<?php

return [

    // Days an integration-log record is kept before it is pruned. Default: 30.
    'retention_days' => (int) env('INTEGRATION_LOG_RETENTION_DAYS', 30),

];
