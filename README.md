# Makhanda Connect

A web-based information and issue-reporting platform that connects the citizens of Makhanda (Eastern Cape, South Africa) with their local municipality. Residents can log municipal service faults in their ward, ward councillors can manage and report on them, and the public can stay informed through a town-wide landing page.

> **Information Systems III (301 & 302), 2026: Systems Development Project**
> Department of Information Systems

---

## Table of Contents

- [Background](#background)
- [Features](#features)
- [User Roles](#user-roles)
- [Reports](#reports)
- [Business Rules](#business-rules)
- [Tech Stack](#tech-stack)
- [Getting Started](#getting-started)
- [Project Structure](#project-structure)
- [Team](#team)
- [Acknowledgements](#acknowledgements)

---

## Background

South Africa has very high mobile phone penetration (124-127 million active cellular connections), and the country ranks first in the world for daily mobile internet usage. Mobile phones are increasingly used for government and citizen engagement, especially at the local level.

Makhanda Connect aims to encourage two-way communication between the Makana local government and the residents of Makhanda by providing:

- A **public landing page** with town-wide information (e.g. widespread power or water problems).
- **Ward-level spaces** where community members log municipal service faults, and where ward councillors manage, track, and report on those faults for submission to the municipality.

## Features

**Public (no account required)**
- General landing page with town information, events, safety notices, local weather, and local dam levels
- Browse ward-specific subpages and ward summaries

**Registered community members and ward councillors**
- Create municipal service fault tickets (water, sanitation, electricity, road infrastructure, parks and recreation, etc.)
- Track the progress of tickets in their ward

**Ward councillors**
- Update ticket status: `Open` → `Reported` → `Resolved` (resolved tickets are closed)
- Add notes/comments to tickets in their ward
- Generate ward-level summaries that are publicly visible

**Municipal communications officers / local municipal managers**
- Add community-wide or ward-level notices
- Track tickets system-wide

**System administrators**
- Manage all user types and verify/assign roles for new accounts
- Manage content and approve or update community-wide notices

## User Roles

| Role | Description |
|------|-------------|
| Community Resident | Registers, logs faults, and tracks tickets for their ward |
| Ward Councillor | Manages tickets, adds notes, and publishes ward summaries for one ward |
| Municipal Communications Officer / Manager | Publishes notices and monitors tickets across all wards |
| System Administrator | Verifies roles, manages users and content (e.g. municipal IT or host institution) |
| General Public | Read-only access to the landing page and ward subpages |

**Wider stakeholders:** community organisations (schools, NGOs, SPCA, neighbourhood watch groups) and municipal service delivery departments.

## Reports

Reports are available to all users and combine text-based output with graphics:

- Service fault statistics per ward (daily / weekly / monthly / yearly)
- Service fault progress
- Turnaround time statistics
- Fault category statistics
- Historical trends in complaint categories
- Fault hot-spot visualisations

## Business Rules

Some of the core rules the system enforces:

- A ticket is created by exactly one user (community member or ward councillor); a user can create zero or many tickets.
- Each ticket belongs to exactly one ward; a ward can have many tickets.
- Each ticket relates to exactly one municipal service; a service can have many tickets.
- Each ward has one councillor, and each councillor is linked to one ward.
- A ticket has one status at a time; statuses can be changed, and a status can apply to many tickets.
- Ward councillors can comment on tickets and update their status; each comment belongs to one ticket.
- System administrators verify user roles and add notices to the community space.
- Communications officers can add notices and track tickets system-wide.
- All users have access to reporting features.

## Tech Stack

| Layer | Technology |
|-------|------------|
| Frontend | HTML, CSS, JavaScript |
| Backend | PHP |
| Database | MySQL / MariaDB (SQL scripts in `TABLES & SQL QUERIES`) |
| Local server | XAMPP / WAMP (Apache + MySQL) |

## Getting Started

### Prerequisites

- A local PHP and MySQL environment such as [XAMPP](https://www.apachefriends.org/) or WAMP
- A web browser

### Installation

```bash
# 1. Clone the repository into your server's web root (e.g. C:\xampp\htdocs)
git clone https://github.com/<your-username>/Makhanda-Link-Recovery.git
```

1. Start **Apache** and **MySQL** from the XAMPP control panel.
2. Open phpMyAdmin (`http://localhost/phpmyadmin`) and create a new database (e.g. `makhanda_connect`).
3. Import the SQL scripts from the `TABLES & SQL QUERIES` folder into that database.
4. Update the database connection details (host, username, password, database name) in your PHP connection file(s).
5. Open the site in your browser. The entry point is the `index` file inside `PUBLIC PAGES`:

```
http://localhost/Makhanda-Link-Recovery/PUBLIC%20PAGES/index.php
```

## Project Structure

```
Makhanda-Link-Recovery/
├── PUBLIC PAGES/               # Public landing page (index), ward subpages, login/registration
├── USER_DASHBOARD/             # Community resident dashboard: log and track fault tickets
├── ward_councillor_dashboard/  # Councillor tools: manage tickets, add notes, ward summaries
├── communication_officer/      # Municipal communications officer pages and system-wide ticket tracking
├── ORGANISATION_DASHBOARD/     # Community organisation dashboard
├── AdminOverview/              # Administrator overview: user management and role verification
├── ManageContent - Notices/    # Create, approve and update community and ward notices
├── TABLES & SQL QUERIES/       # Database table definitions and SQL scripts
├── icons/                      # Icons used across the interface
├── documentations.../          # Supporting project documentation
├── ZIPPED FILES/               # Archived copies of project files
├── Error404Connection.php      # Error page
├── makanalogo.jpeg             # Makana municipality logo
└── README.md
```

## Team

| Name | Role / Contribution |
|------|---------------------|
| Faith Lizwi Mlambo | Project Manager & Full Stack Developer |
| Hlompho Noah Nyathi | Infrastructure Manager & Full Stack Developer |
| Qhamani Somhlahlo | Change Manager Analyst & front-end developer |
| Qhamani Somhlahlo | Systems Analyst & Full Stack developer |
| Zevile Ngwabi | Business Analyst & front-end developer |

## Acknowledgements

Project brief prepared by Chris Upfold and Ingrid Siebörger, Department of Information Systems (January 2026, v.1).

## License

> note that this project is for academic purposes only.
