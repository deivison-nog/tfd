CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL,
    active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL,
    updated_at TEXT
);

CREATE TABLE patients (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    cpf TEXT NOT NULL UNIQUE,
    cns TEXT,
    phone TEXT,
    address TEXT,
    notes TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT
);

CREATE TABLE tfd_processes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    process_number TEXT NOT NULL UNIQUE,
    opening_year INTEGER NOT NULL,
    update_year INTEGER NOT NULL,
    treatment_location TEXT NOT NULL,
    patient_id INTEGER NOT NULL,
    cid TEXT,
    specialty TEXT,
    destination_city TEXT,
    request_date TEXT,
    priority TEXT,
    status TEXT NOT NULL,
    companion_required INTEGER NOT NULL DEFAULT 0,
    notes TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE RESTRICT
);

CREATE TABLE companions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    process_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    cpf TEXT,
    relationship TEXT,
    phone TEXT,
    notes TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT,
    FOREIGN KEY (process_id) REFERENCES tfd_processes(id) ON DELETE CASCADE
);

CREATE TABLE documents (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    entity_type TEXT NOT NULL,
    entity_id INTEGER NOT NULL,
    document_type TEXT NOT NULL,
    original_name TEXT,
    file_path TEXT,
    upload_status TEXT NOT NULL DEFAULT 'estrutura pronta para upload',
    notes TEXT,
    created_at TEXT NOT NULL
);

CREATE TABLE status_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    process_id INTEGER NOT NULL,
    previous_status TEXT,
    new_status TEXT NOT NULL,
    note TEXT,
    changed_by INTEGER,
    changed_at TEXT NOT NULL,
    FOREIGN KEY (process_id) REFERENCES tfd_processes(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE trips (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    process_id INTEGER NOT NULL,
    departure_date TEXT,
    return_date TEXT,
    departure_time TEXT,
    return_time TEXT,
    transport_type TEXT,
    vehicle TEXT,
    driver_name TEXT,
    boarding_place TEXT,
    execution_status TEXT NOT NULL DEFAULT 'agendado',
    notes TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT,
    FOREIGN KEY (process_id) REFERENCES tfd_processes(id) ON DELETE CASCADE
);
