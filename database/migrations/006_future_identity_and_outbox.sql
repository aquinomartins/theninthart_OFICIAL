CREATE TABLE users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id CHAR(36) NOT NULL,
    display_name VARCHAR(255) NULL,
    status VARCHAR(50) NOT NULL,
    metadata_json LONGTEXT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_public_id (public_id),
    KEY idx_users_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE auth_identities (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    provider VARCHAR(100) NOT NULL,
    provider_subject VARCHAR(191) NOT NULL,
    metadata_json LONGTEXT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_auth_identities_provider_subject (provider, provider_subject),
    KEY idx_auth_identities_user_id (user_id),
    CONSTRAINT fk_auth_identities_user FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE outbox_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id CHAR(36) NOT NULL,
    aggregate_type VARCHAR(100) NOT NULL,
    aggregate_public_id CHAR(36) NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    payload_json LONGTEXT NOT NULL,
    schema_version VARCHAR(50) NOT NULL,
    status VARCHAR(50) NOT NULL,
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    available_at DATETIME NOT NULL,
    processed_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_outbox_events_public_id (public_id),
    KEY idx_outbox_events_available (status, available_at),
    KEY idx_outbox_events_aggregate (aggregate_type, aggregate_public_id),
    KEY idx_outbox_events_event_type (event_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE sessions
    ADD CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE story_runs
    ADD CONSTRAINT fk_story_runs_user FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE interaction_events
    ADD CONSTRAINT fk_interaction_events_user FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL;
