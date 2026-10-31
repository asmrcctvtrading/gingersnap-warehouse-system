const { Document, Packer, Paragraph, Table, TableRow, TableCell, TextRun, HeadingLevel, WidthType, BorderStyle, ShadingType, PageBreak, AlignmentType, PositionalTab, PositionalTabAlignment, PositionalTabLeader, UnderlineType } = require('docx');
const fs = require('fs');

const doc = new Document({
  page: {
    size: { width: 12240, height: 15840 } // US Letter
  },
  sections: [{
    properties: {},
    children: [
      // Title
      new Paragraph({
        text: 'BARANGAY ESTABLISHMENT LOCATOR SYSTEM',
        heading: HeadingLevel.HEADING_1,
        alignment: AlignmentType.CENTER,
        spacing: { after: 0 }
      }),
      new Paragraph({
        text: 'Project Proposal',
        heading: HeadingLevel.HEADING_2,
        alignment: AlignmentType.CENTER,
        spacing: { after: 200 }
      }),

      // Subtitle
      new Paragraph({
        text: 'Local Network-Based Application',
        alignment: AlignmentType.CENTER,
        spacing: { after: 400 }
      }),

      // Key metrics
      new Paragraph({
        text: 'Proposed Project Cost: ',
        bold: true,
        children: [new TextRun('₱200,000.00')]
      }),
      new Paragraph({
        text: 'Project Duration: ',
        bold: true,
        children: [new TextRun('Four (4) weeks')]
      }),
      new Paragraph({
        text: 'Deployment Environment: ',
        bold: true,
        children: [new TextRun('Windows PC with XAMPP')]
      }),
      new Paragraph({
        text: 'Network Access: ',
        bold: true,
        children: [new TextRun('Barangay Local Area Network (LAN)')]
      }),
      new Paragraph({
        text: 'Technology Stack: ',
        bold: true,
        children: [new TextRun('PHP, MySQL/MariaDB, JavaScript, Leaflet, HTML5, and CSS3')],
        spacing: { after: 400 }
      }),

      // Executive Summary
      new Paragraph({
        text: '1. Executive Summary',
        heading: HeadingLevel.HEADING_2,
        spacing: { after: 200 }
      }),
      new Paragraph({
        text: 'The Barangay Establishment Locator System is a web-based application designed to centralize, organize, and locate establishments within the barangay. The system will provide an interactive digital map and a searchable directory of establishments, enabling authorized barangay personnel to access and manage establishment information efficiently.',
        spacing: { after: 200 }
      }),
      new Paragraph({
        text: 'The application will operate on a dedicated local server running XAMPP. Multiple computers connected to the barangay\'s local area network will access the application through a standard web browser, eliminating the need for separate client installations.',
        spacing: { after: 200 }
      }),
      new Paragraph({
        text: 'This solution will improve information accessibility, enhance record management capabilities, streamline establishment identification, and increase administrative efficiency while maintaining full control over locally stored data.',
        spacing: { after: 400 }
      }),

      // Project Objectives
      new Paragraph({
        text: '2. Project Objectives',
        heading: HeadingLevel.HEADING_2,
        spacing: { after: 200 }
      }),
      new Paragraph({
        text: 'The project will achieve the following objectives:',
        spacing: { after: 200 }
      }),
      ...createNumberedList([
        'Develop a centralized digital directory of all establishments within the barangay.',
        'Provide an interactive map displaying establishment locations.',
        'Enable users to search and filter establishments by name, category, address, and designated area.',
        'Furnish authorized personnel with comprehensive tools to create, update, verify, and manage establishment records.',
        'Support simultaneous access from multiple computers connected to the barangay LAN.',
        'Protect establishment information through authentication, role-based access controls, and regular backups.',
        'Reduce dependence on manually maintained directories and paper-based location records.'
      ]),
      new Paragraph({
        text: '',
        spacing: { after: 200 }
      }),

      // Project Scope
      new Paragraph({
        text: '3. Project Scope and Features',
        heading: HeadingLevel.HEADING_2,
        spacing: { after: 200 }
      }),

      new Paragraph({
        text: '3.1 Interactive Establishment Map',
        heading: HeadingLevel.HEADING_3,
        spacing: { after: 100 }
      }),
      ...createBulletList([
        'Display establishment locations using map markers on an interactive interface.',
        'Present establishment names, categories, addresses, and available contact information when markers are selected.',
        'Enable map navigation, zooming, and location exploration.',
        'Display establishment profiles directly from marker interactions.'
      ]),

      new Paragraph({
        text: '3.2 Establishment Directory and Search',
        heading: HeadingLevel.HEADING_3,
        spacing: { after: 100 }
      }),
      ...createBulletList([
        'Search establishments by name, category, and location.',
        'Filter and view establishment profiles, business hours, contact details, and addresses.',
        'Display associated establishment images and precise location coordinates.',
        'Support advanced filtering by multiple criteria simultaneously.'
      ]),

      new Paragraph({
        text: '3.3 Establishment Record Management',
        heading: HeadingLevel.HEADING_3,
        spacing: { after: 100 }
      }),
      ...createBulletList([
        'Create and add new establishment records to the system.',
        'Edit and update existing establishment information.',
        'Deactivate outdated or permanently closed establishment records.',
        'Upload and manage establishment images with automatic file validation.',
        'Track and record verification status and approval information.'
      ]),

      new Paragraph({
        text: '3.4 Administrator Dashboard',
        heading: HeadingLevel.HEADING_3,
        spacing: { after: 100 }
      }),
      ...createBulletList([
        'Manage all establishment records from a centralized interface.',
        'Review submitted or newly encoded establishment information.',
        'Approve or reject records in accordance with assigned permissions.',
        'View summary statistics, analytics, and administrative reports.',
        'Manage authorized user accounts and assign role-based access permissions.'
      ]),

      new Paragraph({
        text: '3.5 User Access and Security',
        heading: HeadingLevel.HEADING_3,
        spacing: { after: 100 }
      }),
      new Paragraph({
        text: 'The application will support role-based access control with the following user roles, subject to final client approval:',
        spacing: { after: 100 }
      }),
      ...createBulletList([
        'Administrator: Manage system users, application settings, and all establishment records.',
        'Encoder: Create and update records within assigned departmental or functional permissions.',
        'Viewer: Search and view approved establishment information only.'
      ]),
      new Paragraph({
        text: 'Security measures will include the following:',
        spacing: { after: 100 }
      }),
      ...createBulletList([
        'Password hashing using industry-standard algorithms.',
        'Prepared SQL statements to prevent injection attacks.',
        'Comprehensive input validation and sanitization.',
        'Secure session handling with appropriate timeout mechanisms.',
        'Cross-Site Request Forgery (CSRF) protection.',
        'Restrictions on unauthorized record modifications.'
      ]),

      new Paragraph({
        text: '3.6 Local Network Access',
        heading: HeadingLevel.HEADING_3,
        spacing: { after: 100 }
      }),
      ...createBulletList([
        'Install the application on a dedicated Windows server computer within the barangay office.',
        'Utilize XAMPP to run the Apache web server, PHP runtime, and MySQL/MariaDB database engine.',
        'Configure the system to allow authorized client computers on the barangay LAN to access the application through a web browser.',
        'Maintain a single centralized database to ensure information consistency across all users.',
        'Configure Windows Firewall rules to restrict unauthorized access appropriately.',
        'Restrict access to database administration tools and database ports to authorized server-side operations only.'
      ]),

      new Paragraph({
        text: '3.7 Backup and Recovery',
        heading: HeadingLevel.HEADING_3,
        spacing: { after: 100 }
      }),
      ...createBulletList([
        'Provide a documented database backup procedure suitable for routine use.',
        'Document comprehensive database restoration procedures for recovery scenarios.',
        'Recommend scheduled backups to external storage devices or other approved backup destinations.',
        'Supply step-by-step recovery instructions for use following server failure or data loss.'
      ]),
      new Paragraph({
        text: '',
        spacing: { after: 200 }
      }),

      // Technical Architecture
      new Paragraph({
        text: '4. Technical Architecture',
        heading: HeadingLevel.HEADING_2,
        spacing: { after: 200 }
      }),
      new Paragraph({
        text: 'The application will employ a client-server architecture optimized for local network deployment.',
        spacing: { after: 200 }
      }),

      new Paragraph({
        text: 'Server Configuration',
        bold: true,
        spacing: { after: 100 }
      }),
      ...createBulletList([
        'Windows operating system (Windows 7 or later)',
        'XAMPP integration package',
        'Apache web server',
        'PHP application framework',
        'MySQL/MariaDB database engine',
        'Application files and backup storage'
      ]),

      new Paragraph({
        text: 'Client Configuration',
        bold: true,
        spacing: { before: 100, after: 100 }
      }),
      ...createBulletList([
        'Desktop computers or laptops connected to the barangay LAN',
        'Modern web browser (Chrome, Firefox, Edge, or equivalent)',
        'No additional software installation required on client computers'
      ]),

      new Paragraph({
        text: 'Access Method',
        bold: true,
        spacing: { before: 100, after: 100 }
      }),
      new Paragraph({
        text: 'Users will access the application through a local uniform resource locator (URL) using the configured server IP address. Example:',
        spacing: { after: 100 }
      }),
      new Paragraph({
        text: 'http://192.168.1.10/barangay-locator/',
        spacing: { after: 200 }
      }),
      new Paragraph({
        text: 'The final IP address and URL will be determined based on the actual barangay network configuration and assigned during deployment.',
        spacing: { after: 200 }
      }),
      new Paragraph({
        text: 'The system is designed exclusively for private local network use and will not require public web hosting, domain registration, or cPanel-based deployment.',
        spacing: { after: 200 }
      }),

      new Paragraph({
        text: 'Mapping Dependency',
        bold: true,
        spacing: { after: 100 }
      }),
      new Paragraph({
        text: 'The interactive map will use external map tiles and requires an internet connection for optimal functionality. Offline mapping capabilities may be evaluated as an additional requirement if continuous map availability independent of internet connectivity is required.',
        spacing: { after: 400 }
      }),

      // Cost Breakdown
      new Paragraph({
        text: '5. Project Cost Breakdown',
        heading: HeadingLevel.HEADING_2,
        spacing: { after: 200 }
      }),
      createCostTable(),
      new Paragraph({
        text: 'Notes: The amounts listed constitute the complete project budget. Hardware acquisition, extensive data encoding, third-party services, and other items not explicitly included in the approved scope are excluded unless separately authorized in writing. Any change orders or additional services outside this scope will be quoted and invoiced separately.',
        spacing: { after: 400 },
        italics: true
      }),

      // Implementation Schedule
      new Paragraph({
        text: '6. Project Implementation Schedule',
        heading: HeadingLevel.HEADING_2,
        spacing: { after: 200 }
      }),
      new Paragraph({
        text: 'The project will be completed within four (4) weeks, equivalent to 28 calendar days, according to the following implementation schedule:',
        spacing: { after: 200 }
      }),
      createScheduleTable(),
      new Paragraph({
        text: 'Schedule Dependencies: This timeline assumes timely provision of the server computer, LAN configuration access, required establishment data, and responsive stakeholder feedback. Significant changes to the approved project scope may affect the delivery date and will be communicated in advance.',
        spacing: { after: 400 },
        italics: true
      }),

      // Payment Terms
      new Paragraph({
        text: '7. Payment Terms and Milestones',
        heading: HeadingLevel.HEADING_2,
        spacing: { after: 200 }
      }),
      new Paragraph({
        text: 'The proposed payment schedule is structured according to the following project milestones:',
        spacing: { after: 200 }
      }),
      createPaymentTable(),
      new Paragraph({
        text: 'Payment Conditions: All payments will be subject to the final written agreement and achievement of documented milestone acceptance criteria as mutually agreed upon.',
        spacing: { after: 400 },
        italics: true
      }),

      // Testing and Acceptance
      new Paragraph({
        text: '8. Testing and Acceptance Criteria',
        heading: HeadingLevel.HEADING_2,
        spacing: { after: 200 }
      }),
      new Paragraph({
        text: 'The system will be deemed ready for client acceptance upon successful demonstration of the following acceptance criteria:',
        spacing: { after: 200 }
      }),
      ...createNumberedList([
        'The application launches successfully on the designated XAMPP server without errors.',
        'Authorized computers successfully access the application through the barangay LAN via the configured local URL.',
        'Establishment records can be created, searched, viewed, updated, and deactivated in accordance with assigned user permissions.',
        'The interactive map accurately displays establishment markers with associated information.',
        'Administrator authentication and role-based access restrictions function correctly and prevent unauthorized operations.',
        'Changes made by one authorized user are immediately reflected and visible to all other concurrent users.',
        'Security controls effectively prevent unauthorized access to protected functions and sensitive data.',
        'Database backup and restoration procedures are documented, tested, and verified to function correctly.',
        'Comprehensive user documentation and administrator orientation training are provided.'
      ]),
      new Paragraph({
        text: 'Acceptance Testing: All acceptance testing will be formally documented. Any identified defects within the approved project scope will be corrected according to the agreed-upon terms and timelines.',
        spacing: { after: 400 },
        italics: true
      }),

      // Warranty
      new Paragraph({
        text: '9. Warranty and Maintenance',
        heading: HeadingLevel.HEADING_2,
        spacing: { after: 200 }
      }),
      new Paragraph({
        text: 'A 60-day warranty period will be provided, commencing upon formal client acceptance of the delivered system. The warranty will cover reproducible defects in the delivered functionality as implemented within the approved project scope.',
        spacing: { after: 200 }
      }),

      new Paragraph({
        text: 'Warranty Exclusions',
        bold: true,
        spacing: { after: 100 }
      }),
      new Paragraph({
        text: 'The warranty does not cover the following:',
        spacing: { after: 100 }
      }),
      ...createBulletList([
        'New features or modifications to approved project requirements.',
        'Hardware failures or operating system issues outside the application\'s control.',
        'Network equipment failures or changes to the barangay LAN configuration.',
        'Unauthorized modifications to application files or database structures.',
        'Third-party service interruptions or failures.',
        'Issues arising from user error or misuse of the system.'
      ]),

      new Paragraph({
        text: 'Post-Warranty Support',
        bold: true,
        spacing: { before: 100, after: 100 }
      }),
      new Paragraph({
        text: 'Ongoing maintenance, additional training sessions, and future system enhancements may be provided under a separate service agreement to be negotiated after delivery.',
        spacing: { after: 400 }
      }),

      // Assumptions
      new Paragraph({
        text: '10. Assumptions and Exclusions',
        heading: HeadingLevel.HEADING_2,
        spacing: { after: 200 }
      }),

      new Paragraph({
        text: 'Project Assumptions',
        bold: true,
        spacing: { after: 100 }
      }),
      new Paragraph({
        text: 'This proposal is based on the following assumptions:',
        spacing: { after: 100 }
      }),
      ...createBulletList([
        'The barangay will provide a suitable Windows computer to serve as the dedicated local application server.',
        'The barangay will provide a functioning local area network with router/switch infrastructure and will grant authorized access to required network configuration settings.',
        'The client will provide establishment data, categories, and initial record requirements, or will collaborate with the development team to compile this information.',
        'The system will be used exclusively within the barangay\'s private local network.',
        'The client understands and accepts that this is a local network solution and does not include public internet hosting or cPanel-based deployment.'
      ]),

      new Paragraph({
        text: 'Scope Exclusions',
        bold: true,
        spacing: { before: 100, after: 100 }
      }),
      new Paragraph({
        text: 'The following items are excluded from this proposal and will require separate authorization and pricing if required:',
        spacing: { after: 100 }
      }),
      ...createBulletList([
        'Offline map tiles and offline mapping capabilities.',
        'Specialized geographic information system (GIS) datasets or advanced mapping features.',
        'Hardware procurement or server computer purchase.',
        'Large-scale establishment data collection or data entry services.',
        'Integration with third-party external systems or APIs.',
        'Public internet exposure or cloud-based deployment.',
        'Applicable taxes and government fees (to be confirmed before final agreement execution).'
      ]),
      new Paragraph({
        text: '',
        spacing: { after: 200 }
      }),

      // Benefits
      new Paragraph({
        text: '11. Expected Benefits',
        heading: HeadingLevel.HEADING_2,
        spacing: { after: 200 }
      }),
      new Paragraph({
        text: 'Implementation of the Barangay Establishment Locator System is expected to deliver the following benefits to the barangay:',
        spacing: { after: 200 }
      }),
      ...createBulletList([
        'Centralized Information: Establishment information is consolidated in a single, searchable digital database.',
        'Improved Accessibility: Authorized staff can quickly identify establishment locations and details without manual searching.',
        'Enhanced Data Consistency: Information remains consistent and up-to-date across all authorized user workstations.',
        'Improved Administrative Control: Verification workflows and role-based permissions provide robust oversight and quality assurance.',
        'Reduced Manual Processes: Replacement of manually maintained directories and paper-based location records.',
        'Local Data Ownership: Complete control over application data, backups, and access without dependence on external hosting services.',
        'Foundation for Growth: Documented system architecture supports future enhancements and additional functionality as needs evolve.'
      ]),
      new Paragraph({
        text: '',
        spacing: { after: 200 }
      }),

      // Conclusion
      new Paragraph({
        text: '12. Conclusion',
        heading: HeadingLevel.HEADING_2,
        spacing: { after: 200 }
      }),
      new Paragraph({
        text: 'The Barangay Establishment Locator System represents a practical, localized solution to provide barangay staff with centralized access to establishment information through an interactive, searchable digital interface. The system\'s client-server architecture will enable multiple authorized users to access a shared application and database from computers connected to the barangay local area network, eliminating the need for public web hosting or complex infrastructure.',
        spacing: { after: 200 }
      }),
      new Paragraph({
        text: 'With a proposed project investment of ₱200,000.00 and a four-week implementation timeline, this project will deliver development, local deployment, comprehensive testing, documentation, and staff training services within the agreed scope.',
        spacing: { after: 200 }
      }),
      new Paragraph({
        text: 'Formal approval of this proposal will authorize the parties to finalize the detailed requirements, payment terms, project schedule, and specific acceptance criteria before application development commences.',
        spacing: { after: 400 }
      }),

      // Approval Section
      new Paragraph({ text: '', spacing: { after: 200 } }),
      new Paragraph({
        text: 'Approval and Acceptance',
        heading: HeadingLevel.HEADING_2,
        spacing: { after: 200 }
      }),
      createApprovalTable(),

      new Paragraph({ text: '', spacing: { after: 200 } }),
      new Paragraph({
        text: 'Document Control',
        bold: true,
        spacing: { after: 100 }
      }),
      ...createBulletList([
        'Version: 1.0',
        'Prepared by: [Developer Name/Organization]',
        'Date Prepared: [Date]',
        'Last Updated: [Date]',
        'Status: Ready for Client Presentation'
      ])
    ]
  }]
});

