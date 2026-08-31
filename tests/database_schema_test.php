<?php
require_once __DIR__.'/../config/database.php';
$db=(new Database())->getConnection();if(!$db)throw new RuntimeException('Database unavailable');
$required=['payment_transactions','return_requests','return_request_items','refunds','seller_ledger','audit_logs','login_attempts','password_reset_tokens','order_fulfillments','user_addresses','wishlists','product_images','product_variants','auth_challenges','email_delivery_logs','security_events','security_blocks','security_rules','request_rate_counters','user_login_locations','order_status_history','support_tickets','support_messages','privacy_consents','chat_conversations','chat_messages','chat_knowledge_base'];
$tables=$db->query('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()')->fetchAll(PDO::FETCH_COLUMN);
foreach($required as $table)if(!in_array($table,$tables,true))throw new RuntimeException('Missing table: '.$table);
$columns=[];foreach($db->query("SHOW COLUMNS FROM order_items") as $row)$columns[]=$row['Field'];foreach(['variant_id','variant_sku','variant_name'] as $column)if(!in_array($column,$columns,true))throw new RuntimeException('Missing order_items column: '.$column);
$orderColumns=[];foreach($db->query('SHOW COLUMNS FROM orders') as $row)$orderColumns[]=$row['Field'];foreach(['subtotal_amount','discount_amount','shipping_amount','payment_expires_at'] as $column)if(!in_array($column,$orderColumns,true))throw new RuntimeException('Missing orders column: '.$column);
echo "Database commerce schema tests passed\n";
