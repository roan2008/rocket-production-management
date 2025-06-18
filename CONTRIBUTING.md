# 🤝 Contributing to Rocket Production Management System

We love your input! We want to make contributing to this project as easy and transparent as possible, whether it's:

- Reporting a bug
- Discussing the current state of the code
- Submitting a fix
- Proposing new features
- Becoming a maintainer

## 🔄 Development Process

We use GitHub to host code, to track issues and feature requests, as well as accept pull requests.

### 1. Fork & Clone
```bash
# Fork the repository on GitHub
git clone https://github.com/[your-username]/rocket-production-management.git
cd rocket-production-management
```

### 2. Create Feature Branch
```bash
git checkout -b feature/amazing-new-feature
```

### 3. Development Setup
```bash
# Setup XAMPP environment
# Import database schema
mysql -u root -p < sql/schema_mysql.sql

# Configure database connection
cp config.php.example config.php
# Edit config.php with your database credentials
```

## 📋 Pull Request Process

1. **Update Documentation**: Ensure any install or build dependencies are removed
2. **Update README**: Update the README.md with details of changes if applicable
3. **Version Numbers**: Update version numbers following [SemVer](http://semver.org/)
4. **Code Review**: The PR will be merged once you have the sign-off of at least one maintainer

### Pull Request Template
```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix (non-breaking change which fixes an issue)
- [ ] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Documentation update

## Testing
- [ ] Manual testing completed
- [ ] All existing tests pass
- [ ] New tests added for new functionality

## Screenshots (if applicable)
Add screenshots to help explain your changes

## Checklist
- [ ] My code follows the project's coding standards
- [ ] I have performed a self-review of my own code
- [ ] I have commented my code in hard-to-understand areas
- [ ] I have made corresponding changes to the documentation
```

## 🐛 Bug Reports

We use GitHub issues to track public bugs. Report a bug by [opening a new issue](https://github.com/[your-username]/rocket-production-management/issues).

### Great Bug Reports Include:

- **Quick summary** and/or background
- **Steps to reproduce**
  - Be specific!
  - Give sample code if you can
- **What you expected would happen**
- **What actually happens**
- **Screenshots** (if applicable)
- **Environment details** (OS, PHP version, MySQL version)

### Bug Report Template
```markdown
## Bug Description
A clear and concise description of what the bug is.

## Steps to Reproduce
1. Go to '...'
2. Click on '....'
3. Scroll down to '....'
4. See error

## Expected Behavior
A clear description of what you expected to happen.

## Actual Behavior
A clear description of what actually happened.

## Screenshots
If applicable, add screenshots to help explain your problem.

## Environment
- OS: [e.g. Windows 10, macOS 12.0, Ubuntu 20.04]
- PHP Version: [e.g. 7.4.21]
- MySQL Version: [e.g. 5.7.34]
- Browser: [e.g. Chrome 91.0.4472.124]
- XAMPP Version: [if applicable]

## Additional Context
Add any other context about the problem here.
```

## 💡 Feature Requests

We use GitHub issues to track feature requests. Submit a feature request by [opening a new issue](https://github.com/[your-username]/rocket-production-management/issues) with the label "enhancement".

### Feature Request Template
```markdown
## Feature Description
A clear and concise description of the feature you'd like to see.

## Problem Statement
What problem does this feature solve? Who would benefit from it?

## Proposed Solution
Describe how you envision this feature working.

## Alternative Solutions
Any alternative solutions or features you've considered.

## Additional Context
Add any other context, mockups, or screenshots about the feature request.

## Implementation Considerations
- Security implications
- Performance impact
- Database changes required
- UI/UX considerations
```

## 💻 Coding Standards

### PHP Standards
```php
<?php
/**
 * Class description
 * 
 * @author Your Name
 * @version 1.0.0
 */
class ProductionOrder {
    // Properties (private first)
    private $productionNumber;
    
    // Constructor
    public function __construct($database) {
        $this->database = $database;
    }
    
    // Public methods
    public function createOrder($data) {
        // Method implementation
    }
    
    // Private methods
    private function validateData($data) {
        // Helper method implementation
    }
}
```

### JavaScript Standards
```javascript
// Use ES6+ features
const ProductionOrderManager = {
    // Method naming: camelCase
    initializeForm() {
        // Implementation
    },
    
    // Async operations
    async saveOrder(orderData) {
        try {
            const response = await this.makeRequest('/api/orders.php', orderData);
            return response;
        } catch (error) {
            console.error('Save order failed:', error);
            throw error;
        }
    }
};
```

### CSS Standards
```css
/* BEM Methodology */
.production-order { /* Block */ }
.production-order__header { /* Element */ }
.production-order--completed { /* Modifier */ }

/* Consistent naming */
.btn-primary { }
.form-group { }
.loading-spinner { }
```

## 🧪 Testing Guidelines

### Manual Testing Checklist
- [ ] Login/logout functionality
- [ ] Production order CRUD operations
- [ ] Process log management
- [ ] Form validation
- [ ] AJAX functionality
- [ ] Browser compatibility (Chrome, Firefox, Safari, Edge)
- [ ] Responsive design
- [ ] Performance (page load times)

### Code Review Checklist
- [ ] Code follows project standards
- [ ] No security vulnerabilities
- [ ] Proper error handling
- [ ] Performance considerations
- [ ] Documentation updated
- [ ] No breaking changes (unless intentional)

## 🔒 Security Guidelines

### Security Best Practices
- **Always use prepared statements** for database queries
- **Validate and sanitize** all user inputs
- **Use CSRF tokens** for forms
- **Implement proper authentication** and authorization
- **Keep dependencies updated**
- **Follow OWASP guidelines**

### Security Checklist
- [ ] SQL injection prevention
- [ ] XSS protection
- [ ] CSRF protection
- [ ] Input validation
- [ ] Output encoding
- [ ] Session security
- [ ] File upload security

## 📝 Documentation Guidelines

### Code Documentation
- Document **why**, not **what**
- Use PHPDoc for PHP functions and classes
- Add inline comments for complex logic
- Keep README.md updated
- Document API endpoints

### Commit Message Format
```
type(scope): description

feat(orders): add production order filtering
fix(auth): resolve session timeout issue
docs(readme): update installation instructions
test(orders): add validation tests
refactor(database): optimize query performance
```

### Types:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation only changes
- `style`: Code style changes (formatting, etc.)
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks

## 🏷️ Issue Labels

We use labels to categorize issues:

- `bug` - Something isn't working
- `enhancement` - New feature or request
- `documentation` - Improvements or additions to documentation
- `good first issue` - Good for newcomers
- `help wanted` - Extra attention is needed
- `question` - Further information is requested
- `security` - Security-related issues
- `performance` - Performance improvements
- `ui/ux` - User interface and experience improvements

## 🎯 Development Priorities

### High Priority
1. Security vulnerabilities
2. Critical bugs affecting core functionality
3. Performance issues
4. Data integrity problems

### Medium Priority
1. Feature enhancements
2. UI/UX improvements
3. Code refactoring
4. Documentation updates

### Low Priority
1. Minor bug fixes
2. Code style improvements
3. Optional features
4. Performance optimizations

## 📞 Getting Help

If you need help:

1. **Check existing issues** - Your question might already be answered
2. **Read the documentation** - Check README.md and other docs
3. **Ask in discussions** - Use GitHub Discussions for questions
4. **Open an issue** - For bugs or feature requests

## 📜 Code of Conduct

### Our Pledge
We pledge to make participation in our project a harassment-free experience for everyone, regardless of age, body size, disability, ethnicity, gender identity and expression, level of experience, nationality, personal appearance, race, religion, or sexual identity and orientation.

### Our Standards
- Use welcoming and inclusive language
- Be respectful of differing viewpoints and experiences
- Gracefully accept constructive criticism
- Focus on what is best for the community
- Show empathy towards other community members

### Unacceptable Behavior
- Harassment, trolling, or insulting/derogatory comments
- Public or private harassment
- Publishing others' private information
- Other conduct which could reasonably be considered inappropriate

## 📄 License

By contributing to this project, you agree that your contributions will be licensed under the MIT License.

---

**Thank you for contributing to the Rocket Production Management System! 🚀**

*Last updated: June 18, 2025*
