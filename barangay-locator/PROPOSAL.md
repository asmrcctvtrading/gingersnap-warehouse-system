# BARANGAY ESTABLISHMENT LOCATOR SYSTEM

## Project Proposal

**Local Network-Based Application**

---

**Proposed Project Cost:** ₱200,000.00  
**Project Duration:** Four (4) weeks  
**Deployment Environment:** Windows PC with XAMPP  
**Network Access:** Barangay Local Area Network (LAN)  
**Technology Stack:** PHP, MySQL/MariaDB, JavaScript, Leaflet, HTML5, and CSS3

---

## 1. Executive Summary

The Barangay Establishment Locator System is a web-based application designed to centralize, organize, and locate establishments within the barangay. The system will provide an interactive digital map and a searchable directory of establishments, enabling authorized barangay personnel to access and manage establishment information efficiently.

The application will operate on a dedicated local server running XAMPP. Multiple computers connected to the barangay's local area network will access the application through a standard web browser, eliminating the need for separate client installations.

This solution will improve information accessibility, enhance record management capabilities, streamline establishment identification, and increase administrative efficiency while maintaining full control over locally stored data.

---

## 2. Project Objectives

The project will achieve the following objectives:

1. Develop a centralized digital directory of all establishments within the barangay.
2. Provide an interactive map displaying establishment locations.
3. Enable users to search and filter establishments by name, category, address, and designated area.
4. Furnish authorized personnel with comprehensive tools to create, update, verify, and manage establishment records.
5. Support simultaneous access from multiple computers connected to the barangay LAN.
6. Protect establishment information through authentication, role-based access controls, and regular backups.
7. Reduce dependence on manually maintained directories and paper-based location records.

---

## 3. Project Scope and Features

### 3.1 Interactive Establishment Map

- Display establishment locations using map markers on an interactive interface.
- Present establishment names, categories, addresses, and available contact information when markers are selected.
- Enable map navigation, zooming, and location exploration.
- Display establishment profiles directly from marker interactions.

### 3.2 Establishment Directory and Search

- Search establishments by name, category, and location.
- Filter and view establishment profiles, business hours, contact details, and addresses.
- Display associated establishment images and precise location coordinates.
- Support advanced filtering by multiple criteria simultaneously.

### 3.3 Establishment Record Management

- Create and add new establishment records to the system.
- Edit and update existing establishment information.
- Deactivate outdated or permanently closed establishment records.
- Upload and manage establishment images with automatic file validation.
- Track and record verification status and approval information.

### 3.4 Administrator Dashboard

- Manage all establishment records from a centralized interface.
- Review submitted or newly encoded establishment information.
- Approve or reject records in accordance with assigned permissions.
- View summary statistics, analytics, and administrative reports.
- Manage authorized user accounts and assign role-based access permissions.

### 3.5 User Access and Security

The application will support role-based access control with the following user roles, subject to final client approval:

- **Administrator:** Manage system users, application settings, and all establishment records.
- **Encoder:** Create and update records within assigned departmental or functional permissions.
- **Viewer:** Search and view approved establishment information only.

Security measures will include the following:

- Password hashing using industry-standard algorithms.
- Prepared SQL statements to prevent injection attacks.
- Comprehensive input validation and sanitization.
- Secure session handling with appropriate timeout mechanisms.
- Cross-Site Request Forgery (CSRF) protection.
- Restrictions on unauthorized record modifications.

### 3.6 Local Network Access

- Install the application on a dedicated Windows server computer within the barangay office.
- Utilize XAMPP to run the Apache web server, PHP runtime, and MySQL/MariaDB database engine.
- Configure the system to allow authorized client computers on the barangay LAN to access the application through a web browser.
- Maintain a single centralized database to ensure information consistency across all users.
- Configure Windows Firewall rules to restrict unauthorized access appropriately.
- Restrict access to database administration tools and database ports to authorized server-side operations only.

### 3.7 Backup and Recovery

