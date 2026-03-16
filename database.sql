CREATE DATABASE IF NOT EXISTS smartload;
USE smartload;

CREATE TABLE teachers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    type ENUM('Full-time', 'Part-time') NOT NULL,
    max_units TINYINT UNSIGNED NOT NULL,
    current_units TINYINT UNSIGNED NOT NULL DEFAULT 0,
    expertise_tags VARCHAR(255) DEFAULT NULL,
    is_archived TINYINT(1) NOT NULL DEFAULT 0,
    INDEX idx_teachers_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE subjects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    program VARCHAR(100) NOT NULL,
    units TINYINT UNSIGNED NOT NULL,
    prerequisites VARCHAR(255) DEFAULT NULL,
    is_archived TINYINT(1) NOT NULL DEFAULT 0,
    INDEX idx_subjects_program (program)
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
    INDEX idx_assignments_status (status)
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

-- Migration for existing databases:
-- ALTER TABLE teachers ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0;
-- ALTER TABLE subjects ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0;
