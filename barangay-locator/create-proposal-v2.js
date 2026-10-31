const { Document, Packer, Paragraph, Table, TableRow, TableCell, TextRun, HeadingLevel, WidthType, BorderStyle, ShadingType, AlignmentType } = require('docx');
const fs = require('fs');

const costData = [
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

const doc = new Document({
  sections: [{
    children: [
      // Title
      new Paragraph({
        text: 'BARANGAY ESTABLISHMENT LOCATOR SYSTEM',
        heading: HeadingLevel.HEADING_1,
        alignment: AlignmentType.CENTER,
        spacing: { after: 100 }
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
        text: 'Proposed Project Cost: ₱200,000.00',
        spacing: { after: 50 }
      }),
      new Paragraph({
        text: 'Project Duration: Four (4) weeks',
        spacing: { after: 50 }
      }),
      new Paragraph({
        text: 'Deployment Environment: Windows PC with XAMPP',
        spacing: { after: 50 }
      }),
      new Paragraph({
        text: 'Network Access: Barangay Local Area Network (LAN)',
        spacing: { after: 50 }
      }),
      new Paragraph({
        text: 'Technology Stack: PHP, MySQL/MariaDB, JavaScript, Leaflet, HTML5, and CSS3',
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
        spacing: { after: 100 }
      }),
      new Paragraph({ text: '1. Develop a centralized digital directory of all establishments within the barangay.', spacing: { after: 50 } }),
      new Paragraph({ text: '2. Provide an interactive map displaying establishment locations.', spacing: { after: 50 } }),
      new Paragraph({ text: '3. Enable users to search and filter establishments by name, category, address, and designated area.', spacing: { after: 50 } }),
      new Paragraph({ text: '4. Furnish authorized personnel with comprehensive tools to create, update, verify, and manage establishment records.', spacing: { after: 50 } }),
      new Paragraph({ text: '5. Support simultaneous access from multiple computers connected to the barangay LAN.', spacing: { after: 50 } }),
      new Paragraph({ text: '6. Protect establishment information through authentication, role-based access controls, and regular backups.', spacing: { after: 50 } }),
      new Paragraph({ text: '7. Reduce dependence on manually maintained directories and paper-based location records.', spacing: { after: 400 } }),

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
      new Paragraph({ text: '• Display establishment locations using map markers on an interactive interface.', spacing: { after: 50 } }),
      new Paragraph({ text: '• Present establishment names, categories, addresses, and available contact information when markers are selected.', spacing: { after: 50 } }),
      new Paragraph({ text: '• Enable map navigation, zooming, and location exploration.', spacing: { after: 50 } }),
      new Paragraph({ text: '• Display establishment profiles directly from marker interactions.', spacing: { after: 200 } }),

      new Paragraph({
        text: '3.2 Establishment Directory and Search',
        heading: HeadingLevel.HEADING_3,
        spacing: { after: 100 }
      }),
      new Paragraph({ text: '• Search establishments by name, category, and location.', spacing: { after: 50 } }),
      new Paragraph({ text: '• Filter and view establishment profiles, business hours, contact details, and addresses.', spacing: { after: 50 } }),
      new Paragraph({ text: '• Display associated establishment images and precise location coordinates.', spacing: { after: 50 } }),
      new Paragraph({ text: '• Support advanced filtering by multiple criteria simultaneously.', spacing: { after: 200 } }),

      new Paragraph({
        text: '3.3 Establishment Record Management',
        heading: HeadingLevel.HEADING_3,
        spacing: { after: 100 }
      }),
      new Paragraph({ text: '• Create and add new establishment records to the system.', spacing: { after: 50 } }),
      new Paragraph({ text: '• Edit and update existing establishment information.', spacing: { after: 50 } }),
      new Paragraph({ text: '• Deactivate outdated or permanently closed establishment records.', spacing: { after: 50 } }),
      new Paragraph({ text: '• Upload and manage establishment images with automatic file validation.', spacing: { after: 50 } }),
      new Paragraph({ text: '• Track and record verification status and approval information.', spacing: { after: 200 } }),

      new Paragraph({
        text: '3.4 Administrator Dashboard',
        heading: HeadingLevel.HEADING_3,
        spacing: { after: 100 }
      }),
      new Paragraph({ text: '• Manage all establishment records from a centralized interface.', spacing: { after: 50 } }),
      new Paragraph({ text: '• Review submitted or newly encoded establishment information.', spacing: { after: 50 } }),
      new Paragraph({ text: '• Approve or reject records in accordance with assigned permissions.', spacing: { after: 50 } }),
      new Paragraph({ text: '• View summary statistics, analytics, and administrative reports.', spacing: { after: 50 } }),
      new Paragraph({ text: '• Manage authorized user accounts and assign role-based access permissions.', spacing: { after: 200 } }),

      new Paragraph({
        text: '3.5 User Access and Security',
        heading: HeadingLevel.HEADING_3,
        spacing: { after: 100 }
      }),
      new Paragraph({
        text: 'The application will support role-based access control with the following user roles, subject to final client approval:',
        spacing: { after: 100 }
      }),
      new Paragraph({ text: '• Administrator: Manage system users, application settings, and all establishment records.', spacing: { after: 50 } }),
      new Paragraph({ text: '• Encoder: Create and update records within assigned departmental or functional permissions.', spacing: { after: 50 } }),
      new Paragraph({ text: '• Viewer: Search and view approved establishment information only.', spacing: { after: 200 } }),

      new Paragraph({
        text: 'Security measures will include the following:',
        spacing: { after: 100 }
      }),
      new Paragraph({ text: '• Password hashing using industry-standard algorithms.', spacing: { after: 50 } }),
      new Paragraph({ text: '• Prepared SQL statements to prevent injection attacks.', spacing: { after: 50 } }),
      new Paragraph({ text: '• Comprehensive input validation and sanitization.', spacing: { after: 50 } }),
      new Paragraph({ text: '• Secure session handling with appropriate timeout mechanisms.', spacing: { after: 50 } }),
      new Paragraph({ text: '• Cross-Site Request Forgery (CSRF) protection.', spacing: { after: 50 } }),
      new Paragraph({ text: '• Restrictions on unauthorized record modifications.', spacing: { after: 200 } }),

      new Paragraph({
        text: '3.6 Local Network Access',
        heading: HeadingLevel.HEADING_3,
        spacing: { after: 100 }
      }),
      new Paragraph({ text: '• Install the application on a dedicated Windows server computer within the barangay office.', spacing: { after: 50 } }),
      new Paragraph({ text: '• Utilize XAMPP to run the Apache web server, PHP runtime, and MySQL/MariaDB database engine.', spacing: { after: 50 } }),
      new Paragraph({ text: '• Configure the system to allow authorized client computers on the barangay LAN to access the application through a web browser.', spacing: { after: 50 } }),
      new Paragraph({ text: '• Maintain a single centralized database to ensure information consistency across all users.', spacing: { after: 50 } }),
      new Paragraph({ text: '• Configure Windows Firewall rules to restrict unauthorized access appropriately.', spacing: { after: 50 } }),
      new Paragraph({ text: '• Restrict access to database administration tools and database ports to authorized server-side operations only.', spacing: { after: 200 } }),

      new Paragraph({
        text: '3.7 Backup and Recovery',
        heading: HeadingLevel.HEADING_3,
        spacing: { after: 100 }
      }),
      new Paragraph({ text: '• Provide a documented database backup procedure suitable for routine use.', spacing: { after: 50 } }),
      new Paragraph({ text: '• Document comprehensive database restoration procedures for recovery scenarios.', spacing: { after: 50 } }),
      new Paragraph({ text: '• Recommend scheduled backups to external storage devices or other approved backup destinations.', spacing: { after: 50 } }),
      new Paragraph({ text: '• Supply step-by-step recovery instructions for use following server failure or data loss.', spacing: { after: 400 } }),

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
      new Paragraph({ text: '• Windows operating system (Windows 7 or later)', spacing: { after: 50 } }),
      new Paragraph({ text: '• XAMPP integration package', spacing: { after: 50 } }),
      new Paragraph({ text: '• Apache web server', spacing: { after: 50 } }),
      new Paragraph({ text: '• PHP application framework', spacing: { after: 50 } }),
      new Paragraph({ text: '• MySQL/MariaDB database engine', spacing: { after: 50 } }),
      new Paragraph({ text: '• Application files and backup storage', spacing: { after: 200 } }),

      new Paragraph({
        text: 'Client Configuration',
        bold: true,
        spacing: { after: 100 }
      }),
      new Paragraph({ text: '• Desktop computers or laptops connected to the barangay LAN', spacing: { after: 50 } }),
      new Paragraph({ text: '• Modern web browser (Chrome, Firefox, Edge, or equivalent)', spacing: { after: 50 } }),
      new Paragraph({ text: '• No additional software installation required on client computers', spacing: { after: 200 } }),

      new Paragraph({
        text: 'Access Method',
        bold: true,
        spacing: { after: 100 }
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

      // Conclusion
      new Paragraph({
        text: '6. Conclusion',
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
      })
    ]
  }]
});

function createCostTable() {
  const headerRow = new TableRow({
    children: [
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
  });

  const dataRows = costData.map(([component, amount]) =>
    new TableRow({
      children: [
        new TableCell({
          children: [new Paragraph(component)],
          width: { size: 70, type: WidthType.PERCENTAGE }
        }),
        new TableCell({
          children: [new Paragraph({ text: amount, alignment: AlignmentType.RIGHT })],
          width: { size: 30, type: WidthType.PERCENTAGE }
        })
      ]
    })
  );

  const totalRow = new TableRow({
    children: [
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
  });

  return new Table({
    width: { size: 100, type: WidthType.PERCENTAGE },
    rows: [headerRow, ...dataRows, totalRow]
  });
}

Packer.toBuffer(doc).then(buffer => {
  fs.writeFileSync('PROPOSAL.docx', buffer);
  console.log('Word document created successfully: PROPOSAL.docx');
});
