<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

require_once 'tasks.php';
Disciple_Tools_Tasks_Base::instance();

if ( class_exists( 'DT_Dashboard_Tile' ) ) {
    require_once 'dashboard.php';
}