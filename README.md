# Web-Based IT Service Desk & Virtual Meeting Scheduling Platform (ALISA)

> **Internal IT Helpdesk Ticket System, Real-Time Coordination Chat, Technician KPI Monitoring, and Zoom Meeting Room Reservation Platform Powered by Fat-Free Framework (F3)**

![PHP](https://img.shields.io/badge/PHP-%3E%3D%207.4%20%7C%208.x-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Framework](https://img.shields.io/badge/Framework-Fat--Free%203.x-F15A24?style=for-the-badge)
![Database](https://img.shields.io/badge/Database-MySQL%20%7C%20MariaDB-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Architecture](https://img.shields.io/badge/Architecture-MVC%20Pattern-00599C?style=for-the-badge)
![Frontend](https://img.shields.io/badge/Frontend-Bootstrap%205%20%7C%20Chart.js%20%7C%20Plus%20Jakarta%20Sans-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)
![Security](https://img.shields.io/badge/Security-CSRF%20%7C%20Rate%20Limiting%20%7C%20SVG%20CAPTCHA-2e7d32?style=for-the-badge)

---

## Overview & Background

**ALISA (Aplikasi Layanan Internal IT)** is an enterprise web-based IT Service Management (ITSM) and collaborative helpdesk platform. The system serves as the unified central bridge between cross-departmental staff and the internal IT infrastructure unit.

Prior to the deployment of ALISA, technical service provisioning within organizations frequently faced acute operational bottlenecks:
- **Fragmented Support Channels:** Troubleshooting inquiries (LAN networks, fiber-optic drops, workstation OS crashes, device drivers, network printers) were scattered across personal WhatsApp threads, paper memos, and unrecorded verbal reports, resulting in lost ticket history and lack of accountability.
- **Resource Collision in Hybrid Conferencing:** The coordination of virtual and hybrid official meetings frequently resulted in double-booked enterprise Zoom accounts and conflicting physical meeting rooms.
- **Lack of Quantitative IT Performance Metrics:** IT management lacked real-time visibility into workload distribution, average technician response times, mean-time-to-resolution (MTTR), and recurring hardware failures.
- **Identity & Session Security Risks:** Legacy administrative tools lacked unified CSRF protection, brute-force mitigation, and strict audit log capabilities.

To resolve these challenges, **ALISA** was architected on a high-throughput, minimalist **Fat-Free Framework (F3)** MVC core. The platform integrates responsive two-way helpdesk chat, dynamic ticket classification with customizable form fields, an automated conflict-free Zoom scheduling engine with interactive calendar heatmaps, real-time technician status toggles (*Available, Busy, Offline*), and an executive analytical dashboard powered by Chart.js.

---

## Key Modules & Functional Capabilities

### 1. Helpdesk Ticketing & Live Incident Chat
- **Instant Incident Dispatch:** Employees submit structured service tickets categorized by problem domain (Internet, Workstations, Printers, Zoom Meetings, Software, and Enterprise Systems).
- **Two-Way Communication Stream:** Full asynchronous chat thread connecting the requester directly to assigned IT specialists, complete with image and document attachment uploads.
- **Ticket Lifecycle State Machine:** Tracks tickets from `waiting` (queued) to `in_progress` (assigned and actively handled) through `done` (resolved with timestamp audit).

### 2. Conflict-Free Zoom Meeting & Room Scheduling
- **Room Allocation Engine:** Manages physical conference spaces mapped directly to organizational Zoom licenses.
- **Multi-Type Meeting Requests:** Supports IT-generated meeting links, dedicated technician on-site facilitation, or user-provided external meeting IDs with passcode management.
- **Interactive Availability Calendar:** FullCalendar view displaying scheduled sessions, time slots, and room occupancy status to eliminate overlapping sessions.

### 3. Internal IT Team Group Coordination
- **Dedicated Tech Channels:** Private group chat spaces for IT engineers and administrators to coordinate on-site repairs, server maintenance, and infrastructure updates.
- **Message Pinning & Mention Engine:** Facilitates highlighting critical announcements, server IPs, and network outage alerts directly within channels.

### 4. Executive Analytics & Technician KPI Monitoring
- **Real-Time KPI Scorecards:** Aggregates today's incoming load, resolved tickets, queue bottlenecks, and average resolution speed.
- **Categorical Failure Distribution:** Visual analytics illustrating failure frequencies to support proactive preventative maintenance.
- **Individual Technician Workload Breakdown:** Monitors performance equity across assigned technician rosters.

### 5. Dynamic Service Categories & Form Builder
- **Extensible Schema:** Administrators can create new service categories and attach dynamic custom fields (text inputs, dropdown selects, and textareas) without modifying database schemas.

---

## Technology Stack

- **Backend Framework:** Fat-Free Framework 3.9+ (F3) running on PHP 7.4 / 8.x
- **Architecture Pattern:** Model-View-Controller (MVC) with decoupled controllers, single-responsibility models, and componentized view partials
- **Database Engine:** MySQL 5.7+ / MariaDB 10.3+ (InnoDB, UTF-8 MB4 Unicode)
- **Frontend Presentation:** HTML5, CSS3 Modern Glassmorphism & Dark Themes, Bootstrap
- **Typography:** Plus Jakarta Sans & Inter (Google Fonts CDN)
- **Data Visualizations:** Chart.js
- **Calendar & Scheduling:** FullCalendar Engine with RESTful event feed (`/api/calendar/events`)

---
