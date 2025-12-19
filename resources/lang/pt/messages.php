<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Action Labels
    |--------------------------------------------------------------------------
    */
    'action' => [
        'label' => 'Exportar',
    ],

    /*
    |--------------------------------------------------------------------------
    | Modal
    |--------------------------------------------------------------------------
    */
    'modal' => [
        'heading' => 'Exportar Registros',
        'description' => 'Selecione as colunas desejadas e confirme. Serão exportadas no máximo :limit linhas com os filtros aplicados.',
        'submit' => 'Exportar',
    ],

    /*
    |--------------------------------------------------------------------------
    | Form Fields
    |--------------------------------------------------------------------------
    */
    'form' => [
        'order_column' => [
            'label' => 'Ordenar por Coluna',
            'placeholder' => 'Selecione a coluna para ordenação...',
            'helper' => 'Escolha por qual coluna os dados serão ordenados no Excel',
        ],
        'order_direction' => [
            'label' => 'Direção da Ordenação',
            'options' => [
                'asc' => 'Crescente (A→Z, 1→9, Mais Antigo→Mais Recente)',
                'desc' => 'Decrescente (Z→A, 9→1, Mais Recente→Mais Antigo)',
            ],
            'helper' => 'Escolha se quer ordenar de forma crescente ou decrescente',
        ],
        'columns' => [
            'label' => 'Configurar Colunas para Export',
            'field' => [
                'label' => 'Campo',
            ],
            'title' => [
                'label' => 'Título Personalizado',
                'placeholder' => 'Digite o título desejado...',
            ],
            'add' => 'Adicionar Coluna',
            'new' => 'Nova Coluna',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'no_data' => [
            'title' => 'Nenhum registro encontrado',
            'body' => 'Não há dados para exportar com os filtros aplicados.',
        ],
        'success' => [
            'title' => 'Exportação concluída',
            'body' => ':count registros exportados com sucesso.',
        ],
        'queued' => [
            'title' => 'Exportação em processamento',
            'body' => 'Sua exportação está sendo processada em segundo plano. Você será notificado quando estiver pronta.',
        ],
        'error' => [
            'title' => 'Erro na exportação',
            'body' => 'Ocorreu um erro durante o processamento: :message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Background Job Notifications
    |--------------------------------------------------------------------------
    */
    'notification' => [
        'export_complete' => 'Exportação Concluída',
        'export_body' => 'Sua exportação com :records registros está pronta. Arquivo: :filename',
        'download' => 'Baixar',
        'export_failed' => 'Exportação Falhou',
        'export_failed_body' => 'A exportação :filename falhou ao processar. Por favor, tente novamente.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Background Export Action
    |--------------------------------------------------------------------------
    */
    'background' => [
        'action_label' => 'Exportar em Segundo Plano',
        'modal_heading' => 'Exportar em Segundo Plano',
        'modal_description' => 'A exportação será processada em segundo plano. Você receberá uma notificação quando o arquivo estiver pronto para download.',
        'dispatched_title' => 'Exportação Iniciada',
        'dispatched_body' => 'Sua exportação está sendo processada. Você receberá uma notificação quando estiver pronta.',
    ],

    /*
    |--------------------------------------------------------------------------
    | General
    |--------------------------------------------------------------------------
    */
    'undefined_title' => 'Título não definido',
    'yes' => 'Sim',
    'no' => 'Não',
];
