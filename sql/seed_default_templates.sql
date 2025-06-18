-- Seed data for Process Template feature

-- Create default templates for existing models
INSERT INTO ProcessTemplates (ModelID, TemplateName, Description)
VALUES
    (1, 'Standard Process', 'Default steps for Apollo-V1'),
    (2, 'Standard Process', 'Default steps for Apollo-V2'),
    (3, 'Standard Process', 'Default steps for Mars-Rover-1');

-- Insert steps for first template as example
INSERT INTO TemplateSteps (TemplateID, StepOrder, StepName, DefaultValue, IsRequired) VALUES
    (1, 1, 'Visual Inspection', NULL, TRUE),
    (1, 2, 'Weight Measurement', NULL, TRUE),
    (1, 3, 'Sign-off', NULL, TRUE);