function createBulletList(items) {
  return items.map((item, idx) => new Paragraph({
    text: item,
    spacing: { after: 100 },
    bullet: { level: 0 }
  }));
}

function createNumberedList(items) {
  return items.map((item, idx) => new Paragraph({
    text: `${idx + 1}. ${item}`,
    spacing: { after: 100 }
  }));
}

function createCostTable() {
  return new Table({
    width: { size: 100, type: WidthType.PERCENTAGE },
    rows: [
      new TableRow({
        cells: [
          new TableCell({
            children: [new Paragraph({ text: 'Component', bold: true })],
            shading: { fill: 'D3D3D3', type: ShadingType.CLEAR },
            width: { size: 70, type: WidthType.PERCENTAGE }
          }),
          new TableCell({
            children: [new Paragraph({ text: 'Amount', bold: true, alignment: AlignmentType.RIGHT })],
            shading: { fill: 'D3D3D3', type: ShadingType.CLEAR },
            width: { size: 30, type: WidthType.PERCENTAGE }
          })
        ]
      }),
      ...createCostRows(),
      new TableRow({
        cells: [
          new TableCell({
            children: [new Paragraph({ text: 'TOTAL PROJECT COST', bold: true })],
            shading: { fill: 'E8E8E8', type: ShadingType.CLEAR },
            width: { size: 70, type: WidthType.PERCENTAGE }
          }),
          new TableCell({
            children: [new Paragraph({ text: '₱200,000.00', bold: true, alignment: AlignmentType.RIGHT })],
            shading: { fill: 'E8E8E8', type: ShadingType.CLEAR },
            width: { size: 30, type: WidthType.PERCENTAGE }
          })
        ]
      })
    ]
  });
}

