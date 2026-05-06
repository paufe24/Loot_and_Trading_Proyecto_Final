<?php
/**
 * Configuración local de Loot & Trading.
 *
 * NO subir a Git. Está incluido en .gitignore.
 *
 * Datos de conexión a Supabase: revisar en
 *   Supabase -> Settings -> Database -> Connection string (URI)
 *   Supabase -> Settings -> API Keys
 */

return [
    // ----- Supabase Postgres (conexión directa via PDO) -----
    'db' => [
        // Host del pooler. Recomendado para PHP/XAMPP: Session pooler (puerto 5432)
        // Verifica en Supabase -> Settings -> Database -> Connection string
        'host'     => 'aws-0-eu-west-1.pooler.supabase.com',
        'port'     => 5432,
        'dbname'   => 'postgres',
        // Usuario del pooler: postgres.<project-ref>
        'user'     => 'postgres.jmfqqzarmjwmfumfrvmu',
        // ¡¡ RELLENAR !! Password de la BD de Supabase (la que reseteaste)
        'password' => 'rPKXhnei8fjsUCpb',
        'sslmode'  => 'require',
    ],

    // ----- Supabase Data API (REST/Storage) -----
    'supabase' => [
        'url'             => 'https://jmfqqzarmjwmfumfrvmu.supabase.co',
        'publishable_key' => 'sb_publishable_0PyDfr7VkOUDMRNoD8x9Kw_lyoT2inr',
        // service_role key SOLO en servidor, NO usar desde JS
        'service_role'    => 'PON_AQUI_LA_SERVICE_ROLE_SI_LA_NECESITAS',
    ],
];
