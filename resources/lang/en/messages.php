<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Action Labels
    |--------------------------------------------------------------------------
    */
    'action' => [
        'label' => 'Export',
    ],

    /*
    |--------------------------------------------------------------------------
    | Modal
    |--------------------------------------------------------------------------
    */
    'modal' => [
        'heading' => 'Export Records',
        'description' => 'Select the columns you want to export. Maximum of :limit records will be exported with the applied filters.',
        'submit' => 'Export',
    ],

    /*
    |--------------------------------------------------------------------------
    | Form Fields
    |--------------------------------------------------------------------------
    */
    'form' => [
        'order_column' => [
            'label' => 'Order by Column',
            'placeholder' => 'Select the column to order by...',
            'helper' => 'Choose which column the data will be sorted by in the Excel file',
        ],
        'order_direction' => [
            'label' => 'Order Direction',
            'options' => [
                'asc' => 'Ascending (A→Z, 1→9, Oldest→Newest)',
                'desc' => 'Descending (Z→A, 9→1, Newest→Oldest)',
            ],
            'helper' => 'Choose whether to sort ascending or descending',
        ],
        'columns' => [
            'label' => 'Configure Export Columns',
            'field' => [
                'label' => 'Field',
            ],
            'title' => [
                'label' => 'Custom Title',
                'placeholder' => 'Enter the desired title...',
            ],
            'add' => 'Add Column',
            'new' => 'New Column',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'no_data' => [
            'title' => 'No records found',
            'body' => 'There is no data to export with the applied filters.',
        ],
        'success' => [
            'title' => 'Export completed',
            'body' => ':count records exported successfully.',
        ],
        'queued' => [
            'title' => 'Export queued',
            'body' => 'Your export is being processed in the background. You will be notified when it is ready.',
        ],
        'error' => [
            'title' => 'Export error',
            'body' => 'An error occurred during processing: :message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Background Job Notifications
    |--------------------------------------------------------------------------
    */
    'notification' => [
        'export_complete' => 'Export Complete',
        'export_body' => 'Your export with :records records is ready. File: :filename',
        'download' => 'Download',
        'export_failed' => 'Export Failed',
        'export_failed_body' => 'The export :filename failed to process. Please try again.',
    ],

    /*
    |--------------------------------------------------------------------------
    | General
    |--------------------------------------------------------------------------
    */
    'undefined_title' => 'Undefined Title',
    'yes' => 'Yes',
    'no' => 'No',
];
