<?php
if (!defined('WP_UNINSTALL_PLUGIN')) exit;
delete_option('wpcc_api_key');
delete_option('wpcc_active_status');
?>