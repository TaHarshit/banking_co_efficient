import sys
import os
import pptx
from pptx.util import Inches, Pt
from pptx.dml.color import RGBColor
from pptx.enum.text import PP_ALIGN, MSO_ANCHOR
from pptx.enum.shapes import MSO_SHAPE

def create_deck(filename="NegoMaster_Data_Flow_and_Security_Architecture.pptx"):
    prs = pptx.Presentation()
    prs.slide_width = Inches(13.333)
    prs.slide_height = Inches(7.5)
    blank_layout = prs.slide_layouts[6]

    # Brand Colors
    NAVY_DARK = RGBColor(11, 19, 43)        # #0B132B
    NAVY_CARD = RGBColor(22, 33, 62)        # #16213E
    BLUE_PRIMARY = RGBColor(26, 86, 219)    # #1A56DB
    BLUE_LIGHT = RGBColor(239, 246, 255)    # #EFF6FF
    BLUE_ACCENT = RGBColor(14, 165, 233)    # #0EA5E9
    EMERALD = RGBColor(16, 185, 129)        # #10B981
    EMERALD_BG = RGBColor(236, 253, 245)    # #ECFDF5
    AMBER = RGBColor(245, 158, 11)          # #F59E0B
    SLATE_TEXT = RGBColor(30, 41, 59)       # #1E293B
    MUTED_TEXT = RGBColor(100, 116, 139)    # #64748B
    WHITE = RGBColor(255, 255, 255)
    LIGHT_BG = RGBColor(248, 250, 252)      # #F8FAFC
    BORDER_COLOR = RGBColor(226, 232, 240)  # #E2E8F0
    CARD_BG = RGBColor(255, 255, 255)

    def set_slide_background(slide, color):
        background = slide.background
        fill = background.fill
        fill.solid()
        fill.fore_color.rgb = color

    def add_header(slide, title_text, category="ARCHITECTURE & DATA GOVERNANCE", dark=False):
        # Category / Tracker
        tb_cat = slide.shapes.add_textbox(Inches(0.8), Inches(0.4), Inches(11.7), Inches(0.3))
        tf_cat = tb_cat.text_frame
        tf_cat.word_wrap = True
        tf_cat.margin_left = tf_cat.margin_right = tf_cat.margin_top = tf_cat.margin_bottom = 0
        p_cat = tf_cat.paragraphs[0]
        p_cat.text = category.upper()
        p_cat.font.size = Pt(10)
        p_cat.font.bold = True
        p_cat.font.color.rgb = BLUE_ACCENT if dark else BLUE_PRIMARY

        # Main Title
        tb_title = slide.shapes.add_textbox(Inches(0.8), Inches(0.68), Inches(11.7), Inches(0.6))
        tf_title = tb_title.text_frame
        tf_title.word_wrap = True
        tf_title.margin_left = tf_title.margin_right = tf_title.margin_top = tf_title.margin_bottom = 0
        p_title = tf_title.paragraphs[0]
        p_title.text = title_text
        p_title.font.size = Pt(22)
        p_title.font.bold = True
        p_title.font.color.rgb = WHITE if dark else NAVY_DARK

        # Top subtle separator line
        line = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(0.8), Inches(1.3), Inches(11.733), Inches(0.02))
        line.fill.solid()
        line.fill.fore_color.rgb = RGBColor(30, 41, 59) if dark else BORDER_COLOR
        line.line.color.rgb = RGBColor(30, 41, 59) if dark else BORDER_COLOR

    def add_footer(slide, dark=False):
        tb = slide.shapes.add_textbox(Inches(0.8), Inches(7.05), Inches(11.733), Inches(0.3))
        tf = tb.text_frame
        tf.margin_left = tf.margin_right = tf.margin_top = tf.margin_bottom = 0
        p = tf.paragraphs[0]
        p.text = "NegoMaster Banking Platform | Technical Architecture & Data Processing Security Whitepaper | European Banking Compliance"
        p.font.size = Pt(9)
        p.font.color.rgb = RGBColor(148, 163, 184) if dark else MUTED_TEXT

    # -------------------------------------------------------------
    # SLIDE 1: Title Slide (Dark Theme)
    # -------------------------------------------------------------
    s1 = prs.slides.add_slide(blank_layout)
    set_slide_background(s1, NAVY_DARK)

    # Accent decorative bar
    bar = s1.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(0.8), Inches(1.2), Inches(0.6), Inches(0.08))
    bar.fill.solid()
    bar.fill.fore_color.rgb = BLUE_ACCENT
    bar.line.fill.background()

    tb = s1.shapes.add_textbox(Inches(0.8), Inches(1.5), Inches(11.5), Inches(0.4))
    tf = tb.text_frame
    p = tf.paragraphs[0]
    p.text = "BANKING IT SECURITY & COMPLIANCE ARCHITECTURE"
    p.font.size = Pt(13)
    p.font.bold = True
    p.font.color.rgb = BLUE_ACCENT

    tb = s1.shapes.add_textbox(Inches(0.8), Inches(2.0), Inches(11.5), Inches(1.8))
    tf = tb.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "NegoMaster: Data Flow, Storage Models & Security Isolation"
    p.font.size = Pt(32)
    p.font.bold = True
    p.font.color.rgb = WHITE

    p2 = tf.add_paragraph()
    p2.text = "A technical overview of data processing across Admin Panels, Local Devices, AI Assistant, and Vectorized RAG for Banking CISOs and IT Security Officers."
    p2.font.size = Pt(15)
    p2.font.color.rgb = RGBColor(203, 213, 225)
    p2.space_before = Pt(12)

    # 3 Key Assurance Highlights (Cards at bottom)
    cards_data = [
        ("ZERO BANK NETWORK LINK", "Air-gapped from Active Directory & internal core banking systems. No lateral attack vector.", EMERALD),
        ("ZERO CUSTOMER PII DATA", "Strictly handles internal training scenarios & negotiation exercises. No real client financials.", BLUE_ACCENT),
        ("EUROPEAN DATA SOVEREIGNTY", "Hosted on Swiss/EU infrastructure. AES-256 at rest, TLS 1.3 in transit, full GDPR compliance.", WHITE)
    ]
    for i, (title, desc, color) in enumerate(cards_data):
        x = Inches(0.8 + i * 4.0)
        y = Inches(4.5)
        w = Inches(3.73)
        h = Inches(2.0)
        
        card = s1.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, x, y, w, h)
        card.fill.solid()
        card.fill.fore_color.rgb = NAVY_CARD
        card.line.color.rgb = RGBColor(51, 65, 85)
        
        tb_c = s1.shapes.add_textbox(x + Inches(0.2), y + Inches(0.2), w - Inches(0.4), h - Inches(0.4))
        tf_c = tb_c.text_frame
        tf_c.word_wrap = True
        
        p_c1 = tf_c.paragraphs[0]
        p_c1.text = title
        p_c1.font.size = Pt(11)
        p_c1.font.bold = True
        p_c1.font.color.rgb = color
        
        p_c2 = tf_c.add_paragraph()
        p_c2.text = desc
        p_c2.font.size = Pt(10)
        p_c2.font.color.rgb = RGBColor(203, 213, 225)
        p_c2.space_before = Pt(8)

    add_footer(s1, dark=True)

    # -------------------------------------------------------------
    # SLIDE 2: Executive Summary & CISO Context (French Bank Incident Context)
    # -------------------------------------------------------------
    s2 = prs.slides.add_slide(blank_layout)
    set_slide_background(s2, LIGHT_BG)
    add_header(s2, "Executive Briefing: Security Perimeter & Incident Containment", "THREAT MODEL & PERIMETER ISOLATION")

    # Left Column: CISO Security Perimeter (High Priority Box)
    x1, y1, w1, h1 = Inches(0.8), Inches(1.5), Inches(5.6), Inches(5.2)
    card1 = s2.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, x1, y1, w1, h1)
    card1.fill.solid()
    card1.fill.fore_color.rgb = CARD_BG
    card1.line.color.rgb = BORDER_COLOR

    tb1 = s2.shapes.add_textbox(x1 + Inches(0.3), y1 + Inches(0.3), w1 - Inches(0.6), h1 - Inches(0.6))
    tf1 = tb1.text_frame
    tf1.word_wrap = True
    
    p = tf1.paragraphs[0]
    p.text = "Strict Perimeter Isolation (Zero Lateral Exposure)"
    p.font.size = Pt(15)
    p.font.bold = True
    p.font.color.rgb = NAVY_DARK

    bullets1 = [
        ("No Active Directory (AD) / LDAP Connection", "NegoMaster operates completely outside the bank's internal intranet. It does not require VPN tunnels, AD bindings, or internal domain credentials. A compromise on an external system can never lead to lateral movement into the bank."),
        ("Zero Real Customer or Financial Data", "The platform never touches, requests, or stores real client bank account numbers, transactions, credit files, or IBANs. It is strictly an educational simulation and negotiation training environment."),
        ("No Core Banking System Interfaces", "No APIs or connections exist to core banking engines (e.g., SAB, Temenos, Avaloq). All scenarios are simulated fictional case studies."),
        ("Self-Contained SaaS / Dedicated Tier Option", "Runs in an isolated European cloud cluster with strict perimeter firewalls (WAF, DDoS mitigation, HTTPS TLS 1.3).")
    ]
    for b_title, b_desc in bullets1:
        pb = tf1.add_paragraph()
        pb.text = f"• {b_title}: "
        pb.font.bold = True
        pb.font.size = Pt(10.5)
        pb.font.color.rgb = BLUE_PRIMARY
        pb.space_before = Pt(8)
        run = pb.add_run()
        run.text = b_desc
        run.font.bold = False
        run.font.color.rgb = SLATE_TEXT

    # Right Column: The 4 Data Categories Breakdown Summary
    x2, y2, w2, h2 = Inches(6.8), Inches(1.5), Inches(5.733), Inches(5.2)
    card2 = s2.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, x2, y2, w2, h2)
    card2.fill.solid()
    card2.fill.fore_color.rgb = CARD_BG
    card2.line.color.rgb = BORDER_COLOR

    tb2 = s2.shapes.add_textbox(x2 + Inches(0.3), y2 + Inches(0.3), w2 - Inches(0.6), h2 - Inches(0.6))
    tf2 = tb2.text_frame
    tf2.word_wrap = True

    p = tf2.paragraphs[0]
    p.text = "Data Scope & Storage Overview"
    p.font.size = Pt(15)
    p.font.bold = True
    p.font.color.rgb = NAVY_DARK

    categories = [
        ("1. Admin Panels & Users", "MySQL 8.0 (EU)", "Business admin credentials (bcrypt), staff roster (email/name/department), exam scores, and tenant policies. Strictly scoped by business_id."),
        ("2. Local Device (Browser/Mobile)", "Device Memory", "Ephemeral session token (Sanctum/JWT) in HTTP-only cookie + UI language preference. ZERO sensitive data stored in local persistent storage."),
        ("3. AI Assistant & Case Studies", "Stateless API + MySQL", "Fictional case studies, trainee roleplay answers, and generated evaluation matrices. Stateless API: Zero model training on bank data."),
        ("4. Vectorized Chunks (RAG)", "Qdrant Vector DB", "Sanitized bank policy PDFs chunked & embedded for fast compliance lookup. Stored with tenant isolation metadata. No Supabase required.")
    ]
    for c_title, c_tech, c_desc in categories:
        pb = tf2.add_paragraph()
        pb.text = f"{c_title}  [{c_tech}]"
        pb.font.bold = True
        pb.font.size = Pt(11)
        pb.font.color.rgb = EMERALD
        pb.space_before = Pt(10)

        pd = tf2.add_paragraph()
        pd.text = c_desc
        pd.font.size = Pt(10)
        pd.font.color.rgb = SLATE_TEXT
        pd.space_before = Pt(2)

    add_footer(s2)

    # -------------------------------------------------------------
    # SLIDE 3: End-to-End Data Processing Flow (Architecture Diagram)
    # -------------------------------------------------------------
    s3 = prs.slides.add_slide(blank_layout)
    set_slide_background(s3, LIGHT_BG)
    add_header(s3, "End-to-End Data Processing Architecture", "DATA FLOW PIPELINE")

    # 4 Flow Steps / Pillars
    stages = [
        ("STAGE 1: CLIENT LAYER", "Trainee & Bank Admin", [
            ("Client Devices", "Web Browser / Mobile PWA"),
            ("Data Handled", "User inputs, negotiation responses, view dashboards"),
            ("Device Storage", "Ephemeral session token (Secure/HTTP-only)"),
            ("Local PII", "NONE - Zero cached customer data")
        ], BLUE_PRIMARY),
        ("STAGE 2: APP & DB CORE", "Laravel 11 & MySQL", [
            ("Gateway", "HTTPS / TLS 1.3 + WAF / CSRF Shield"),
            ("Data Stored", "Staff profiles, exam results, policy text, case studies"),
            ("Isolation", "Tier 1 Logical Multi-Tenancy (business_id)"),
            ("Encryption", "AES-256 storage, Bcrypt passwords")
        ], NAVY_DARK),
        ("STAGE 3: RAG & VECTOR", "FastAPI & Qdrant DB", [
            ("Service", "Python Microservice (Port 8001)"),
            ("Vector Store", "Dedicated Qdrant Engine (Not Supabase)"),
            ("Data Chunked", "Internal policy PDFs & negotiation guidelines"),
            ("Embedding", "Local multilingual transformer model")
        ], BLUE_ACCENT),
        ("STAGE 4: AI INFERENCE", "Stateless LLM Engine", [
            ("AI Role", "Evaluates trainee responses against bank policies"),
            ("Integration", "Stateless HTTPS REST calls"),
            ("Privacy Pact", "Zero-Data Retention (ZDR) guarantee"),
            ("Model Retraining", "NONE - Trainee data is NEVER used for training")
        ], EMERALD)
    ]

    for i, (st_name, st_sub, items, border_col) in enumerate(stages):
        x = Inches(0.8 + i * 2.98)
        y = Inches(1.5)
        w = Inches(2.8)
        h = Inches(4.5)

        card = s3.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, x, y, w, h)
        card.fill.solid()
        card.fill.fore_color.rgb = CARD_BG
        card.line.color.rgb = border_col
        card.line.width = Pt(1.5)

        tb = s3.shapes.add_textbox(x + Inches(0.2), y + Inches(0.2), w - Inches(0.4), h - Inches(0.4))
        tf = tb.text_frame
        tf.word_wrap = True

        p1 = tf.paragraphs[0]
        p1.text = st_name
        p1.font.size = Pt(11)
        p1.font.bold = True
        p1.font.color.rgb = border_col

        p2 = tf.add_paragraph()
        p2.text = st_sub
        p2.font.size = Pt(10)
        p2.font.color.rgb = MUTED_TEXT
        p2.space_before = Pt(2)

        for label, val in items:
            pl = tf.add_paragraph()
            pl.text = f"{label}:"
            pl.font.size = Pt(9)
            pl.font.bold = True
            pl.font.color.rgb = SLATE_TEXT
            pl.space_before = Pt(8)

            pv = tf.add_paragraph()
            pv.text = val
            pv.font.size = Pt(9.5)
            pv.font.color.rgb = NAVY_DARK
            pv.space_before = Pt(1)

    # Security Banner at bottom
    bx, by, bw, bh = Inches(0.8), Inches(6.15), Inches(11.733), Inches(0.7)
    bcard = s3.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, bx, by, bw, bh)
    bcard.fill.solid()
    bcard.fill.fore_color.rgb = EMERALD_BG
    bcard.line.color.rgb = EMERALD

    btb = s3.shapes.add_textbox(bx + Inches(0.2), by + Inches(0.12), bw - Inches(0.4), bh - Inches(0.24))
    btf = btb.text_frame
    btf.word_wrap = True
    bp = btf.paragraphs[0]
    bp.text = "SECURITY GUARANTEE: Continuous end-to-end TLS 1.3 encryption, zero unauthenticated API routes, tenant metadata isolation at database & vector layers, and complete air-gapping from bank core networks."
    bp.font.size = Pt(9.5)
    bp.font.bold = True
    bp.font.color.rgb = RGBColor(6, 95, 70)

    add_footer(s3)

    # -------------------------------------------------------------
    # SLIDE 4: Everything Stored in the Admin Panels (Relational DB)
    # -------------------------------------------------------------
    s4 = prs.slides.add_slide(blank_layout)
    set_slide_background(s4, LIGHT_BG)
    add_header(s4, "Data Model: Everything Stored in Admin Panels", "STORAGE LAYER: RELATIONAL DATABASE (MYSQL 8.0)")

    # Left Column: Super Admin Panel Scope
    x1, y1, w1, h1 = Inches(0.8), Inches(1.5), Inches(5.6), Inches(5.2)
    card1 = s4.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, x1, y1, w1, h1)
    card1.fill.solid()
    card1.fill.fore_color.rgb = CARD_BG
    card1.line.color.rgb = BORDER_COLOR

    tb1 = s4.shapes.add_textbox(x1 + Inches(0.3), y1 + Inches(0.3), w1 - Inches(0.6), h1 - Inches(0.6))
    tf1 = tb1.text_frame
    tf1.word_wrap = True
    p1 = tf1.paragraphs[0]
    p1.text = "Central Super Admin Portal (Master Governance)"
    p1.font.size = Pt(14)
    p1.font.bold = True
    p1.font.color.rgb = NAVY_DARK

    super_items = [
        ("Tenant Institutional Records", "Bank legal name, tenant domain, platform tier, subscription licensing status, and technical contact details."),
        ("Global Master Baseline Catalog", "Standard negotiation curriculum templates, foundational profiling questionnaires, and baseline policy benchmarks."),
        ("Cross-Tenant Isolation Shield", "Super Admin manages platform lifecycle but CANNOT view bank-specific employee identities or confidential internal negotiation case study content."),
        ("Central Security & Audit Logs", "System maintenance logs, authentication events, and API error monitoring. Stored on secure private volumes.")
    ]
    for it_t, it_d in super_items:
        p = tf1.add_paragraph()
        p.text = f"• {it_t}: "
        p.font.bold = True
        p.font.size = Pt(10)
        p.font.color.rgb = BLUE_PRIMARY
        p.space_before = Pt(8)
        run = p.add_run()
        run.text = it_d
        run.font.bold = False
        run.font.color.rgb = SLATE_TEXT

    # Right Column: Bank Admin Panel Scope (Dedicated Bank Tenant)
    x2, y2, w2, h2 = Inches(6.8), Inches(1.5), Inches(5.733), Inches(5.2)
    card2 = s4.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, x2, y2, w2, h2)
    card2.fill.solid()
    card2.fill.fore_color.rgb = CARD_BG
    card2.line.color.rgb = BORDER_COLOR

    tb2 = s4.shapes.add_textbox(x2 + Inches(0.3), y2 + Inches(0.3), w2 - Inches(0.6), h2 - Inches(0.6))
    tf2 = tb2.text_frame
    tf2.word_wrap = True
    p2 = tf2.paragraphs[0]
    p2.text = "Bank Admin Portal (Dedicated Institution Boundary)"
    p2.font.size = Pt(14)
    p2.font.bold = True
    p2.font.color.rgb = NAVY_DARK

    bank_items = [
        ("Staff Trainee Directory", "Bank employee name, institutional email, designated department (e.g., Commercial Lending, Wealth Management, Risk), and status. ZERO customer PII."),
        ("Internal Bank Negotiation Policies", "Bank-specific credit limits, pricing escalation rules, and commercial guidelines uploaded in French and English."),
        ("Simulated Negotiation Cases", "Fictional corporate borrowing scenarios, client roleplay profiles, and customized question matrices."),
        ("Evaluation & Manager Reviews", "Trainee scores, manager grading feedback, compliance audit ratings, and historical learning progress."),
        ("Logical Isolation Enforcement", "Enforced at database layer: every query is scoped by `WHERE business_id = ?`. Zero cross-bank data bleed.")
    ]
    for it_t, it_d in bank_items:
        p = tf2.add_paragraph()
        p.text = f"• {it_t}: "
        p.font.bold = True
        p.font.size = Pt(10)
        p.font.color.rgb = EMERALD
        p.space_before = Pt(8)
        run = p.add_run()
        run.text = it_d
        run.font.bold = False
        run.font.color.rgb = SLATE_TEXT

    add_footer(s4)

    # -------------------------------------------------------------
    # SLIDE 5: Everything Stored Locally on the Device (Browser / Mobile)
    # -------------------------------------------------------------
    s5 = prs.slides.add_slide(blank_layout)
    set_slide_background(s5, LIGHT_BG)
    add_header(s5, "Endpoint Security: What Lives on the User's Device?", "ENDPOINT FOOTPRINT: ZERO PERSISTENT PII")

    # 3 Column Cards
    col_w = Inches(3.733)
    c_data = [
        ("1. What IS Stored Locally", BLUE_PRIMARY, [
            ("Ephemeral Session Token", "Short-lived JWT / Laravel Sanctum Bearer token stored in secure, SameSite, HTTP-only storage."),
            ("UI Localization Preference", "Stores language toggle ('en' or 'fr') in browser localStorage."),
            ("Theme & Display State", "Responsive UI preferences and collapsed sidebar state."),
            ("Active Form State", "Temporary in-memory input during live quiz/case response.")
        ]),
        ("2. What is NEVER Stored Locally", RGBColor(220, 38, 38), [
            ("Zero Bank Customer Data", "No real client data ever leaves the cloud or enters browser cache."),
            ("Zero Plaintext Passwords", "Credentials are never stored on device. Always bcrypt-hashed on server."),
            ("No Offline Relational DB", "No SQLite or IndexedDB storage containing bank institutional files."),
            ("No Exported Policy Files", "Internal policy documents are rendered via secure streams, not cached permanently.")
        ]),
        ("3. Device Security Protections", EMERALD, [
            ("Automatic Session Expiration", "Idle timeout automatically invalidates tokens on both client and server."),
            ("Remote Token Invalidation", "Bank Admins can revoke staff access with one click, cutting active sessions instantly."),
            ("Full In-Transit Encryption", "Enforced TLS 1.3 / HSTS prevents interception over Wi-Fi or public networks."),
            ("Immune to Lost Device Risks", "Because no business data resides on device, lost or stolen laptops expose zero banking data.")
        ])
    ]

    for i, (col_title, col_col, col_items) in enumerate(c_data):
        x = Inches(0.8 + i * 4.0)
        y = Inches(1.5)
        h = Inches(5.2)

        card = s5.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, x, y, col_w, h)
        card.fill.solid()
        card.fill.fore_color.rgb = CARD_BG
        card.line.color.rgb = col_col
        card.line.width = Pt(1.5)

        tb = s5.shapes.add_textbox(x + Inches(0.25), y + Inches(0.25), col_w - Inches(0.5), h - Inches(0.5))
        tf = tb.text_frame
        tf.word_wrap = True

        p = tf.paragraphs[0]
        p.text = col_title
        p.font.size = Pt(13)
        p.font.bold = True
        p.font.color.rgb = col_col

        for it_title, it_desc in col_items:
            pb = tf.add_paragraph()
            pb.text = f"• {it_title}"
            pb.font.bold = True
            pb.font.size = Pt(10)
            pb.font.color.rgb = NAVY_DARK
            pb.space_before = Pt(8)

            pd = tf.add_paragraph()
            pd.text = it_desc
            pd.font.size = Pt(9.5)
            pd.font.color.rgb = SLATE_TEXT
            pd.space_before = Pt(1)

    add_footer(s5)

    # -------------------------------------------------------------
    # SLIDE 6: Everything Generated by Case Studies & AI Assistant (LLM)
    # -------------------------------------------------------------
    s6 = prs.slides.add_slide(blank_layout)
    set_slide_background(s6, LIGHT_BG)
    add_header(s6, "AI Assistant Architecture: Case Studies & LLM Processing", "AI GOVERNANCE: ZERO RETENTION & NO SUPABASE RISK")

    # Left: How AI Processes Case Studies
    x1, y1, w1, h1 = Inches(0.8), Inches(1.5), Inches(5.6), Inches(5.2)
    card1 = s6.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, x1, y1, w1, h1)
    card1.fill.solid()
    card1.fill.fore_color.rgb = CARD_BG
    card1.line.color.rgb = BORDER_COLOR

    tb1 = s6.shapes.add_textbox(x1 + Inches(0.3), y1 + Inches(0.3), w1 - Inches(0.6), h1 - Inches(0.6))
    tf1 = tb1.text_frame
    tf1.word_wrap = True
    p1 = tf1.paragraphs[0]
    p1.text = "How Case Studies & AI Feedback Work"
    p1.font.size = Pt(14)
    p1.font.bold = True
    p1.font.color.rgb = NAVY_DARK

    ai_steps = [
        ("Simulation Dialogue Input", "Trainee enters negotiation arguments, rate offers, or concessions into a simulated corporate case study scenario."),
        ("Policy Alignment Prompt Assembly", "NegoMaster retrieves relevant bank policy rules via semantic vector lookup and combines them into an evaluation prompt."),
        ("AI Scoring & Action Plan Generation", "The LLM evaluates the negotiation behavior against policy limits and returns structured JSON: `ai_analysis`, `action_plan`, and rating scores."),
        ("Persistent Result Storage", "Generated analysis is saved directly into the institution's MySQL database table (`client_cases`) scoped by tenant ID for manager audit.")
    ]
    for it_t, it_d in ai_steps:
        p = tf1.add_paragraph()
        p.text = f"• {it_t}: "
        p.font.bold = True
        p.font.size = Pt(10)
        p.font.color.rgb = BLUE_PRIMARY
        p.space_before = Pt(8)
        run = p.add_run()
        run.text = it_d
        run.font.bold = False
        run.font.color.rgb = SLATE_TEXT

    # Right: Direct Clarification on LLM, Supabase & Zero Training
    x2, y2, w2, h2 = Inches(6.8), Inches(1.5), Inches(5.733), Inches(5.2)
    card2 = s6.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, x2, y2, w2, h2)
    card2.fill.solid()
    card2.fill.fore_color.rgb = CARD_BG
    card2.line.color.rgb = BORDER_COLOR

    tb2 = s6.shapes.add_textbox(x2 + Inches(0.3), y2 + Inches(0.3), w2 - Inches(0.6), h2 - Inches(0.6))
    tf2 = tb2.text_frame
    tf2.word_wrap = True
    p2 = tf2.paragraphs[0]
    p2.text = "Direct Answers to Bank IT Security Concerns"
    p2.font.size = Pt(14)
    p2.font.bold = True
    p2.font.color.rgb = NAVY_DARK

    ciso_clarifications = [
        ("Is Supabase Used?", "NO. NegoMaster does NOT use Supabase or external shared cloud BaaS databases. All relational records reside in our self-hosted MySQL, and vectors reside in a dedicated Qdrant engine."),
        ("Is Data Used to Train AI Models?", "STRICTLY NO. All LLM calls operate under commercial Zero Data Retention (ZDR) agreements. Inference prompts and outputs are NEVER stored by the LLM vendor, nor used for model training or RLHF."),
        ("Stateless Processing Model", "AI inference calls are purely stateless API executions over TLS 1.3 HTTPS. No persistent conversational memory is retained outside our private database."),
        ("Data Sovereignty & European Hosting", "The AI microservice (`pdf_service`) is hosted in our private cloud in Europe/Switzerland, ensuring all prompt orchestration stays within European legal jurisdiction.")
    ]
    for it_t, it_d in ciso_clarifications:
        p = tf2.add_paragraph()
        p.text = f"• {it_t} "
        p.font.bold = True
        p.font.size = Pt(10)
        p.font.color.rgb = EMERALD
        p.space_before = Pt(8)
        run = p.add_run()
        run.text = it_d
        run.font.bold = False
        run.font.color.rgb = SLATE_TEXT

    add_footer(s6)

    # -------------------------------------------------------------
    # SLIDE 7: Vectorized Chunks & RAG Engine (Qdrant & Embeddings)
    # -------------------------------------------------------------
    s7 = prs.slides.add_slide(blank_layout)
    set_slide_background(s7, LIGHT_BG)
    add_header(s7, "Vectorized Chunks & RAG: Policy Ingestion Architecture", "KNOWLEDGE ENGINE: HIGH-PRECISION RETRIEVAL WITH ZERO DATA LEAKAGE")

    # 4 Architecture Steps Horizontally
    steps = [
        ("1. PDF Document Upload", "Bank Admin uploads institutional negotiation guidelines, risk manuals, or pricing rules (PDF/Text).", BLUE_PRIMARY),
        ("2. Semantic Text Chunking", "Extracted text is split into semantic paragraphs (preserving chapter headings, points & bilingual tags).", NAVY_DARK),
        ("3. Dense Vector Embedding", "Open-source multilingual embedding model (`MiniLM-L12-v2`) transforms text chunks into 384-dimensional math vectors.", BLUE_ACCENT),
        ("4. Qdrant Vector Storage", "Vectors stored in containerized Qdrant Vector DB with strict tenant metadata (`business_id`). Queried with cross-encoder re-ranking.", EMERALD)
    ]
    for i, (st_t, st_d, st_c) in enumerate(steps):
        x = Inches(0.8 + i * 2.98)
        y = Inches(1.5)
        w = Inches(2.8)
        h = Inches(2.2)

        card = s7.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, x, y, w, h)
        card.fill.solid()
        card.fill.fore_color.rgb = CARD_BG
        card.line.color.rgb = st_c
        card.line.width = Pt(1.5)

        tb = s7.shapes.add_textbox(x + Inches(0.18), y + Inches(0.18), w - Inches(0.36), h - Inches(0.36))
        tf = tb.text_frame
        tf.word_wrap = True

        p = tf.paragraphs[0]
        p.text = st_t
        p.font.size = Pt(11)
        p.font.bold = True
        p.font.color.rgb = st_c

        pd = tf.add_paragraph()
        pd.text = st_d
        pd.font.size = Pt(9.5)
        pd.font.color.rgb = SLATE_TEXT
        pd.space_before = Pt(6)

    # Bottom Half: Two Technical Security Cards on Vector Chunks
    vx1, vy1, vw1, vh1 = Inches(0.8), Inches(3.9), Inches(5.6), Inches(2.8)
    vcard1 = s7.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, vx1, vy1, vw1, vh1)
    vcard1.fill.solid()
    vcard1.fill.fore_color.rgb = CARD_BG
    vcard1.line.color.rgb = BORDER_COLOR

    vtb1 = s7.shapes.add_textbox(vx1 + Inches(0.25), vy1 + Inches(0.2), vw1 - Inches(0.5), vh1 - Inches(0.4))
    vtf1 = vtb1.text_frame
    vtf1.word_wrap = True
    vp1 = vtf1.paragraphs[0]
    vp1.text = "Vector Database Isolation (No Cross-Tenant Retrieval)"
    vp1.font.size = Pt(12)
    vp1.font.bold = True
    vp1.font.color.rgb = NAVY_DARK

    v_points1 = [
        ("Tenant-Scoped Payload Filter", "Every vector query passes a mandatory filter matching the active `business_id`. A search executed by Bank A can mathematically never retrieve chunks from Bank B."),
        ("No Financial PII Vectorization", "Only educational policy texts and fictional negotiation manuals are indexed. Real bank customer accounts are never ingested into the vector DB."),
        ("Self-Hosted Infrastructure", "Qdrant runs as a private microservice, isolated within our secure container network.")
    ]
    for pt, pd in v_points1:
        p = vtf1.add_paragraph()
        p.text = f"• {pt}: "
        p.font.bold = True
        p.font.size = Pt(9)
        p.font.color.rgb = BLUE_PRIMARY
        p.space_before = Pt(4)
        run = p.add_run()
        run.text = pd
        run.font.bold = False
        run.font.color.rgb = SLATE_TEXT

    vx2, vy2, vw2, vh2 = Inches(6.8), Inches(3.9), Inches(5.733), Inches(2.8)
    vcard2 = s7.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, vx2, vy2, vw2, vh2)
    vcard2.fill.solid()
    vcard2.fill.fore_color.rgb = CARD_BG
    vcard2.line.color.rgb = BORDER_COLOR

    vtb2 = s7.shapes.add_textbox(vx2 + Inches(0.25), vy2 + Inches(0.2), vw2 - Inches(0.5), vh2 - Inches(0.4))
    vtf2 = vtb2.text_frame
    vtf2.word_wrap = True
    vp2 = vtf2.paragraphs[0]
    vp2.text = "Mathematical One-Way Nature of Vectors"
    vp2.font.size = Pt(12)
    vp2.font.bold = True
    vp2.font.color.rgb = NAVY_DARK

    v_points2 = [
        ("Non-Reversible Embeddings", "Dense vectors are mathematical points in multi-dimensional space (floating point arrays). They cannot be reverse-engineered to reconstruct original confidential texts without authorized collection access."),
        ("Cross-Encoder Re-Ranking", "Retrieved chunks are scored locally by a Cross-Encoder (`mmarco-mMiniLMv2`) to deliver top-ranked policy precision before answering."),
        ("Instant Purge on Deletion", "When a bank policy is updated or deleted, associated vector points in Qdrant are wiped immediately.")
    ]
    for pt, pd in v_points2:
        p = vtf2.add_paragraph()
        p.text = f"• {pt}: "
        p.font.bold = True
        p.font.size = Pt(9)
        p.font.color.rgb = EMERALD
        p.space_before = Pt(4)
        run = p.add_run()
        run.text = pd
        run.font.bold = False
        run.font.color.rgb = SLATE_TEXT

    add_footer(s7)

    # -------------------------------------------------------------
    # SLIDE 8: Comprehensive Master Data Model & Storage Matrix
    # -------------------------------------------------------------
    s8 = prs.slides.add_slide(blank_layout)
    set_slide_background(s8, LIGHT_BG)
    add_header(s8, "Comprehensive Data Model & Storage Location Matrix", "SECURITY REFERENCE TABLE FOR IT AUDITORS")

    # Table of Data Model & Locations
    table_shape = s8.shapes.add_table(6, 5, Inches(0.8), Inches(1.5), Inches(11.733), Inches(5.1))
    table = table_shape.table

    # Column widths
    table.columns[0].width = Inches(2.2) # Data Category
    table.columns[1].width = Inches(2.8) # Specific Fields
    table.columns[2].width = Inches(2.4) # Storage Tech & Location
    table.columns[3].width = Inches(2.2) # Encryption Standard
    table.columns[4].width = Inches(2.133) # Multi-Tenant Isolation

    headers = ["Data Category", "Data Fields Stored", "Storage Technology & Location", "Encryption Standards", "Tenant Isolation Method"]
    for col_idx, text in enumerate(headers):
        cell = table.cell(0, col_idx)
        cell.fill.solid()
        cell.fill.fore_color.rgb = NAVY_DARK
        tf = cell.text_frame
        tf.word_wrap = True
        p = tf.paragraphs[0]
        p.text = text
        p.font.size = Pt(10)
        p.font.bold = True
        p.font.color.rgb = WHITE
        p.alignment = PP_ALIGN.LEFT

    rows_data = [
        ("Admin Panel Data\n(Tenants & Users)", 
         "Bank profile, admin credentials (bcrypt), staff name, work email, department, role permissions.",
         "MySQL 8.0 Relational DB\nSovereign EU Cloud",
         "In Transit: TLS 1.3\nAt Rest: AES-256 / Bcrypt",
         "Strict `business_id` scoping across all DB queries"),

        ("Local Device Data\n(Browser / Mobile)",
         "Session token (Sanctum/JWT), language preference ('en'/'fr'), UI theme state. ZERO PII.",
         "Client Memory / HTTP-only Cookie\nNo persistent offline DB",
         "In Transit: HTTPS / TLS 1.3\nCookie: Secure, SameSite",
         "Session invalidated on logout or idle timeout"),

        ("Case Study Results &\nAI Feedback",
         "Trainee simulation inputs, AI negotiation analysis, action plans, grading scores, review notes.",
         "MySQL 8.0 (`client_cases`)\nSovereign EU Cloud",
         "In Transit: TLS 1.3\nAt Rest: AES-256",
         "Scoped by user_id & business_id foreign keys"),

        ("Policy Documents &\nVector Chunks",
         "Bank negotiation manuals, credit policy rules, semantic text chunks, 384-dim dense vectors.",
         "Private Qdrant Vector DB +\nLocal Storage for PDFs (EU)",
         "In Transit: TLS 1.3\nAt Rest: AES-256 volume",
         "Qdrant collection payload filter on `business_id`"),

        ("External LLM\nInference Calls",
         "Temporary prompt text containing fictional scenario + policy context. ZERO customer data.",
         "Stateless API Call\nZero Data Retention (ZDR)",
         "In Transit: TLS 1.3\nZero Persistent Vendor Storage",
         "Ephemeral runtime RAM only; no model training")
    ]

    for row_idx, r in enumerate(rows_data, start=1):
        for col_idx, val in enumerate(r):
            cell = table.cell(row_idx, col_idx)
            cell.fill.solid()
            cell.fill.fore_color.rgb = CARD_BG if row_idx % 2 == 1 else RGBColor(241, 245, 249)
            tf = cell.text_frame
            tf.word_wrap = True
            p = tf.paragraphs[0]
            p.text = val
            p.font.size = Pt(9)
            p.font.color.rgb = SLATE_TEXT
            p.alignment = PP_ALIGN.LEFT

    add_footer(s8)

    # -------------------------------------------------------------
    # SLIDE 9: CISO & IT Security FAQ (Addressing Nervous Bank Security Officers)
    # -------------------------------------------------------------
    s9 = prs.slides.add_slide(blank_layout)
    set_slide_background(s9, LIGHT_BG)
    add_header(s9, "Banking CISO FAQ: Security Assurance & Incident Containment", "AUDIT DEFENSE: 5 CRITICAL QUESTIONS FOR IT SECURITY OFFICERS")

    faqs = [
        ("1. Can an attacker move laterally from NegoMaster into our Core Bank network?",
         "IMPOSSIBLE. NegoMaster has ZERO network connectivity to your bank's internal intranet, core banking system (SAB, Temenos, Avaloq), or private VPN. It is an independent SaaS/dedicated environment. Lateral compromise is physically impossible.",
         EMERALD),
        ("2. Does NegoMaster require integration with our Active Directory (AD / Azure AD)?",
         "NO. NegoMaster utilizes its own isolated role-based authentication system with salted Bcrypt password hashing. Staff log in with dedicated credentials, preventing any exposure or compromise of bank domain credentials.",
         BLUE_PRIMARY),
        ("3. Is our bank's internal policy or training data used to train public AI models?",
         "STRICTLY NO. All AI inference is executed under enterprise Zero-Data Retention (ZDR) commercial agreements. Your policy prompts and trainee answers are never saved by model providers and never enter training sets.",
         EMERALD),
        ("4. Where is our data physically stored, and are you GDPR/RGPD compliant?",
         "100% EUROPEAN HOSTING. All application databases, vector stores, and backup volumes reside in EU / Swiss data centers. The platform fully complies with European GDPR, including data residency, deletion rights, and tenant export.",
         NAVY_DARK),
        ("5. What happens to our bank's data if our contract terminates or on audit request?",
         "GUARANTEED IMMEDIATE PURGE. Bank Admins can export all staff records and case results to Excel at any time. Upon contract completion, our team executes a certified cryptographic wipe of all MySQL tables and Qdrant vector vectors.",
         BLUE_ACCENT)
    ]

    for i, (q, a, border_col) in enumerate(faqs):
        y = Inches(1.5 + i * 1.02)
        x = Inches(0.8)
        w = Inches(11.733)
        h = Inches(0.92)

        card = s9.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, x, y, w, h)
        card.fill.solid()
        card.fill.fore_color.rgb = CARD_BG
        card.line.color.rgb = border_col
        card.line.width = Pt(1.2)

        tb = s9.shapes.add_textbox(x + Inches(0.2), y + Inches(0.1), w - Inches(0.4), h - Inches(0.2))
        tf = tb.text_frame
        tf.word_wrap = True

        pq = tf.paragraphs[0]
        pq.text = q
        pq.font.size = Pt(10.5)
        pq.font.bold = True
        pq.font.color.rgb = border_col

        pa = tf.add_paragraph()
        pa.text = a
        pa.font.size = Pt(9.5)
        pa.font.color.rgb = SLATE_TEXT
        pa.space_before = Pt(2)

    add_footer(s9)

    prs.save(filename)
    print(f"Successfully generated PowerPoint presentation: {filename}")

if __name__ == "__main__":
    out_file = sys.argv[1] if len(sys.argv) > 1 else "NegoMaster_Data_Flow_and_Security_Architecture.pptx"
    create_deck(out_file)

