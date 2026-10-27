<?php

return [

    // Contract pagination (C2).
    'pagination' => [
        'default_per_page' => (int) env('API_DEFAULT_PER_PAGE', 15),

        // Fixed by the contract: por_pagina goes from 1 to 100.
        'max_per_page' => 100,
    ],

];