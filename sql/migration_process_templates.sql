-- Migration for Process Template feature (Week 3-4)

-- Create table to store templates for each model
CREATE TABLE IF NOT EXISTS ProcessTemplates (
    TemplateID INT AUTO_INCREMENT PRIMARY KEY,
    ModelID INT NOT NULL,
    TemplateName VARCHAR(100) NOT NULL,
    Description TEXT,
    IsActive BOOLEAN DEFAULT TRUE,
    CreatedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ModelID) REFERENCES Models(ModelID)
);

-- Template steps for a given template
CREATE TABLE IF NOT EXISTS TemplateSteps (
    StepID INT AUTO_INCREMENT PRIMARY KEY,
    TemplateID INT NOT NULL,
    StepOrder INT NOT NULL,
    StepName VARCHAR(100) NOT NULL,
    DefaultValue VARCHAR(255),
    IsRequired BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (TemplateID) REFERENCES ProcessTemplates(TemplateID)
);

-- Link Models to a default template (optional)
ALTER TABLE Models ADD COLUMN DefaultTemplateID INT NULL;
ALTER TABLE Models ADD CONSTRAINT FK_Models_DefaultTemplate
    FOREIGN KEY (DefaultTemplateID) REFERENCES ProcessTemplates(TemplateID);
