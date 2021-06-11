<?php
/**
 * @package     Epicom_MHub
 * @copyright   Copyright (c) 2021 Gamuza Technologies (http://www.gamuza.com.br/)
 * @author      Eneias Ramos de Melo <eneias@gamuza.com.br>
 */

$installer = $this;
$installer->startSetup();

function addMHubShippingsTable ($installer, $model, $description)
{
    $table = $installer->getTable ($model);

    $sqlBlock = <<< SQLBLOCK
CREATE TABLE IF NOT EXISTS {$table}
(
    entity_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    PRIMARY KEY (entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 COMMENT='{$description}';
SQLBLOCK;

    $installer->run ($sqlBlock);

    $installer->getConnection ()
        ->addColumn ($table, 'external_id', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_INTEGER,
            'length'   => 11,
            'unsigned' => true,
            'nullable' => false,
            'comment'  => 'External ID',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'provider', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'   => 255,
            'nullable' => false,
            'comment'  => 'Provider',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'carrier', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'   => 255,
            'nullable' => false,
            'comment'  => 'Carrier',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'name', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'   => 255,
            'nullable' => false,
            'comment'  => 'Name',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'is_active', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_BOOLEAN,
            'unsigned' => true,
            'nullable' => false,
            'default'  => 1,
            'comment'  => 'Is Active',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'created_at', array(
            'type'     => Varien_Db_Ddl_Table::TYPE_DATETIME,
            'nullable' => false,
            'comment'  => 'Created At',
        ));
    $installer->getConnection ()
        ->addColumn ($table, 'updated_at', array(
            'type' => Varien_Db_Ddl_Table::TYPE_DATETIME,
            'nullable' => true,
            'comment' => 'Updated At'
        ));

    $sqlBlock = <<< SQLBLOCK
INSERT INTO {$table} (created_at, external_id, provider, carrier, name) VALUES
(NOW(), 1,    'intelipost', 'Correios',            'Correios PAC'),
(NOW(), 2,    'intelipost', 'Correios',            'Correios Sedex'),
(NOW(), 4,    'intelipost', 'Total Express',       'Total Express'),
(NOW(), 22,   'intelipost', 'JadLog',              'JadLog Standard'),
(NOW(), 23,   'intelipost', 'Jamef',               'Jamef Standard'),
(NOW(), 26,   'intelipost', 'Transcole',           'Transcole Standard'),
(NOW(), 41,   'intelipost', 'Rodonaves',           'Rodonaves Standard'),
(NOW(), 42,   'intelipost', 'Sunorte',             'Sunorte Standard'),
(NOW(), 43,   'intelipost', 'Transoliveira',       'Transoliveira Standard'),
(NOW(), 44,   'intelipost', 'Vitlog',              'Vitlog Standard'),
(NOW(), 78,   'intelipost', 'Transfolha',          'Transfolha Standard'),
(NOW(), 109,  'intelipost', 'Plimor',              'Plimor Standard'),
(NOW(), 112,  'intelipost', 'Movvi',               'Movvi Standard'),
(NOW(), 329,  'intelipost', 'Exata Cargo',         'Exata Cargo'),
(NOW(), 330,  'intelipost', 'Ociani Transportes',  'Ociani Transportes'),
(NOW(), 334,  'intelipost', 'Transduarte',         'Transduarte'),
(NOW(), 335,  'intelipost', 'Transville',          'Transville'),
(NOW(), 377,  'intelipost', 'Ativa Logistica',     'Ativa Logistica Standard'),
(NOW(), 393,  'intelipost', 'Expresso São Miguel', 'Expresso São Miguel'),
(NOW(), 453,  'intelipost', 'JadLog',              'JadLog Standard 2'),
(NOW(), 459,  'intelipost', 'Total Express',       'Total B2B'),
(NOW(), 770,  'intelipost', 'LagExpress',          'LagExpress'),
(NOW(), 1356, 'intelipost', 'STJ Transportes',     'STJ Standard'),
(NOW(), 1367, 'intelipost', 'Platinum Log',        'Platinum Standard 1'),
(NOW(), 1369, 'intelipost', 'Platinum Log',        'Platinum Standard 2'),
(NOW(), 4962, 'intelipost', 'Transcase',           'Transcase');
SQLBLOCK;

    $installer->run ($sqlBlock);
}

addMHubShippingsTable ($installer, Epicom_MHub_Helper_Data::SHIPPING_TABLE, 'Epicom MHub Shipping');

$installer->endSetup();

