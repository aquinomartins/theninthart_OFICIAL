CREATE TABLE sessions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id CHAR(36) NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    anonymous_token_hash CHAR(64) NOT NULL,
    schema_version VARCHAR(50) NOT NULL,
    mechanism_version VARCHAR(50) NOT NULL,
    status VARCHAR(50) NOT NULL,
    seed BIGINT UNSIGNED NOT NULL,
    revision INT UNSIGNED NOT NULL DEFAULT 1,
    metadata_json LONGTEXT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    last_seen_at DATETIME NOT NULL,
    completed_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_sessions_public_id (public_id),
    KEY idx_sessions_user_id (user_id),
    KEY idx_sessions_anonymous_token_hash (anonymous_token_hash),
    KEY idx_sessions_status_last_seen_at (status, last_seen_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE session_control_states (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    session_id BIGINT UNSIGNED NOT NULL,
    control_id BIGINT UNSIGNED NOT NULL,
    enabled TINYINT(1) NOT NULL DEFAULT 0,
    value_json LONGTEXT NOT NULL,
    revision INT UNSIGNED NOT NULL DEFAULT 1,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_session_control_states_session_control (session_id, control_id),
    KEY idx_session_control_states_control_id (control_id),
    CONSTRAINT fk_session_control_states_session FOREIGN KEY (session_id) REFERENCES sessions (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_session_control_states_control FOREIGN KEY (control_id) REFERENCES story_controls (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE session_widget_states (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    session_id BIGINT UNSIGNED NOT NULL,
    widget_id BIGINT UNSIGNED NOT NULL,
    state_json LONGTEXT NOT NULL,
    revision INT UNSIGNED NOT NULL DEFAULT 1,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_session_widget_states_session_widget (session_id, widget_id),
    KEY idx_session_widget_states_widget_id (widget_id),
    CONSTRAINT fk_session_widget_states_session FOREIGN KEY (session_id) REFERENCES sessions (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_session_widget_states_widget FOREIGN KEY (widget_id) REFERENCES story_widgets (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
