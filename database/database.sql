CREATE DATABASE IF NOT EXISTS smartload;
USE smartload;

-- ===================================================
-- CURRENT OPERATIONAL DATA TABLES
-- ===================================================

CREATE TABLE teachers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    type ENUM('Full-time', 'Part-time') NOT NULL,
    max_units TINYINT UNSIGNED NOT NULL,
    current_units TINYINT UNSIGNED NOT NULL DEFAULT 0,
    expertise_tags VARCHAR(255) DEFAULT NULL,
    is_archived TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_teachers_type (type),
    INDEX idx_teachers_archived (is_archived)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE subjects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    program VARCHAR(100) NOT NULL,
    units TINYINT UNSIGNED NOT NULL,
    prerequisites VARCHAR(255) DEFAULT NULL,
    is_archived TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_subjects_program (program),
    INDEX idx_subjects_archived (is_archived)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE teacher_availability (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT UNSIGNED NOT NULL,
    day_of_week ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    INDEX idx_availability_teacher (teacher_id),
    INDEX idx_availability_day (day_of_week)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE schedules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject_id INT UNSIGNED NOT NULL,
    day_of_week ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    room VARCHAR(50) NOT NULL,
    section VARCHAR(50) DEFAULT NULL,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    INDEX idx_schedules_subject (subject_id),
    INDEX idx_schedules_day (day_of_week),
    INDEX idx_schedules_room (room)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE assignments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject_id INT UNSIGNED NOT NULL,
    teacher_id INT UNSIGNED NOT NULL,
    status ENUM('Pending','Approved','Rejected','Manual') NOT NULL DEFAULT 'Pending',
    rationale TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    INDEX idx_assignments_subject (subject_id),
    INDEX idx_assignments_teacher (teacher_id),
    INDEX idx_assignments_status (status),
    INDEX idx_assignments_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE audit_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    action_type VARCHAR(50) NOT NULL,
    description TEXT DEFAULT NULL,
    user VARCHAR(100) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_action (action_type),
    INDEX idx_audit_user (user),
    INDEX idx_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE policy_settings (
    id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
    max_teaching_load TINYINT UNSIGNED NOT NULL DEFAULT 18,
    expertise_weight TINYINT UNSIGNED NOT NULL DEFAULT 70,
    availability_weight TINYINT UNSIGNED NOT NULL DEFAULT 30,
    detect_schedule_overlaps TINYINT(1) NOT NULL DEFAULT 1,
    flag_overload_teachers TINYINT(1) NOT NULL DEFAULT 1,
    check_prerequisites TINYINT(1) NOT NULL DEFAULT 1,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===================================================
-- HISTORICAL DATA TABLES (For Predictive Analytics)
-- ===================================================

CREATE TABLE historical_teachers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academic_year VARCHAR(20) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    original_id INT UNSIGNED,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) DEFAULT NULL,
    type ENUM('Full-time', 'Part-time') NOT NULL,
    max_units TINYINT UNSIGNED NOT NULL,
    units_assigned TINYINT UNSIGNED NOT NULL DEFAULT 0,
    expertise_tags VARCHAR(255) DEFAULT NULL,
    recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_hist_teachers_year (academic_year),
    INDEX idx_hist_teachers_semester (semester),
    INDEX idx_hist_teachers_year_semester (academic_year, semester),
    INDEX idx_hist_teachers_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE historical_subjects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academic_year VARCHAR(20) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    original_id INT UNSIGNED,
    course_code VARCHAR(20) NOT NULL,
    name VARCHAR(150) NOT NULL,
    program VARCHAR(100) NOT NULL,
    units TINYINT UNSIGNED NOT NULL,
    prerequisites VARCHAR(255) DEFAULT NULL,
    recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_hist_subjects_year (academic_year),
    INDEX idx_hist_subjects_semester (semester),
    INDEX idx_hist_subjects_year_semester (academic_year, semester),
    INDEX idx_hist_subjects_code (course_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE historical_schedules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academic_year VARCHAR(20) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    subject_id INT UNSIGNED NOT NULL,
    subject_code VARCHAR(20) NOT NULL,
    day_of_week ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    room VARCHAR(50) NOT NULL,
    section VARCHAR(50) DEFAULT NULL,
    recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_hist_schedules_year (academic_year),
    INDEX idx_hist_schedules_semester (semester),
    INDEX idx_hist_schedules_year_semester (academic_year, semester),
    INDEX idx_hist_schedules_day (day_of_week)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE historical_assignments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academic_year VARCHAR(20) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    subject_id INT UNSIGNED NOT NULL,
    subject_code VARCHAR(20) NOT NULL,
    teacher_id INT UNSIGNED NOT NULL,
    teacher_name VARCHAR(100) NOT NULL,
    teacher_email VARCHAR(150) DEFAULT NULL,
    status ENUM('Assigned','Unassigned','Substituted') NOT NULL DEFAULT 'Assigned',
    rationale TEXT DEFAULT NULL,
    recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_hist_assignments_year (academic_year),
    INDEX idx_hist_assignments_semester (semester),
    INDEX idx_hist_assignments_year_semester (academic_year, semester),
    INDEX idx_hist_assignments_teacher_email (teacher_email),
    INDEX idx_hist_assignments_subject_code (subject_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE historical_analytics_metadata (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academic_year VARCHAR(20) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    import_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total_teachers INT UNSIGNED DEFAULT 0,
    total_subjects INT UNSIGNED DEFAULT 0,
    total_assignments INT UNSIGNED DEFAULT 0,
    notes TEXT DEFAULT NULL,
    UNIQUE KEY uk_analytics_year_semester (academic_year, semester),
    INDEX idx_analytics_year (academic_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===================================================
-- MIGRATION SUPPORT
-- ===================================================

-- Migration for existing databases:
-- ALTER TABLE teachers ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0;
-- ALTER TABLE subjects ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0;
