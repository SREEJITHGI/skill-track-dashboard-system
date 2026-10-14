
-- Create the database
CREATE DATABASE IF NOT EXISTS skill_tracking_system;
USE skill_tracking_system;

-- Create admin_users table
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create employees table
CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(20) NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    department VARCHAR(50),
    position VARCHAR(50),
    target_role_id INT NULL,
    password VARCHAR(255) NOT NULL,
    join_date DATE NOT NULL,
    last_login TIMESTAMP NULL
);

-- Create skills table
CREATE TABLE skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    skill_name VARCHAR(100) NOT NULL UNIQUE,
    category VARCHAR(50),
    description TEXT
);

-- Create job_roles table for benchmark targets
CREATE TABLE job_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(100) NOT NULL UNIQUE,
    department VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create role_skill_benchmarks table (stores expected proficiency 1-10 per skill for each role)
CREATE TABLE role_skill_benchmarks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    skill_id INT NOT NULL,
    required_proficiency INT NOT NULL CHECK (required_proficiency BETWEEN 1 AND 10),
    is_mandatory BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (role_id) REFERENCES job_roles(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE,
    UNIQUE KEY (role_id, skill_id)
);

-- Create employee_skills table
CREATE TABLE employee_skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT,
    skill_id INT,
    proficiency_level INT NOT NULL CHECK (proficiency_level BETWEEN 1 AND 10),
    last_updated DATE NOT NULL,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE,
    UNIQUE KEY (employee_id, skill_id)
);

-- Create certifications table
CREATE TABLE certifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cert_name VARCHAR(100) NOT NULL,
    provider VARCHAR(100),
    description TEXT,
    valid_period INT -- in months, NULL if it doesn't expire
);

-- Create employee_certifications table
CREATE TABLE employee_certifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT,
    certification_id INT,
    date_earned DATE NOT NULL,
    expiration_date DATE,
    cert_file_path VARCHAR(255), -- Path to uploaded certificate file
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (certification_id) REFERENCES certifications(id) ON DELETE CASCADE
);

-- Create training table
CREATE TABLE training (
    id INT AUTO_INCREMENT PRIMARY KEY,
    training_name VARCHAR(100) NOT NULL,
    description TEXT,
    provider VARCHAR(100),
    duration VARCHAR(50), -- e.g., "3 months", "40 hours"
    related_skills TEXT
);

-- Create employee_training table
CREATE TABLE employee_training (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT,
    training_id INT,
    start_date DATE,
    completion_date DATE,
    status ENUM('Not Started', 'In Progress', 'Completed', 'Failed') DEFAULT 'Not Started',
    completion_certificate VARCHAR(255),
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (training_id) REFERENCES training(id) ON DELETE CASCADE
);

-- Create activities table (for tracking actions in the system)
CREATE TABLE activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT,
    activity_description TEXT NOT NULL,
    activity_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Insert a default admin user (username: admin, password: admin123)
INSERT INTO admin_users (username, password, email) 
VALUES ('admin', '$2y$10$QcBnHdK9p5bV2BEcjkUF..Kh4Nj8AdYQ6RGewsDPfgHmGHFCp1J8m', 'admin@example.com');

-- Insert some sample skills
INSERT INTO skills (skill_name, category, description) VALUES 
('Java Programming', 'Technical', 'Java SE and EE development'),
('JavaScript', 'Technical', 'Web development with JavaScript'),
('Project Management', 'Soft Skill', 'Managing projects, resources and timelines'),
('SQL', 'Technical', 'Database query language'),
('Leadership', 'Soft Skill', 'Team leadership and management'),
('Python', 'Technical', 'Python programming language'),
('Communication', 'Soft Skill', 'Verbal and written communication skills');

-- Insert sample certifications
INSERT INTO certifications (cert_name, provider, description, valid_period) VALUES
('AWS Certified Solutions Architect', 'Amazon Web Services', 'Professional level certification for AWS architecture', 24),
('PMP', 'Project Management Institute', 'Project Management Professional certification', 36),
('Microsoft Azure Fundamentals', 'Microsoft', 'Fundamental understanding of Azure cloud services', NULL),
('Oracle Certified Java Programmer', 'Oracle', 'Java SE programming certification', NULL);