function createCostRows() {
  const costs = [
    ['Requirements Analysis and System Design', '₱15,000.00'],
    ['User Interface and Responsive Web Design', '₱20,000.00'],
    ['PHP and MySQL Application Development', '₱45,000.00'],
    ['Interactive Map and Establishment Locator', '₱30,000.00'],
    ['Establishment Search and Category Filtering', '₱15,000.00'],
    ['Administrator Dashboard and User Management', '₱20,000.00'],
    ['Establishment Verification and Approval Workflow', '₱15,000.00'],
    ['Security Implementation and Quality Assurance', '₱15,000.00'],
    ['XAMPP Installation and Local Database Configuration', '₱10,000.00'],
    ['User Manual, Technical Documentation, and Training', '₱10,000.00']
  ];

  return costs.map(([comp, amt]) => new TableRow({
    cells: [
      new TableCell({
        children: [new Paragraph(comp)],
        width: { size: 70, type: WidthType.PERCENTAGE }
      }),
      new TableCell({
        children: [new Paragraph({ text: amt, alignment: AlignmentType.RIGHT })],
        width: { size: 30, type: WidthType.PERCENTAGE }
      })
    ]
  }));
}

function createScheduleTable() {
  return new Table({
    width: { size: 100, type: WidthType.PERCENTAGE },
    rows: [
      new TableRow({
        cells: [
          new TableCell({
            children: [new Paragraph({ text: 'Period', bold: true })],
            shading: { fill: 'D3D3D3', type: ShadingType.CLEAR },
            width: { size: 15, type: WidthType.PERCENTAGE }
          }),
          new TableCell({
            children: [new Paragraph({ text: 'Major Activities', bold: true })],
            shading: { fill: 'D3D3D3', type: ShadingType.CLEAR },
            width: { size: 42, type: WidthType.PERCENTAGE }
          }),
          new TableCell({
            children: [new Paragraph({ text: 'Expected Deliverables', bold: true })],
            shading: { fill: 'D3D3D3', type: ShadingType.CLEAR },
            width: { size: 43, type: WidthType.PERCENTAGE }
          })
        ]
      }),
      new TableRow({
        cells: [
          new TableCell({ children: [new Paragraph('Week 1')], width: { size: 15, type: WidthType.PERCENTAGE } }),
          new TableCell({ children: [new Paragraph('Requirements confirmation, LAN assessment, database schema design, user interface mockups, and development environment configuration')], width: { size: 42, type: WidthType.PERCENTAGE } }),
          new TableCell({ children: [new Paragraph('Approved requirements documentation, confirmed system design, and ready development environment')], width: { size: 43, type: WidthType.PERCENTAGE } })
        ]
      }),
      new TableRow({
        cells: [
          new TableCell({ children: [new Paragraph('Week 2')], width: { size: 15, type: WidthType.PERCENTAGE } }),
          new TableCell({ children: [new Paragraph('Core application development, establishment directory implementation, search functionality, database integration, and interactive map development')], width: { size: 42, type: WidthType.PERCENTAGE } }),
          new TableCell({ children: [new Paragraph('Functional core application with all primary features')], width: { size: 43, type: WidthType.PERCENTAGE } })
        ]
      }),
      new TableRow({
        cells: [
          new TableCell({ children: [new Paragraph('Week 3')], width: { size: 15, type: WidthType.PERCENTAGE } }),
          new TableCell({ children: [new Paragraph('Administrator dashboard development, user role implementation, verification workflow, security controls integration, and feature integration')], width: { size: 42, type: WidthType.PERCENTAGE } }),
          new TableCell({ children: [new Paragraph('Feature-complete application ready for testing')], width: { size: 43, type: WidthType.PERCENTAGE } })
        ]
      }),
      new TableRow({
        cells: [
          new TableCell({ children: [new Paragraph('Week 4')], width: { size: 15, type: WidthType.PERCENTAGE } }),
          new TableCell({ children: [new Paragraph('Functional testing, multi-computer LAN testing, bug resolution, backup configuration, documentation finalization, staff training, and formal handover')], width: { size: 42, type: WidthType.PERCENTAGE } }),
          new TableCell({ children: [new Paragraph('Tested and deployed application ready for production use')], width: { size: 43, type: WidthType.PERCENTAGE } })
        ]
      })
    ]
  });
}

