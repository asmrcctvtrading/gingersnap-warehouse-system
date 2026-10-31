<!-- converted from PROPOSAL.docx -->

# BARANGAY ESTABLISHMENT LOCATOR SYSTEM
## Project Proposal
Local Network-Based Application
Proposed Project Cost: ₱200,000.00
Project Duration: Four (4) weeks
Deployment Environment: Windows PC with XAMPP
Network Access: Barangay Local Area Network (LAN)
Technology Stack: PHP, MySQL/MariaDB, JavaScript, Leaflet, HTML5, and CSS3
## 1. Executive Summary
The Barangay Establishment Locator System is a web-based application designed to centralize, organize, and locate establishments within the barangay. The system will provide an interactive digital map and a searchable directory of establishments, enabling authorized barangay personnel to access and manage establishment information efficiently.
The application will operate on a dedicated local server running XAMPP. Multiple computers connected to the barangay's local area network will access the application through a standard web browser, eliminating the need for separate client installations.
This solution will improve information accessibility, enhance record management capabilities, streamline establishment identification, and increase administrative efficiency while maintaining full control over locally stored data.
## 2. Project Objectives
The project will achieve the following objectives:
1. Develop a centralized digital directory of all establishments within the barangay.
2. Provide an interactive map displaying establishment locations.
3. Enable users to search and filter establishments by name, category, address, and designated area.
4. Furnish authorized personnel with comprehensive tools to create, update, verify, and manage establishment records.
5. Support simultaneous access from multiple computers connected to the barangay LAN.
6. Protect establishment information through authentication, role-based access controls, and regular backups.
7. Reduce dependence on manually maintained directories and paper-based location records.
## 3. Project Scope and Features
### 3.1 Interactive Establishment Map
• Display establishment locations using map markers on an interactive interface.
• Present establishment names, categories, addresses, and available contact information when markers are selected.
• Enable map navigation, zooming, and location exploration.
• Display establishment profiles directly from marker interactions.
### 3.2 Establishment Directory and Search
• Search establishments by name, category, and location.
• Filter and view establishment profiles, business hours, contact details, and addresses.
• Display associated establishment images and precise location coordinates.
• Support advanced filtering by multiple criteria simultaneously.
### 3.3 Establishment Record Management
• Create and add new establishment records to the system.
• Edit and update existing establishment information.
• Deactivate outdated or permanently closed establishment records.
• Upload and manage establishment images with automatic file validation.
• Track and record verification status and approval information.
### 3.4 Administrator Dashboard
• Manage all establishment records from a centralized interface.
• Review submitted or newly encoded establishment information.
• Approve or reject records in accordance with assigned permissions.
• View summary statistics, analytics, and administrative reports.
• Manage authorized user accounts and assign role-based access permissions.
### 3.5 User Access and Security
The application will support role-based access control with the following user roles, subject to final client approval:
• Administrator: Manage system users, application settings, and all establishment records.
• Encoder: Create and update records within assigned departmental or functional permissions.
• Viewer: Search and view approved establishment information only.
Security measures will include the following:
• Password hashing using industry-standard algorithms.
• Prepared SQL statements to prevent injection attacks.
• Comprehensive input validation and sanitization.
• Secure session handling with appropriate timeout mechanisms.
• Cross-Site Request Forgery (CSRF) protection.
• Restrictions on unauthorized record modifications.
### 3.6 Local Network Access
• Install the application on a dedicated Windows server computer within the barangay office.
• Utilize XAMPP to run the Apache web server, PHP runtime, and MySQL/MariaDB database engine.
• Configure the system to allow authorized client computers on the barangay LAN to access the application through a web browser.
• Maintain a single centralized database to ensure information consistency across all users.
• Configure Windows Firewall rules to restrict unauthorized access appropriately.
• Restrict access to database administration tools and database ports to authorized server-side operations only.
### 3.7 Backup and Recovery
• Provide a documented database backup procedure suitable for routine use.
• Document comprehensive database restoration procedures for recovery scenarios.
• Recommend scheduled backups to external storage devices or other approved backup destinations.
• Supply step-by-step recovery instructions for use following server failure or data loss.
## 4. Technical Architecture
The application will employ a client-server architecture optimized for local network deployment.
Server Configuration
• Windows operating system (Windows 7 or later)
• XAMPP integration package
• Apache web server
• PHP application framework
• MySQL/MariaDB database engine
• Application files and backup storage
Client Configuration
• Desktop computers or laptops connected to the barangay LAN
• Modern web browser (Chrome, Firefox, Edge, or equivalent)
• No additional software installation required on client computers
Access Method
Users will access the application through a local uniform resource locator (URL) using the configured server IP address. Example:
http://192.168.1.10/barangay-locator/
The final IP address and URL will be determined based on the actual barangay network configuration and assigned during deployment.
The system is designed exclusively for private local network use and will not require public web hosting, domain registration, or cPanel-based deployment.
Mapping Dependency
The interactive map will use external map tiles and requires an internet connection for optimal functionality. Offline mapping capabilities may be evaluated as an additional requirement if continuous map availability independent of internet connectivity is required.
## 5. Project Cost Breakdown
| Component | Amount |
| --- | --- |
| Requirements Analysis and System Design | ₱15,000.00 |
| User Interface and Responsive Web Design | ₱20,000.00 |
| PHP and MySQL Application Development | ₱45,000.00 |
| Interactive Map and Establishment Locator | ₱30,000.00 |
| Establishment Search and Category Filtering | ₱15,000.00 |
| Administrator Dashboard and User Management | ₱20,000.00 |
| Establishment Verification and Approval Workflow | ₱15,000.00 |
| Security Implementation and Quality Assurance | ₱15,000.00 |
| XAMPP Installation and Local Database Configuration | ₱10,000.00 |
| User Manual, Technical Documentation, and Training | ₱10,000.00 |
| TOTAL PROJECT COST | ₱200,000.00 |
Notes: The amounts listed constitute the complete project budget. Hardware acquisition, extensive data encoding, third-party services, and other items not explicitly included in the approved scope are excluded unless separately authorized in writing. Any change orders or additional services outside this scope will be quoted and invoiced separately.
## 6. Conclusion
The Barangay Establishment Locator System represents a practical, localized solution to provide barangay staff with centralized access to establishment information through an interactive, searchable digital interface. The system's client-server architecture will enable multiple authorized users to access a shared application and database from computers connected to the barangay local area network, eliminating the need for public web hosting or complex infrastructure.
With a proposed project investment of ₱200,000.00 and a four-week implementation timeline, this project will deliver development, local deployment, comprehensive testing, documentation, and staff training services within the agreed scope.
Formal approval of this proposal will authorize the parties to finalize the detailed requirements, payment terms, project schedule, and specific acceptance criteria before application development commences.