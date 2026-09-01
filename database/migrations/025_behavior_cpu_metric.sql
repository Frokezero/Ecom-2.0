ALTER TABLE behavior_experiments ADD COLUMN cpu_usage_percent DECIMAL(9,3) NOT NULL DEFAULT 0 AFTER cpu_time_ms;