function createPaymentTable() {
  return new Table({
    width: { size: 100, type: WidthType.PERCENTAGE },
    rows: [
      new TableRow({
        cells: [
          new TableCell({
            children: [new Paragraph({ text: 'Milestone', bold: true })],
            shading: { fill: 'D3D3D3', type: ShadingType.CLEAR },
            width: { size: 50, type: WidthType.PERCENTAGE }
          }),
          new TableCell({
            children: [new Paragraph({ text: 'Percentage', bold: true, alignment: AlignmentType.CENTER })],
            shading: { fill: 'D3D3D3', type: ShadingType.CLEAR },
            width: { size: 25, type: WidthType.PERCENTAGE }
          }),
          new TableCell({
            children: [new Paragraph({ text: 'Amount', bold: true, alignment: AlignmentType.RIGHT })],
            shading: { fill: 'D3D3D3', type: ShadingType.CLEAR },
            width: { size: 25, type: WidthType.PERCENTAGE }
          })
        ]
      }),
      new TableRow({
        cells: [
          new TableCell({ children: [new Paragraph('Project approval and commencement')], width: { size: 50, type: WidthType.PERCENTAGE } }),
          new TableCell({ children: [new Paragraph({ text: '40%', alignment: AlignmentType.CENTER })], width: { size: 25, type: WidthType.PERCENTAGE } }),
          new TableCell({ children: [new Paragraph({ text: '₱80,000.00', alignment: AlignmentType.RIGHT })], width: { size: 25, type: WidthType.PERCENTAGE } })
        ]
      }),
      new TableRow({
        cells: [
          new TableCell({ children: [new Paragraph('Completion of core application features')], width: { size: 50, type: WidthType.PERCENTAGE } }),
          new TableCell({ children: [new Paragraph({ text: '40%', alignment: AlignmentType.CENTER })], width: { size: 25, type: WidthType.PERCENTAGE } }),
          new TableCell({ children: [new Paragraph({ text: '₱80,000.00', alignment: AlignmentType.RIGHT })], width: { size: 25, type: WidthType.PERCENTAGE } })
        ]
      }),
      new TableRow({
        cells: [
          new TableCell({ children: [new Paragraph('Final acceptance and handover')], width: { size: 50, type: WidthType.PERCENTAGE } }),
          new TableCell({ children: [new Paragraph({ text: '20%', alignment: AlignmentType.CENTER })], width: { size: 25, type: WidthType.PERCENTAGE } }),
          new TableCell({ children: [new Paragraph({ text: '₱40,000.00', alignment: AlignmentType.RIGHT })], width: { size: 25, type: WidthType.PERCENTAGE } })
        ]
      }),
      new TableRow({
        cells: [
          new TableCell({
            children: [new Paragraph({ text: 'Total', bold: true })],
            shading: { fill: 'E8E8E8', type: ShadingType.CLEAR },
            width: { size: 50, type: WidthType.PERCENTAGE }
          }),
          new TableCell({
            children: [new Paragraph({ text: '100%', bold: true, alignment: AlignmentType.CENTER })],
            shading: { fill: 'E8E8E8', type: ShadingType.CLEAR },
            width: { size: 25, type: WidthType.PERCENTAGE }
          }),
          new TableCell({
            children: [new Paragraph({ text: '₱200,000.00', bold: true, alignment: AlignmentType.RIGHT })],
            shading: { fill: 'E8E8E8', type: ShadingType.CLEAR },
            width: { size: 25, type: WidthType.PERCENTAGE }
          })
        ]
      })
    ]
  });
}