-- Insert sample training programs
INSERT INTO training (training_name, description, provider, duration, related_skills) VALUES
('Advanced Java Programming', 'In-depth Java course covering advanced topics', 'Udemy', '40 hours', 'Java Programming'),
('Leadership Fundamentals', 'Basic leadership training course', 'Internal', '3 days', 'Leadership, Communication'),
('Database Design and Optimization', 'Learning optimal database design principles', 'Coursera', '4 weeks', 'SQL');

-- IMPORTANT: Insert a sample employee for testing (ID: EMP001, password: password)
INSERT INTO employees (employee_id, first_name, last_name, email, department, position, password, join_date) 
VALUES ('EMP001', 'John', 'Doe', 'john@example.com', 'IT', 'Software Developer', '$2y$10$fXupgvyl5n2RV51.x50coeJMwvS58LKpRKPeF2JNcKT3SqMcuE32K', '2023-01-15');

-- Add some skills to the sample employee
INSERT INTO employee_skills (employee_id, skill_id, proficiency_level, last_updated) VALUES
(1, 1, 8, '2023-02-10'), -- Java
(1, 2, 7, '2023-03-15'), -- JavaScript
(1, 4, 9, '2023-01-20'); -- SQL

-- Add certifications to the sample employee
INSERT INTO employee_certifications (employee_id, certification_id, date_earned, expiration_date) VALUES
(1, 3, '2023-04-10', NULL), -- Microsoft Azure Fundamentals (doesn't expire)
(1, 4, '2022-11-15', NULL); -- Oracle Certified Java Programmer

-- Add sample activities
INSERT INTO activities (employee_id, activity_description, activity_date) VALUES
(1, 'Completed Java Programming skill assessment', '2023-02-10 14:30:00'),
(1, 'Earned Microsoft Azure Fundamentals certification', '2023-04-10 10:15:00'),
(1, 'Updated JavaScript proficiency level from 5 to 7', '2023-03-15 09:45:00');

-- Insert sample job roles for benchmark gap analysis
INSERT INTO job_roles (role_name, department, description) VALUES
('Senior Software Engineer', 'IT', 'Designs and builds scalable enterprise software, conducts code reviews, and architects data layers.'),
('Full-Stack Web Developer', 'IT', 'Builds dynamic user interfaces and backend REST services across modern web stacks.'),
('Technical Project Lead', 'Management', 'Coordinates engineering delivery, tracks timelines, manages stakeholders, and mentors squad engineers.'),
('Data & Backend Specialist', 'IT', 'Optimizes database queries, manages storage systems, and designs data pipelines.');

-- Set role skill benchmarks (Expected score 1-10 per role)
-- 1. Senior Software Engineer Benchmarks:
INSERT INTO role_skill_benchmarks (role_id, skill_id, required_proficiency, is_mandatory) VALUES
(1, 1, 9, TRUE),  -- Java (Target: 9)
(1, 2, 8, TRUE),  -- JavaScript (Target: 8)
(1, 4, 8, TRUE),  -- SQL (Target: 8)
(1, 5, 6, FALSE), -- Leadership (Target: 6)
(1, 7, 7, TRUE);  -- Communication (Target: 7)

-- 2. Full-Stack Web Developer Benchmarks:
INSERT INTO role_skill_benchmarks (role_id, skill_id, required_proficiency, is_mandatory) VALUES
(2, 2, 9, TRUE),  -- JavaScript (Target: 9)
(2, 4, 7, TRUE),  -- SQL (Target: 7)
(2, 1, 6, FALSE), -- Java (Target: 6)
(2, 6, 7, FALSE); -- Python (Target: 7)

-- 3. Technical Project Lead Benchmarks:
INSERT INTO role_skill_benchmarks (role_id, skill_id, required_proficiency, is_mandatory) VALUES
(3, 3, 9, TRUE),  -- Project Management (Target: 9)
(3, 5, 8, TRUE),  -- Leadership (Target: 8)
(3, 7, 9, TRUE),  -- Communication (Target: 9)
(3, 1, 6, FALSE); -- Java (Target: 6)

-- Assign John Doe's target role to Senior Software Engineer
UPDATE employees SET target_role_id = 1 WHERE id = 1;
