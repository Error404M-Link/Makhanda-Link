select * from ward_councillor;
select * from ward;

-- file_size_bytes => SHOWS HOW LARGE THE FILE IS
-- Storing period_start and period_end dates allows you to track the exact timeframe the ward summary covers, rather than just when it was uploaded.

CREATE TABLE ward_summaries (
    summary_id INT AUTO_INCREMENT PRIMARY KEY,
    ward_id INT NOT NULL,
    councillor_username VARCHAR(50) NOT NULL, -- Matched to VARCHAR(50)
    title VARCHAR(255) NOT NULL,
    file_path VARCHAR(512) NOT NULL,
    file_size_bytes BIGINT,
    mime_type VARCHAR(100) DEFAULT 'application/pdf',
    period_start DATE,
    period_end DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key referencing the Ward table
    CONSTRAINT fk_summary_ward 
        FOREIGN KEY (ward_id) REFERENCES ward(ward_id) 
        ON DELETE RESTRICT ON UPDATE CASCADE,

    -- Foreign key referencing ward_councillor(username)
    CONSTRAINT fk_summary_councillor 
        FOREIGN KEY (councillor_username) REFERENCES ward_councillor(username) 
        ON DELETE RESTRICT ON UPDATE CASCADE
);

select * from ward_summaries;