function createApprovalTable() {
  return new Table({
    width: { size: 100, type: WidthType.PERCENTAGE },
    rows: [
      new TableRow({
        cells: [
          new TableCell({
            children: [new Paragraph({ text: 'Role', bold: true })],
            shading: { fill: 'D3D3D3', type: ShadingType.CLEAR }
          }),
          new TableCell({
            children: [new Paragraph({ text: 'Name', bold: true })],
            shading: { fill: 'D3D3D3', type: ShadingType.CLEAR }
          }),
          new TableCell({
            children: [new Paragraph({ text: 'Signature', bold: true })],
            shading: { fill: 'D3D3D3', type: ShadingType.CLEAR }
          }),
          new TableCell({
            children: [new Paragraph({ text: 'Date', bold: true })],
            shading: { fill: 'D3D3D3', type: ShadingType.CLEAR }
          })
        ]
      }),
      new TableRow({
        cells: [
          new TableCell({ children: [new Paragraph('Client Representative')] }),
          new TableCell({ children: [new Paragraph('_____________________')] }),
          new TableCell({ children: [new Paragraph('_____________________')] }),
          new TableCell({ children: [new Paragraph('_____________________')] })
        ]
      }),
      new TableRow({
        cells: [
          new TableCell({ children: [new Paragraph('Project Manager')] }),
          new TableCell({ children: [new Paragraph('_____________________')] }),
          new TableCell({ children: [new Paragraph('_____________________')] }),
          new TableCell({ children: [new Paragraph('_____________________')] })
        ]
      })
    ]
  });
}

Packer.toBuffer(doc).then(buffer => {
  fs.writeFileSync('C:\\Users\\Odyssey Jan Pascual\\.omniroute\\barangay-locator\\PROPOSAL.docx', buffer);
  console.log('Document created: PROPOSAL.docx');
});
