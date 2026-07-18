CREATE TABLE interaction_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id CHAR(36) NOT NULL,
    session_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    story_run_id BIGINT UNSIGNED NULL,
    event_type VARCHAR(100) NOT NULL,
    payload_json LONGTEXT NOT NULL,
    schema_version VARCHAR(50) NOT NULL,
    idempotency_key VARCHAR(191) NOT NULL,
    occurred_at DATETIME NOT NULL,
    received_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_interaction_events_public_id (public_id),
    UNIQUE KEY uq_interaction_events_idempotency_key (idempotency_key),
    KEY idx_interaction_events_session_id (session_id),
    KEY idx_interaction_events_user_id (user_id),
    KEY idx_interaction_events_story_run_id (story_run_id),
    KEY idx_interaction_events_type_received_at (event_type, received_at),
    CONSTRAINT fk_interaction_events_session FOREIGN KEY (session_id) REFERENCES sessions (id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_interaction_events_story_run FOREIGN KEY (story_run_id) REFERENCES story_runs (id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE public_snapshots (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id CHAR(36) NOT NULL,
    snapshot_key VARCHAR(100) NOT NULL,
    snapshot_type VARCHAR(100) NOT NULL,
    aggregate_json LONGTEXT NOT NULL,
    schema_version VARCHAR(50) NOT NULL,
    revision INT UNSIGNED NOT NULL DEFAULT 1,
    generated_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_public_snapshots_public_id (public_id),
    UNIQUE KEY uq_public_snapshots_snapshot_key (snapshot_key),
    KEY idx_public_snapshots_type_generated_at (snapshot_type, generated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
