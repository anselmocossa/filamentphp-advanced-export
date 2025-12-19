<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Export Limits
    |--------------------------------------------------------------------------
    |
    | Configure the maximum number of records that can be exported and the
    | chunk size for processing. The queue_threshold determines when exports
    | should be processed in the background.
    |
    */
    'limits' => [
        'max_records' => env('EXPORT_MAX_RECORDS', 2000),
        'chunk_size' => env('EXPORT_CHUNK_SIZE', 500),
        'queue_threshold' => env('EXPORT_QUEUE_THRESHOLD', 2000),
    ],

    /*
    |--------------------------------------------------------------------------
    | View Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the path and naming conventions for export views.
    | Set use_package_views to true to use the default package views
    | instead of model-specific views.
    |
    */
    'views' => [
        'path' => 'exports',
        'simple_suffix' => '-excel',
        'advanced_suffix' => '-excel-advanced',
        'use_package_views' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Date Formatting
    |--------------------------------------------------------------------------
    |
    | Configure how dates should be formatted in exports.
    |
    */
    'date_format' => 'd/m/Y H:i',
    'date_only_format' => 'd/m/Y',

    /*
    |--------------------------------------------------------------------------
    | File Generation
    |--------------------------------------------------------------------------
    |
    | Configure how export files are generated and stored.
    |
    */
    'file' => [
        'extension' => 'xlsx',
        'disk' => 'public',
        'directory' => 'exports',
        'name_format' => '{resource}_{type}_{datetime}',
        'datetime_format' => 'Y-m-d_H-i-s',
    ],

    /*
    |--------------------------------------------------------------------------
    | Column Configuration
    |--------------------------------------------------------------------------
    |
    | Configure default column behavior in the export form.
    |
    */
    'columns' => [
        'max_default' => 5,
        'max_selectable' => 20,
        'min_required' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Action Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the appearance of the export action button.
    | Set label to null to use translation keys.
    |
    */
    'action' => [
        'name' => 'export',
        'label' => null,
        'icon' => 'heroicon-o-arrow-down-tray',
        'color' => 'success',
        'modal_heading' => null,
        'modal_description' => null,
        'modal_submit_label' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Columns
    |--------------------------------------------------------------------------
    |
    | These columns are used when a model doesn't implement getExportColumns().
    |
    */
    'fallback_columns' => [
        'id' => 'ID',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Filters
    |--------------------------------------------------------------------------
    |
    | Filters that are automatically handled by the package.
    |
    */
    'default_filters' => [
        'created_at',
        'updated_at',
        'created_by',
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Configure background job processing for large exports.
    |
    */
    'queue' => [
        'enabled' => env('EXPORT_QUEUE_ENABLED', true),
        'connection' => env('EXPORT_QUEUE_CONNECTION', 'default'),
        'queue' => env('EXPORT_QUEUE_NAME', 'exports'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Configure notification behavior.
    |
    */
    'notifications' => [
        'show_success' => true,
        'show_no_data' => true,
        'show_errors' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The user model class used for sending database notifications.
    | This is used by ProcessExportJob to find and notify users.
    |
    */
    'user_model' => env('AUTH_MODEL', 'App\\Models\\User'),
];
