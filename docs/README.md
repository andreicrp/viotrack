# VioTrack Documentation

This folder contains technical documentation for the VioTrack Student Violation Tracking System.

## Files

### 1. **VIOLATION_RECORD_ANALYSIS.md**
Complete analysis of the `violation-record.php` page and its connected components.

**Covers:**
- Overview of the violation record system
- Connected PHP files and handlers
- Database tables and structure
- All JavaScript functions (30+)
- AJAX endpoints
- CSS files
- Data flows and component relationships
- Security considerations
- Key features

### 2. **DOC_PROOF_FEATURE.md**
Technical documentation for the Doc Proof (Resolution Letter) feature.

**Covers:**
- Feature functionality and purpose
- Connected PHP files:
  - violation-record.php
  - status-modal.php
  - update-status-handler.php
  - get-student-violations.php
  - resolution-modal.php
  - connect.php
  - adminstudentviolation.php
- Data flow (two implementations: client-side and AJAX-based)
- Database queries and tables
- All JavaScript functions for resolution letters
- Modal HTML structure
- CSS styling files
- Security features
- Comparison of two implementations
- User workflow

## How to Use

1. Refer to these documents when:
   - Developing new features related to violation records
   - Maintaining or debugging the violation tracking system
   - Understanding the data flows and relationships
   - Implementing security patches

2. Keep these documents updated when:
   - Adding new functions
   - Modifying database schemas
   - Changing PHP handlers
   - Adding new CSS styles

## System Overview

The VioTrack system consists of:
- **Dashboard**: Overview of violations and trends
- **Violation Record**: Manage and track student violations
- **Students**: Student management and violation history
- **Teachers**: Staff management
- **Violations**: Manage violation types and categories
- **Reports**: Generate reports and SMS notifications
- **Meetings**: Schedule parent meetings

## Documentation Notes

- These files are auto-generated documentation from code analysis
- Regenerate if system architecture changes significantly
- All PHP files use prepared statements or mysqli escape functions for security
- System uses AJAX for dynamic updates without page reloads
- Mobile responsive design implemented across all pages

