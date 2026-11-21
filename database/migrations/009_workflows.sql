-- Workflow 运行记录表（最小闭环：手动触发 + 同步执行 + 日志查看）
CREATE TABLE IF NOT EXISTS workflow_runs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    repository_id BIGINT UNSIGNED NOT NULL,
    workflow_name VARCHAR(200) NOT NULL COMMENT 'workflow name (from yaml)',
    branch VARCHAR(100) NOT NULL,
    commit_sha VARCHAR(40) NOT NULL DEFAULT '',
    trigger_type ENUM('manual','push') NOT NULL DEFAULT 'manual',
    triggered_by BIGINT UNSIGNED NULL COMMENT 'user id (manual)',
    status ENUM('queued','running','success','failure','cancelled') NOT NULL DEFAULT 'queued',
    started_at TIMESTAMP NULL,
    finished_at TIMESTAMP NULL,
    log MEDIUMTEXT,
    error VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY wr_repo_index (repository_id),
    KEY wr_status_index (status),
    KEY wr_created_index (created_at),
    CONSTRAINT wr_repo_fk FOREIGN KEY (repository_id) REFERENCES repositories(id) ON DELETE CASCADE,
    CONSTRAINT wr_user_fk FOREIGN KEY (triggered_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
