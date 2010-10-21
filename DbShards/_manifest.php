<?php
$config = array(
    'version' => '1.0',
    'map' => array(
        'Sync.php' => array(
            'file' => 'libs/Sonic/Database/Sync.php',
            'extends' => true
        ),
        'Dao.php' => array(
            'file' => 'libs/Sonic/Database/Sync/Dao.php'
        ),
        'Object.php' => array(
            'file' => 'libs/Sonic/Object.php',
            'extends' => true
        ),
        'ShardSelector.php' => array(
            'file' => 'libs/Sonic/Database/ShardSelector.php'
        )
    ),
);
