CREATE TABLE IF NOT EXISTS behavior_baselines (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 feature_code VARCHAR(60) NOT NULL UNIQUE,
 feature_name_en VARCHAR(120) NOT NULL,
 feature_name_th VARCHAR(160) NOT NULL,
 unit VARCHAR(40) NOT NULL,
 mean_value DECIMAL(12,4) NOT NULL,
 sd_value DECIMAL(12,4) NOT NULL,
 threshold_value DECIMAL(12,4) NOT NULL,
 comparison_operator VARCHAR(4) NOT NULL DEFAULT '>',
 window_seconds INT UNSIGNED NOT NULL,
 sample_count INT UNSIGNED NOT NULL DEFAULT 0,
 threshold_method VARCHAR(80) NOT NULL DEFAULT 'policy',
 calculated_at DATETIME NULL,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_activity_logs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NULL,
 occurred_at DATETIME NOT NULL,
 ip_address VARCHAR(64) NOT NULL,
 ip_hash CHAR(64) NOT NULL,
 action VARCHAR(80) NOT NULL,
 url VARCHAR(500) NOT NULL,
 http_status SMALLINT UNSIGNED NOT NULL DEFAULT 200,
 response_time_ms DECIMAL(12,3) NOT NULL DEFAULT 0,
 login_success TINYINT(1) NULL,
 request_count INT UNSIGNED NOT NULL DEFAULT 1,
 order_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
 actual_label ENUM('normal','suspicious') NULL,
 source ENUM('runtime','simulation') NOT NULL DEFAULT 'runtime',
 experiment_case_id VARCHAR(80) NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_activity_user_time (user_id,occurred_at),
 KEY idx_activity_action_time (action,occurred_at),
 KEY idx_activity_source_case (source,experiment_case_id),
 CONSTRAINT fk_activity_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS behavior_detections (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NULL,
 activity_log_id BIGINT UNSIGNED NULL,
 feature_code VARCHAR(60) NOT NULL,
 window_started_at DATETIME NOT NULL,
 window_ended_at DATETIME NOT NULL,
 observed_value DECIMAL(12,4) NOT NULL,
 threshold_value DECIMAL(12,4) NOT NULL,
 predicted_label ENUM('normal','suspicious') NOT NULL,
 actual_label ENUM('normal','suspicious') NULL,
 detection_time_ms DECIMAL(12,3) NOT NULL DEFAULT 0,
 action_taken VARCHAR(80) NOT NULL DEFAULT 'monitored',
 source ENUM('runtime','simulation') NOT NULL DEFAULT 'runtime',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_detection_feature_time (feature_code,created_at),
 KEY idx_detection_user_time (user_id,created_at),
 CONSTRAINT fk_detection_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
 CONSTRAINT fk_detection_activity FOREIGN KEY(activity_log_id) REFERENCES user_activity_logs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS behavior_experiments (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 run_code VARCHAR(80) NOT NULL UNIQUE,
 user_count INT UNSIGNED NOT NULL,
 observation_days INT UNSIGNED NOT NULL,
 total_cases INT UNSIGNED NOT NULL,
 true_positive INT UNSIGNED NOT NULL,
 true_negative INT UNSIGNED NOT NULL,
 false_positive INT UNSIGNED NOT NULL,
 false_negative INT UNSIGNED NOT NULL,
 accuracy DECIMAL(9,6) NOT NULL,
 precision_score DECIMAL(9,6) NOT NULL,
 recall_score DECIMAL(9,6) NOT NULL,
 f1_score DECIMAL(9,6) NOT NULL,
 false_positive_rate DECIMAL(9,6) NOT NULL,
 avg_detection_time_ms DECIMAL(12,3) NOT NULL,
 p95_detection_time_ms DECIMAL(12,3) NOT NULL,
 avg_response_time_ms DECIMAL(12,3) NOT NULL,
 p95_response_time_ms DECIMAL(12,3) NOT NULL,
 throughput_cases_sec DECIMAL(12,3) NOT NULL,
 cpu_time_ms DECIMAL(12,3) NOT NULL,
 peak_memory_mb DECIMAL(12,3) NOT NULL,
 report_json JSON NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO behavior_baselines(feature_code,feature_name_en,feature_name_th,unit,mean_value,sd_value,threshold_value,window_seconds,threshold_method) VALUES
('login_per_min','Login attempts per minute','การเข้าสู่ระบบต่อนาที','events/min',0.4,0.3,2,60,'fixed research policy'),
('request_per_min','Requests per minute','คำขอต่อนาที','requests/min',15,8,50,60,'fixed research policy'),
('order_per_hour','Orders per hour','คำสั่งซื้อต่อชั่วโมง','orders/hour',1.2,1.0,5,3600,'fixed research policy'),
('failed_login_per_10min','Failed logins per 10 minutes','เข้าสู่ระบบล้มเหลวต่อ 10 นาที','events/10min',0.5,1.2,5,600,'fixed research policy')
ON DUPLICATE KEY UPDATE feature_name_en=VALUES(feature_name_en),feature_name_th=VALUES(feature_name_th),unit=VALUES(unit),threshold_value=VALUES(threshold_value),window_seconds=VALUES(window_seconds),threshold_method=VALUES(threshold_method);
