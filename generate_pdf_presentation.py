import subprocess
import os

html_content = """<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>NegoMaster - Data Flow & Security Architecture</title>
<style>
    @page {
        size: 16in 9in;
        margin: 0;
    }
    * {
        box-sizing: border-box;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    body {
        margin: 0;
        padding: 0;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        background-color: #f8fafc;
        color: #1e293b;
        -webkit-font-smoothing: antialiased;
    }

    .slide {
        width: 16in;
        height: 9in;
        padding: 0.65in 0.85in 0.5in 0.85in;
        page-break-after: always;
        position: relative;
        background: #f8fafc;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        overflow: hidden;
    }
    .slide:last-child {
        page-break-after: avoid;
    }

    /* Dark Slide */
    .slide-dark {
        background: radial-gradient(circle at top left, #162447 0%, #0b132b 100%);
        color: #ffffff;
    }

    /* Headers */
    .slide-header {
        margin-bottom: 0.25in;
        position: relative;
    }
    .category-tracker {
        font-size: 11pt;
        font-weight: 800;
        letter-spacing: 1.5px;
        color: #1a56db;
        text-transform: uppercase;
        margin-bottom: 4px;
    }
    .slide-dark .category-tracker {
        color: #0ea5e9;
    }
    .slide-title {
        font-size: 24pt;
        font-weight: 800;
        color: #0b132b;
        margin: 0;
        letter-spacing: -0.5px;
    }
    .slide-dark .slide-title {
        color: #ffffff;
    }
    .header-divider {
        width: 100%;
        height: 2px;
        background: #e2e8f0;
        margin-top: 10px;
    }
    .slide-dark .header-divider {
        background: #334155;
    }

    /* Footer */
    .slide-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid #e2e8f0;
        padding-top: 8px;
        font-size: 9.5pt;
        color: #64748b;
    }
    .slide-dark .slide-footer {
        border-top-color: #334155;
        color: #94a3b8;
    }

    /* Layout Grids */
    .grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.35in;
        flex-grow: 1;
        margin-bottom: 0.15in;
    }
    .grid-3 {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 0.3in;
        flex-grow: 1;
        margin-bottom: 0.15in;
    }
    .grid-4 {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr 1fr;
        gap: 0.25in;
        flex-grow: 1;
        margin-bottom: 0.15in;
    }

    /* Cards */
    .card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 0.28in;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
    }
    .card-dark {
        background: rgba(22, 33, 62, 0.7);
        border: 1px solid #334155;
        border-radius: 12px;
        padding: 0.28in;
    }
    .card-title {
        font-size: 14pt;
        font-weight: 700;
        color: #0b132b;
        margin-top: 0;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .card-title-blue { color: #1a56db; }
    .card-title-emerald { color: #059669; }
    .card-title-danger { color: #dc2626; }
    .card-title-cyan { color: #0284c7; }

    /* Bullet items */
    .item-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .item {
        font-size: 10.5pt;
        line-height: 1.45;
        color: #334155;
    }
    .item-title {
        font-weight: 700;
        color: #0f172a;
    }
    .item-title-blue { color: #1a56db; font-weight: 700; }
    .item-title-emerald { color: #059669; font-weight: 700; }
    .item-title-danger { color: #dc2626; font-weight: 700; }

    /* Badge */
    .badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 9pt;
        font-weight: 700;
        letter-spacing: 0.5px;
    }
    .badge-blue { background: #dbeafe; color: #1e40af; }
    .badge-green { background: #dcfce7; color: #166534; }
    .badge-dark { background: rgba(14, 165, 233, 0.15); color: #38bdf8; border: 1px solid rgba(14, 165, 233, 0.3); }

    /* Banner */
    .security-banner {
        background: #ecfdf5;
        border: 1.5px solid #10b981;
        border-radius: 10px;
        padding: 12px 18px;
        font-size: 10.5pt;
        font-weight: 600;
        color: #065f46;
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 0.15in;
    }

    /* Table */
    table.matrix-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 10pt;
        background: #ffffff;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #cbd5e1;
    }
    table.matrix-table th {
        background: #0b132b;
        color: #ffffff;
        padding: 12px 14px;
        font-weight: 700;
        text-align: left;
        font-size: 10.5pt;
        border-right: 1px solid #1e293b;
    }
    table.matrix-table td {
        padding: 11px 14px;
        border-bottom: 1px solid #e2e8f0;
        border-right: 1px solid #e2e8f0;
        vertical-align: top;
        color: #334155;
        line-height: 1.4;
    }
    table.matrix-table tr:nth-child(even) td {
        background: #f8fafc;
    }
    table.matrix-table td:last-child, table.matrix-table th:last-child {
        border-right: none;
    }

    /* FAQ Card */
    .faq-item {
        background: #ffffff;
        border-radius: 10px;
        border-left: 6px solid #1a56db;
        padding: 12px 18px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.04);
        margin-bottom: 10px;
    }
    .faq-item.border-green { border-left-color: #10b981; }
    .faq-item.border-cyan { border-left-color: #0ea5e9; }
    .faq-item.border-navy { border-left-color: #0b132b; }
    .faq-q {
        font-size: 11.5pt;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 4px;
    }
    .faq-a {
        font-size: 10.2pt;
        color: #475569;
        line-height: 1.45;
    }

    /* Title Slide Specifics */
    .cover-container {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        height: 100%;
    }
    .cover-top {
        margin-top: 0.3in;
    }
    .cover-tagline {
        font-size: 13pt;
        font-weight: 700;
        letter-spacing: 2px;
        color: #38bdf8;
        text-transform: uppercase;
        margin-bottom: 12px;
    }
    .cover-title {
        font-size: 34pt;
        font-weight: 800;
        color: #ffffff;
        line-height: 1.15;
        margin: 0 0 16px 0;
        max-width: 12in;
    }
    .cover-sub {
        font-size: 16pt;
        color: #94a3b8;
        line-height: 1.5;
        max-width: 11in;
    }
</style>
</head>
<body>

<!-- SLIDE 1: COVER -->
<div class="slide slide-dark">
    <div class="cover-container">
        <div class="cover-top">
            <div class="cover-tagline">BANKING IT SECURITY & COMPLIANCE ARCHITECTURE</div>
            <h1 class="cover-title">NegoMaster: Data Flow, Storage Models & Security Isolation</h1>
            <p class="cover-sub">
                A technical overview of data processing across Admin Panels, Local Devices, AI Assistant, and Vectorized RAG for Banking CISOs and IT Security Officers.
            </p>
        </div>

        <div class="grid-3" style="margin-top: 0.4in; flex-grow: 0;">
            <div class="card-dark">
                <div style="font-size: 11.5pt; font-weight: 800; color: #10b981; margin-bottom: 8px;">ZERO BANK NETWORK LINK</div>
                <div style="font-size: 10.5pt; color: #cbd5e1; line-height: 1.45;">
                    Air-gapped from Active Directory & internal core banking systems. Zero lateral movement vector.
                </div>
            </div>
            <div class="card-dark">
                <div style="font-size: 11.5pt; font-weight: 800; color: #38bdf8; margin-bottom: 8px;">ZERO CUSTOMER PII DATA</div>
                <div style="font-size: 10.5pt; color: #cbd5e1; line-height: 1.45;">
                    Strictly handles internal training scenarios & negotiation exercises. No real client financials.
                </div>
            </div>
            <div class="card-dark">
                <div style="font-size: 11.5pt; font-weight: 800; color: #f8fafc; margin-bottom: 8px;">EUROPEAN DATA SOVEREIGNTY</div>
                <div style="font-size: 10.5pt; color: #cbd5e1; line-height: 1.45;">
                    Hosted on Swiss/EU infrastructure. AES-256 at rest, TLS 1.3 in transit, full GDPR compliance.
                </div>
            </div>
        </div>

        <div class="slide-footer">
            <span>NegoMaster Banking Platform | Technical Architecture & Data Processing Security Whitepaper</span>
            <span>Classification: Highly Confidential / Banking Audit</span>
        </div>
    </div>
</div>

<!-- SLIDE 2: PERIMETER ISOLATION & EXECUTIVE SUMMARY -->
<div class="slide">
    <div class="slide-header">
        <div class="category-tracker">THREAT MODEL & PERIMETER ISOLATION</div>
        <h2 class="slide-title">Executive Briefing: Security Perimeter & Incident Containment</h2>
        <div class="header-divider"></div>
    </div>

    <div class="grid-2">
        <div class="card" style="border-left: 5px solid #1a56db;">
            <div class="card-title card-title-blue">Strict Perimeter Isolation (Zero Lateral Exposure)</div>
            <div class="item-list">
                <div class="item">
                    <span class="item-title-blue">• No Active Directory (AD) / LDAP Connection:</span>
                    NegoMaster operates completely outside the bank's internal intranet. It does not require VPN tunnels, AD bindings, or internal domain credentials. A breach on an external system can never lead to lateral movement into the bank.
                </div>
                <div class="item">
                    <span class="item-title-blue">• Zero Real Customer or Financial Data:</span>
                    The platform never touches, requests, or stores real client bank account numbers, transactions, credit files, or IBANs. It is strictly an educational simulation and negotiation training environment.
                </div>
                <div class="item">
                    <span class="item-title-blue">• No Core Banking System Interfaces:</span>
                    No APIs or connections exist to core banking engines (e.g., SAB, Temenos, Avaloq). All scenarios are simulated fictional case studies.
                </div>
                <div class="item">
                    <span class="item-title-blue">• Self-Contained European Cloud Cluster:</span>
                    Runs in an isolated European cloud cluster protected by Web Application Firewalls (WAF), rate-limiting, and TLS 1.3 encryption.
                </div>
            </div>
        </div>

        <div class="card" style="border-left: 5px solid #059669;">
            <div class="card-title card-title-emerald">Data Scope & Storage Overview (4 Categories)</div>
            <div class="item-list">
                <div class="item">
                    <div style="font-weight: 800; color: #059669; font-size: 11pt; margin-bottom: 2px;">
                        1. Admin Panels & Users <span class="badge badge-green">MySQL 8.0 EU</span>
                    </div>
                    Business admin credentials (bcrypt), staff roster (email/name/department), exam scores, and tenant policies. Strictly scoped by <code>business_id</code>.
                </div>
                <div class="item">
                    <div style="font-weight: 800; color: #059669; font-size: 11pt; margin-bottom: 2px;">
                        2. Local Device (Browser/Mobile) <span class="badge badge-blue">Device Memory</span>
                    </div>
                    Ephemeral session token (Sanctum/JWT) in HTTP-only cookie + UI language preference. ZERO sensitive data stored in local persistent storage.
                </div>
                <div class="item">
                    <div style="font-weight: 800; color: #059669; font-size: 11pt; margin-bottom: 2px;">
                        3. AI Assistant & Case Studies <span class="badge badge-green">Stateless API + MySQL</span>
                    </div>
                    Fictional case studies, trainee roleplay answers, and generated evaluation matrices. Stateless API: Zero model training on bank data.
                </div>
                <div class="item">
                    <div style="font-weight: 800; color: #059669; font-size: 11pt; margin-bottom: 2px;">
                        4. Vectorized Chunks (RAG) <span class="badge badge-blue">Qdrant Vector DB</span>
                    </div>
                    Sanitized bank policy PDFs chunked & embedded for fast compliance lookup. Stored with tenant isolation metadata. No Supabase required.
                </div>
            </div>
        </div>
    </div>

    <div class="slide-footer">
        <span>NegoMaster Banking Platform | Technical Architecture & Data Processing Security Whitepaper</span>
        <span>Slide 2 of 9</span>
    </div>
</div>

<!-- SLIDE 3: DATA PROCESSING PIPELINE -->
<div class="slide">
    <div class="slide-header">
        <div class="category-tracker">DATA FLOW PIPELINE</div>
        <h2 class="slide-title">End-to-End Data Processing Architecture</h2>
        <div class="header-divider"></div>
    </div>

    <div class="grid-4">
        <div class="card" style="border-top: 5px solid #1a56db;">
            <div style="font-size: 11pt; font-weight: 800; color: #1a56db; text-transform: uppercase;">STAGE 1: CLIENT LAYER</div>
            <div style="font-size: 9.5pt; color: #64748b; margin-bottom: 12px;">Trainee & Bank Admin</div>
            <div class="item-list">
                <div class="item"><strong>Client Devices:</strong><br>Modern Web Browser / Mobile PWA</div>
                <div class="item"><strong>Data Handled:</strong><br>Form inputs, negotiation responses, dashboard views</div>
                <div class="item"><strong>Device Storage:</strong><br>Ephemeral session token (Secure/HTTP-only)</div>
                <div class="item"><strong>Local PII:</strong><br><span style="color:#dc2626; font-weight:700;">NONE</span> - Zero cached customer data</div>
            </div>
        </div>

        <div class="card" style="border-top: 5px solid #0b132b;">
            <div style="font-size: 11pt; font-weight: 800; color: #0b132b; text-transform: uppercase;">STAGE 2: APP & DB CORE</div>
            <div style="font-size: 9.5pt; color: #64748b; margin-bottom: 12px;">Laravel 11 & MySQL 8.0</div>
            <div class="item-list">
                <div class="item"><strong>Gateway:</strong><br>HTTPS / TLS 1.3 + WAF / CSRF Shield</div>
                <div class="item"><strong>Data Stored:</strong><br>Staff profiles, exam results, policy text, cases</div>
                <div class="item"><strong>Isolation:</strong><br>Tier 1 Logical Multi-Tenancy (<code>business_id</code>)</div>
                <div class="item"><strong>Encryption:</strong><br>AES-256 storage, Bcrypt passwords</div>
            </div>
        </div>

        <div class="card" style="border-top: 5px solid #0284c7;">
            <div style="font-size: 11pt; font-weight: 800; color: #0284c7; text-transform: uppercase;">STAGE 3: RAG & VECTOR</div>
            <div style="font-size: 9.5pt; color: #64748b; margin-bottom: 12px;">FastAPI & Qdrant DB</div>
            <div class="item-list">
                <div class="item"><strong>Service:</strong><br>Python Microservice (Port 8001)</div>
                <div class="item"><strong>Vector Store:</strong><br>Dedicated Qdrant Engine (Not Supabase)</div>
                <div class="item"><strong>Data Chunked:</strong><br>Internal policy PDFs & negotiation rules</div>
                <div class="item"><strong>Embedding:</strong><br>Local multilingual transformer model</div>
            </div>
        </div>

        <div class="card" style="border-top: 5px solid #059669;">
            <div style="font-size: 11pt; font-weight: 800; color: #059669; text-transform: uppercase;">STAGE 4: AI INFERENCE</div>
            <div style="font-size: 9.5pt; color: #64748b; margin-bottom: 12px;">Stateless LLM Engine</div>
            <div class="item-list">
                <div class="item"><strong>AI Role:</strong><br>Evaluates trainee responses vs. bank rules</div>
                <div class="item"><strong>Integration:</strong><br>Stateless HTTPS REST calls</div>
                <div class="item"><strong>Privacy Pact:</strong><br>Zero-Data Retention (ZDR) guarantee</div>
                <div class="item"><strong>Model Retraining:</strong><br><span style="color:#059669; font-weight:700;">NONE</span> - Trainee data is NEVER used</div>
            </div>
        </div>
    </div>

    <div class="security-banner">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        <span>SECURITY GUARANTEE: Continuous end-to-end TLS 1.3 encryption, zero unauthenticated API routes, tenant metadata isolation at database & vector layers, and complete air-gapping from bank core networks.</span>
    </div>

    <div class="slide-footer">
        <span>NegoMaster Banking Platform | Technical Architecture & Data Processing Security Whitepaper</span>
        <span>Slide 3 of 9</span>
    </div>
</div>

<!-- SLIDE 4: STORAGE IN ADMIN PANELS -->
<div class="slide">
    <div class="slide-header">
        <div class="category-tracker">STORAGE LAYER: RELATIONAL DATABASE (MYSQL 8.0)</div>
        <h2 class="slide-title">Data Model: Everything Stored in Admin Panels</h2>
        <div class="header-divider"></div>
    </div>

    <div class="grid-2">
        <div class="card">
            <div class="card-title card-title-blue">Central Super Admin Portal (Master Governance)</div>
            <div class="item-list">
                <div class="item">
                    <span class="item-title-blue">• Tenant Institutional Records:</span>
                    Bank legal name, tenant domain, platform tier, subscription licensing status, and technical contact details.
                </div>
                <div class="item">
                    <span class="item-title-blue">• Global Master Baseline Catalog:</span>
                    Standard negotiation curriculum templates, foundational profiling questionnaires, and baseline policy benchmarks.
                </div>
                <div class="item">
                    <span class="item-title-blue">• Cross-Tenant Isolation Shield:</span>
                    Super Admin manages platform lifecycle but CANNOT view bank-specific employee identities or confidential internal negotiation case study content.
                </div>
                <div class="item">
                    <span class="item-title-blue">• Central Security & Audit Logs:</span>
                    System maintenance logs, authentication events, and API error monitoring. Stored on secure private volumes.
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-title card-title-emerald">Bank Admin Portal (Dedicated Institution Boundary)</div>
            <div class="item-list">
                <div class="item">
                    <span class="item-title-emerald">• Staff Trainee Directory:</span>
                    Bank employee name, institutional email, designated department (e.g., Commercial Lending, Wealth Management, Risk), and status. ZERO customer PII.
                </div>
                <div class="item">
                    <span class="item-title-emerald">• Internal Bank Negotiation Policies:</span>
                    Bank-specific credit limits, pricing escalation rules, and commercial guidelines uploaded in French and English.
                </div>
                <div class="item">
                    <span class="item-title-emerald">• Simulated Negotiation Cases:</span>
                    Fictional corporate borrowing scenarios, client roleplay profiles, and customized question matrices.
                </div>
                <div class="item">
                    <span class="item-title-emerald">• Evaluation & Manager Reviews:</span>
                    Trainee scores, manager grading feedback, compliance audit ratings, and historical learning progress.
                </div>
                <div class="item">
                    <span class="item-title-emerald">• Logical Isolation Enforcement:</span>
                    Enforced at database layer: every query is scoped by <code>WHERE business_id = ?</code>. Zero cross-bank data bleed.
                </div>
            </div>
        </div>
    </div>

    <div class="slide-footer">
        <span>NegoMaster Banking Platform | Technical Architecture & Data Processing Security Whitepaper</span>
        <span>Slide 4 of 9</span>
    </div>
</div>

<!-- SLIDE 5: LOCAL DEVICE STORAGE -->
<div class="slide">
    <div class="slide-header">
        <div class="category-tracker">ENDPOINT FOOTPRINT: ZERO PERSISTENT PII</div>
        <h2 class="slide-title">Endpoint Security: What Lives on the User's Device?</h2>
        <div class="header-divider"></div>
    </div>

    <div class="grid-3">
        <div class="card" style="border-top: 5px solid #1a56db;">
            <div class="card-title card-title-blue">1. What IS Stored Locally</div>
            <div class="item-list">
                <div class="item">
                    <span class="item-title">• Ephemeral Session Token:</span><br>
                    Short-lived JWT / Laravel Sanctum Bearer token stored in secure, SameSite, HTTP-only storage.
                </div>
                <div class="item">
                    <span class="item-title">• UI Localization Preference:</span><br>
                    Stores language toggle ('en' or 'fr') in browser localStorage.
                </div>
                <div class="item">
                    <span class="item-title">• Theme & Display State:</span><br>
                    Responsive UI preferences and collapsed sidebar state.
                </div>
                <div class="item">
                    <span class="item-title">• Active Form State:</span><br>
                    Temporary in-memory input during live quiz/case response.
                </div>
            </div>
        </div>

        <div class="card" style="border-top: 5px solid #dc2626;">
            <div class="card-title card-title-danger">2. What is NEVER Stored Locally</div>
            <div class="item-list">
                <div class="item">
                    <span class="item-title-danger">• Zero Bank Customer Data:</span><br>
                    No real client data ever leaves the cloud or enters browser cache.
                </div>
                <div class="item">
                    <span class="item-title-danger">• Zero Plaintext Passwords:</span><br>
                    Credentials are never stored on device. Always bcrypt-hashed on server.
                </div>
                <div class="item">
                    <span class="item-title-danger">• No Offline Relational DB:</span><br>
                    No SQLite or IndexedDB storage containing bank institutional files.
                </div>
                <div class="item">
                    <span class="item-title-danger">• No Exported Policy Files:</span><br>
                    Internal policy documents are rendered via secure streams, not cached permanently.
                </div>
            </div>
        </div>

        <div class="card" style="border-top: 5px solid #059669;">
            <div class="card-title card-title-emerald">3. Device Security Protections</div>
            <div class="item-list">
                <div class="item">
                    <span class="item-title-emerald">• Automatic Session Expiration:</span><br>
                    Idle timeout automatically invalidates tokens on both client and server.
                </div>
                <div class="item">
                    <span class="item-title-emerald">• Remote Token Invalidation:</span><br>
                    Bank Admins can revoke staff access with one click, cutting active sessions instantly.
                </div>
                <div class="item">
                    <span class="item-title-emerald">• Full In-Transit Encryption:</span><br>
                    Enforced TLS 1.3 / HSTS prevents interception over Wi-Fi or public networks.
                </div>
                <div class="item">
                    <span class="item-title-emerald">• Immune to Lost Device Risks:</span><br>
                    Because no business data resides on device, lost or stolen laptops expose zero banking data.
                </div>
            </div>
        </div>
    </div>

    <div class="slide-footer">
        <span>NegoMaster Banking Platform | Technical Architecture & Data Processing Security Whitepaper</span>
        <span>Slide 5 of 9</span>
    </div>
</div>

<!-- SLIDE 6: AI ASSISTANT & LLM -->
<div class="slide">
    <div class="slide-header">
        <div class="category-tracker">AI GOVERNANCE: ZERO RETENTION & NO SUPABASE RISK</div>
        <h2 class="slide-title">AI Assistant Architecture: Case Studies & LLM Processing</h2>
        <div class="header-divider"></div>
    </div>

    <div class="grid-2">
        <div class="card">
            <div class="card-title card-title-blue">How Case Studies & AI Feedback Work</div>
            <div class="item-list">
                <div class="item">
                    <span class="item-title-blue">• Simulation Dialogue Input:</span>
                    Trainee enters negotiation arguments, rate offers, or concessions into a simulated corporate case study scenario.
                </div>
                <div class="item">
                    <span class="item-title-blue">• Policy Alignment Prompt Assembly:</span>
                    NegoMaster retrieves relevant bank policy rules via semantic vector lookup and combines them into an evaluation prompt.
                </div>
                <div class="item">
                    <span class="item-title-blue">• AI Scoring & Action Plan Generation:</span>
                    The LLM evaluates the negotiation behavior against policy limits and returns structured JSON: <code>ai_analysis</code>, <code>action_plan</code>, and rating scores.
                </div>
                <div class="item">
                    <span class="item-title-blue">• Persistent Result Storage:</span>
                    Generated analysis is saved directly into the institution's MySQL database table (<code>client_cases</code>) scoped by tenant ID for manager audit.
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-title card-title-emerald">Direct Answers to Bank IT Security Concerns</div>
            <div class="item-list">
                <div class="item">
                    <span class="item-title-emerald">• Is Supabase Used?</span>
                    <strong>NO.</strong> NegoMaster does NOT use Supabase or external shared cloud BaaS databases. All relational records reside in our self-hosted MySQL, and vectors reside in a dedicated Qdrant engine.
                </div>
                <div class="item">
                    <span class="item-title-emerald">• Is Data Used to Train AI Models?</span>
                    <strong>STRICTLY NO.</strong> All LLM calls operate under commercial Zero Data Retention (ZDR) agreements. Inference prompts and outputs are NEVER stored by the LLM vendor, nor used for model training or RLHF.
                </div>
                <div class="item">
                    <span class="item-title-emerald">• Stateless Processing Model:</span>
                    AI inference calls are purely stateless API executions over TLS 1.3 HTTPS. No persistent conversational memory is retained outside our private database.
                </div>
                <div class="item">
                    <span class="item-title-emerald">• Data Sovereignty & European Hosting:</span>
                    The AI microservice (<code>pdf_service</code>) is hosted in our private cloud in Europe/Switzerland, ensuring all prompt orchestration stays within European legal jurisdiction.
                </div>
            </div>
        </div>
    </div>

    <div class="slide-footer">
        <span>NegoMaster Banking Platform | Technical Architecture & Data Processing Security Whitepaper</span>
        <span>Slide 6 of 9</span>
    </div>
</div>

<!-- SLIDE 7: VECTORIZED CHUNKS & RAG -->
<div class="slide">
    <div class="slide-header">
        <div class="category-tracker">KNOWLEDGE ENGINE: HIGH-PRECISION RETRIEVAL WITH ZERO DATA LEAKAGE</div>
        <h2 class="slide-title">Vectorized Chunks & RAG: Policy Ingestion Architecture</h2>
        <div class="header-divider"></div>
    </div>

    <div class="grid-4" style="flex-grow: 0; margin-bottom: 0.25in;">
        <div class="card" style="padding: 16px; border-left: 4px solid #1a56db;">
            <div style="font-weight: 800; font-size: 11pt; color: #1a56db; margin-bottom: 4px;">1. PDF Upload</div>
            <div style="font-size: 9.5pt; color: #475569; line-height: 1.4;">
                Bank Admin uploads institutional guidelines, risk manuals, or pricing rules (PDF/Text).
            </div>
        </div>
        <div class="card" style="padding: 16px; border-left: 4px solid #0b132b;">
            <div style="font-weight: 800; font-size: 11pt; color: #0b132b; margin-bottom: 4px;">2. Text Chunking</div>
            <div style="font-size: 9.5pt; color: #475569; line-height: 1.4;">
                Extracted text is split into semantic paragraphs, preserving chapter headers & bilingual tags.
            </div>
        </div>
        <div class="card" style="padding: 16px; border-left: 4px solid #0284c7;">
            <div style="font-weight: 800; font-size: 11pt; color: #0284c7; margin-bottom: 4px;">3. Dense Vectors</div>
            <div style="font-size: 9.5pt; color: #475569; line-height: 1.4;">
                Open-source local model (<code>MiniLM-L12-v2</code>) transforms text into 384-dim math vectors.
            </div>
        </div>
        <div class="card" style="padding: 16px; border-left: 4px solid #059669;">
            <div style="font-weight: 800; font-size: 11pt; color: #059669; margin-bottom: 4px;">4. Qdrant Storage</div>
            <div style="font-size: 9.5pt; color: #475569; line-height: 1.4;">
                Stored in Qdrant Vector DB with strict tenant metadata (<code>business_id</code>). Cross-encoder re-ranked.
            </div>
        </div>
    </div>

    <div class="grid-2">
        <div class="card">
            <div class="card-title card-title-blue">Vector Database Isolation (No Cross-Tenant Retrieval)</div>
            <div class="item-list">
                <div class="item">
                    <span class="item-title-blue">• Tenant-Scoped Payload Filter:</span>
                    Every vector query passes a mandatory filter matching the active <code>business_id</code>. A search executed by Bank A can mathematically never retrieve chunks from Bank B.
                </div>
                <div class="item">
                    <span class="item-title-blue">• No Financial PII Vectorization:</span>
                    Only educational policy texts and fictional negotiation manuals are indexed. Real bank customer accounts are never ingested into the vector DB.
                </div>
                <div class="item">
                    <span class="item-title-blue">• Self-Hosted Infrastructure:</span>
                    Qdrant runs as a private microservice, isolated within our secure container network.
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-title card-title-emerald">Mathematical One-Way Nature of Vectors</div>
            <div class="item-list">
                <div class="item">
                    <span class="item-title-emerald">• Non-Reversible Embeddings:</span>
                    Dense vectors are mathematical points in multi-dimensional space (floating point arrays). They cannot be reverse-engineered to reconstruct original confidential texts without authorized collection access.
                </div>
                <div class="item">
                    <span class="item-title-emerald">• Cross-Encoder Re-Ranking:</span>
                    Retrieved chunks are scored locally by a Cross-Encoder (<code>mmarco-mMiniLMv2</code>) to deliver top-ranked policy precision before answering.
                </div>
                <div class="item">
                    <span class="item-title-emerald">• Instant Purge on Deletion:</span>
                    When a bank policy is updated or deleted, associated vector points in Qdrant are wiped immediately.
                </div>
            </div>
        </div>
    </div>

    <div class="slide-footer">
        <span>NegoMaster Banking Platform | Technical Architecture & Data Processing Security Whitepaper</span>
        <span>Slide 7 of 9</span>
    </div>
</div>

<!-- SLIDE 8: DATA MODEL & STORAGE MATRIX -->
<div class="slide">
    <div class="slide-header">
        <div class="category-tracker">SECURITY REFERENCE TABLE FOR IT AUDITORS</div>
        <h2 class="slide-title">Comprehensive Data Model & Storage Location Matrix</h2>
        <div class="header-divider"></div>
    </div>

    <table class="matrix-table">
        <thead>
            <tr>
                <th style="width: 20%;">Data Category</th>
                <th style="width: 25%;">Data Fields Stored</th>
                <th style="width: 20%;">Storage Technology & Location</th>
                <th style="width: 18%;">Encryption Standards</th>
                <th style="width: 17%;">Tenant Isolation Method</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Admin Panel Data</strong><br>(Tenants & Users)</td>
                <td>Bank profile, admin credentials (bcrypt), staff name, work email, department, role permissions.</td>
                <td>MySQL 8.0 Relational DB<br><strong>Sovereign EU Cloud</strong></td>
                <td>In Transit: TLS 1.3<br>At Rest: AES-256 / Bcrypt</td>
                <td>Strict <code>business_id</code> scoping across all DB queries</td>
            </tr>
            <tr>
                <td><strong>Local Device Data</strong><br>(Browser / Mobile)</td>
                <td>Session token (Sanctum/JWT), language preference ('en'/'fr'), UI theme state. <strong>ZERO PII</strong>.</td>
                <td>Client Memory / HTTP-only Cookie<br><strong>No persistent offline DB</strong></td>
                <td>In Transit: HTTPS / TLS 1.3<br>Cookie: Secure, SameSite</td>
                <td>Session invalidated on logout or idle timeout</td>
            </tr>
            <tr>
                <td><strong>Case Study Results & AI Feedback</strong></td>
                <td>Trainee simulation inputs, AI negotiation analysis, action plans, grading scores, review notes.</td>
                <td>MySQL 8.0 (<code>client_cases</code>)<br><strong>Sovereign EU Cloud</strong></td>
                <td>In Transit: TLS 1.3<br>At Rest: AES-256</td>
                <td>Scoped by <code>user_id</code> & <code>business_id</code> foreign keys</td>
            </tr>
            <tr>
                <td><strong>Policy Documents & Vector Chunks</strong></td>
                <td>Bank negotiation manuals, credit policy rules, semantic text chunks, 384-dim dense vectors.</td>
                <td>Private Qdrant Vector DB + Local Storage for PDFs (EU)</td>
                <td>In Transit: TLS 1.3<br>At Rest: AES-256 volume</td>
                <td>Qdrant collection payload filter on <code>business_id</code></td>
            </tr>
            <tr>
                <td><strong>External LLM Inference Calls</strong></td>
                <td>Temporary prompt text containing fictional scenario + policy context. <strong>ZERO customer data</strong>.</td>
                <td>Stateless API Call<br><strong>Zero Data Retention (ZDR)</strong></td>
                <td>In Transit: TLS 1.3<br>Zero Persistent Vendor Storage</td>
                <td>Ephemeral runtime RAM only; no model training</td>
            </tr>
        </tbody>
    </table>

    <div class="slide-footer">
        <span>NegoMaster Banking Platform | Technical Architecture & Data Processing Security Whitepaper</span>
        <span>Slide 8 of 9</span>
    </div>
</div>

<!-- SLIDE 9: CISO FAQ -->
<div class="slide">
    <div class="slide-header">
        <div class="category-tracker">AUDIT DEFENSE: 5 CRITICAL QUESTIONS FOR IT SECURITY OFFICERS</div>
        <h2 class="slide-title">Banking CISO FAQ: Security Assurance & Incident Containment</h2>
        <div class="header-divider"></div>
    </div>

    <div style="display: flex; flex-direction: column; gap: 8px;">
        <div class="faq-item border-green">
            <div class="faq-q">1. Can an attacker move laterally from NegoMaster into our Core Bank network?</div>
            <div class="faq-a"><strong>IMPOSSIBLE.</strong> NegoMaster has ZERO network connectivity to your bank's internal intranet, core banking system (SAB, Temenos, Avaloq), or private VPN. It is an independent SaaS/dedicated environment. Lateral compromise is physically impossible.</div>
        </div>

        <div class="faq-item">
            <div class="faq-q">2. Does NegoMaster require integration with our Active Directory (AD / Azure AD)?</div>
            <div class="faq-a"><strong>NO.</strong> NegoMaster utilizes its own isolated role-based authentication system with salted Bcrypt password hashing. Staff log in with dedicated credentials, preventing any exposure or compromise of bank domain credentials.</div>
        </div>

        <div class="faq-item border-green">
            <div class="faq-q">3. Is our bank's internal policy or training data used to train public AI models?</div>
            <div class="faq-a"><strong>STRICTLY NO.</strong> All AI inference is executed under enterprise Zero-Data Retention (ZDR) commercial agreements. Your policy prompts and trainee answers are never saved by model providers and never enter training sets.</div>
        </div>

        <div class="faq-item border-navy">
            <div class="faq-q">4. Where is our data physically stored, and are you GDPR/RGPD compliant?</div>
            <div class="faq-a"><strong>100% EUROPEAN HOSTING.</strong> All application databases, vector stores, and backup volumes reside in EU / Swiss data centers. The platform fully complies with European GDPR, including data residency, deletion rights, and tenant export.</div>
        </div>

        <div class="faq-item border-cyan">
            <div class="faq-q">5. What happens to our bank's data if our contract terminates or on audit request?</div>
            <div class="faq-a"><strong>GUARANTEED IMMEDIATE PURGE.</strong> Bank Admins can export all staff records and case results to Excel at any time. Upon contract completion, our team executes a certified cryptographic wipe of all MySQL tables and Qdrant vector vectors.</div>
        </div>
    </div>

    <div class="slide-footer">
        <span>NegoMaster Banking Platform | Technical Architecture & Data Processing Security Whitepaper</span>
        <span>Slide 9 of 9</span>
    </div>
</div>

</body>
</html>
"""

html_path = "E:\\laragon\\www\\banking_co_efficient\\NegoMaster_Data_Flow_and_Security_Architecture.html"
pdf_path = "E:\\laragon\\www\\banking_co_efficient\\NegoMaster_Data_Flow_and_Security_Architecture.pdf"

with open(html_path, "w", encoding="utf-8") as f:
    f.write(html_content)

print(f"Generated HTML slide deck at: {html_path}")

chrome_exe = "C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe"
args = [
    chrome_exe,
    "--headless=new",
    "--disable-gpu",
    "--no-pdf-header-footer",
    f"--print-to-pdf={pdf_path}",
    f"file:///{html_path.replace(os.sep, '/')}"
]

print("Converting HTML slide deck to PDF via Google Chrome...")
result = subprocess.run(args, capture_output=True, text=True)
print("Return code:", result.returncode)
print("STDOUT:", result.stdout)
print("STDERR:", result.stderr)

if os.path.exists(pdf_path):
    size_kb = os.path.getsize(pdf_path) / 1024
    print(f"SUCCESS: Generated PDF at {pdf_path} ({size_kb:.1f} KB)")
else:
    print("FAILED to generate PDF.")