- Provide a documented database backup procedure suitable for routine use.
- Document comprehensive database restoration procedures for recovery scenarios.
- Recommend scheduled backups to external storage devices or other approved backup destinations.
- Supply step-by-step recovery instructions for use following server failure or data loss.

---

## 4. Technical Architecture

The application will employ a client-server architecture optimized for local network deployment.

**Server Configuration**

- Windows operating system (Windows 7 or later)
- XAMPP integration package
- Apache web server
- PHP application framework
- MySQL/MariaDB database engine
- Application files and backup storage

**Client Configuration**

- Desktop computers or laptops connected to the barangay LAN
- Modern web browser (Chrome, Firefox, Edge, or equivalent)
- No additional software installation required on client computers

**Access Method**

Users will access the application through a local uniform resource locator (URL) using the configured server IP address. Example:

```
http://192.168.1.10/barangay-locator/
```

The final IP address and URL will be determined based on the actual barangay network configuration and assigned during deployment.

The system is designed exclusively for private local network use and will not require public web hosting, domain registration, or cPanel-based deployment.

**Mapping Dependency**

The interactive map will use external map tiles and requires an internet connection for optimal functionality. Offline mapping capabilities may be evaluated as an additional requirement if continuous map availability independent of internet connectivity is required.

---

## 5. Project Cost Breakdown

| Component | Amount |
|---|---:|
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
| **TOTAL PROJECT COST** | **₱200,000.00** |

**Notes:** The amounts listed constitute the complete project budget. Hardware acquisition, extensive data encoding, third-party services, and other items not explicitly included in the approved scope are excluded unless separately authorized in writing. Any change orders or additional services outside this scope will be quoted and invoiced separately.

---

## 6. Project Implementation Schedule

The project will be completed within four (4) weeks, equivalent to 28 calendar days, according to the following implementation schedule:

| Period | Major Activities | Expected Deliverables |
|---|---|---|
| Week 1 | Requirements confirmation, LAN assessment, database schema design, user interface mockups, and development environment configuration | Approved requirements documentation, confirmed system design, and ready development environment |
| Week 2 | Core application development, establishment directory implementation, search functionality, database integration, and interactive map development | Functional core application with all primary features |
| Week 3 | Administrator dashboard development, user role implementation, verification workflow, security controls integration, and feature integration | Feature-complete application ready for testing |
| Week 4 | Functional testing, multi-computer LAN testing, bug resolution, backup configuration, documentation finalization, staff training, and formal handover | Tested and deployed application ready for production use |

**Schedule Dependencies:** This timeline assumes timely provision of the server computer, LAN configuration access, required establishment data, and responsive stakeholder feedback. Significant changes to the approved project scope may affect the delivery date and will be communicated in advance.

---

## 7. Payment Terms and Milestones

The proposed payment schedule is structured according to the following project milestones:

| Milestone | Percentage | Amount |
|---|---:|---:|
| Project approval and commencement | 40% | ₱80,000.00 |
| Completion of core application features | 40% | ₱80,000.00 |
| Final acceptance and handover | 20% | ₱40,000.00 |
| **Total** | **100%** | **₱200,000.00** |

**Payment Conditions:** All payments will be subject to the final written agreement and achievement of documented milestone acceptance criteria as mutually agreed upon.

---

## 8. Testing and Acceptance Criteria

The system will be deemed ready for client acceptance upon successful demonstration of the following acceptance criteria:

1. The application launches successfully on the designated XAMPP server without errors.
2. Authorized computers successfully access the application through the barangay LAN via the configured local URL.
3. Establishment records can be created, searched, viewed, updated, and deactivated in accordance with assigned user permissions.
4. The interactive map accurately displays establishment markers with associated information.
5. Administrator authentication and role-based access restrictions function correctly and prevent unauthorized operations.
6. Changes made by one authorized user are immediately reflected and visible to all other concurrent users.
7. Security controls effectively prevent unauthorized access to protected functions and sensitive data.
8. Database backup and restoration procedures are documented, tested, and verified to function correctly.
9. Comprehensive user documentation and administrator orientation training are provided.

**Acceptance Testing:** All acceptance testing will be formally documented. Any identified defects within the approved project scope will be corrected according to the agreed-upon terms and timelines.

---

## 9. Warranty and Maintenance

A 60-day warranty period will be provided, commencing upon formal client acceptance of the delivered system. The warranty will cover reproducible defects in the delivered functionality as implemented within the approved project scope.

**Warranty Exclusions**

The warranty does not cover the following:

- New features or modifications to approved project requirements.
- Hardware failures or operating system issues outside the application's control.
- Network equipment failures or changes to the barangay LAN configuration.
- Unauthorized modifications to application files or database structures.
- Third-party service interruptions or failures.
- Issues arising from user error or misuse of the system.

**Post-Warranty Support**

Ongoing maintenance, additional training sessions, and future system enhancements may be provided under a separate service agreement to be negotiated after delivery.

---

## 10. Assumptions and Exclusions

**Project Assumptions**

This proposal is based on the following assumptions:

- The barangay will provide a suitable Windows computer to serve as the dedicated local application server.
- The barangay will provide a functioning local area network with router/switch infrastructure and will grant authorized access to required network configuration settings.
- The client will provide establishment data, categories, and initial record requirements, or will collaborate with the development team to compile this information.
- The system will be used exclusively within the barangay's private local network.
- The client understands and accepts that this is a local network solution and does not include public internet hosting or cPanel-based deployment.

**Scope Exclusions**

The following items are excluded from this proposal and will require separate authorization and pricing if required:

- Offline map tiles and offline mapping capabilities.
- Specialized geographic information system (GIS) datasets or advanced mapping features.
- Hardware procurement or server computer purchase.
- Large-scale establishment data collection or data entry services.
- Integration with third-party external systems or APIs.
- Public internet exposure or cloud-based deployment.
- Applicable taxes and government fees (to be confirmed before final agreement execution).

---

## 11. Expected Benefits

Implementation of the Barangay Establishment Locator System is expected to deliver the following benefits to the barangay:

- **Centralized Information:** Establishment information is consolidated in a single, searchable digital database.
- **Improved Accessibility:** Authorized staff can quickly identify establishment locations and details without manual searching.
- **Enhanced Data Consistency:** Information remains consistent and up-to-date across all authorized user workstations.
- **Improved Administrative Control:** Verification workflows and role-based permissions provide robust oversight and quality assurance.
- **Reduced Manual Processes:** Replacement of manually maintained directories and paper-based location records.
- **Local Data Ownership:** Complete control over application data, backups, and access without dependence on external hosting services.
- **Foundation for Growth:** Documented system architecture supports future enhancements and additional functionality as needs evolve.

---

## 12. Conclusion

The Barangay Establishment Locator System represents a practical, localized solution to provide barangay staff with centralized access to establishment information through an interactive, searchable digital interface. The system's client-server architecture will enable multiple authorized users to access a shared application and database from computers connected to the barangay local area network, eliminating the need for public web hosting or complex infrastructure.

With a proposed project investment of **₱200,000.00** and a four-week implementation timeline, this project will deliver development, local deployment, comprehensive testing, documentation, and staff training services within the agreed scope.

Formal approval of this proposal will authorize the parties to finalize the detailed requirements, payment terms, project schedule, and specific acceptance criteria before application development commences.

---

## Approval and Acceptance

| Role | Name | Signature | Date |
|---|---|---|---|
| Client Representative | _____________________ | _____________________ | _____________________ |
| Project Manager | _____________________ | _____________________ | _____________________ |

---

**Document Control**

- **Version:** 1.0
- **Prepared by:** [Developer Name/Organization]
- **Date Prepared:** [Date]
- **Last Updated:** [Date]
- **Status:** Ready for Client Presentation

