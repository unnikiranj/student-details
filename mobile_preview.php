<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PVPSIT Student Portal - Android App Live Simulator</title>
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        :root {
            --primary: #4F46E5;
            --primary-dark: #3730A3;
            --primary-light: #EEF2FF;
            --secondary: #06B6D4;
            --accent: #F59E0B;
            --success: #10B981;
            --danger: #EF4444;
            --warning: #F59E0B;
            --bg-dark: #0F172A;
            --card-dark: #1E293B;
            --text-dark: #F8FAFC;
            --text-muted: #94A3B8;
            --phone-width: 390px;
            --phone-height: 800px;
            --phone-radius: 48px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background: #0B0F19;
            color: #F1F5F9;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* Top Header Bar */
        header.simulator-header {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding: 14px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 100;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-icon {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, #6366F1, #06B6D4);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
            box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);
        }

        .brand-title {
            font-size: 17px;
            font-weight: 700;
            letter-spacing: -0.3px;
        }

        .brand-subtitle {
            font-size: 12px;
            color: var(--text-muted);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .badge-live {
            background: rgba(16, 185, 129, 0.15);
            color: #34D399;
            border: 1px solid rgba(16, 185, 129, 0.3);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .badge-live::before {
            content: '';
            width: 7px;
            height: 7px;
            background: #10B981;
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(0.95); opacity: 0.7; }
            50% { transform: scale(1.3); opacity: 1; }
            100% { transform: scale(0.95); opacity: 0.7; }
        }

        /* Workspace Grid */
        .simulator-workspace {
            display: flex;
            flex: 1;
            padding: 24px;
            gap: 28px;
            justify-content: center;
            align-items: flex-start;
            max-width: 1440px;
            margin: 0 auto;
            width: 100%;
        }

        /* Phone Container Frame */
        .device-wrapper {
            position: relative;
            perspective: 1000px;
        }

        .phone-frame {
            width: var(--phone-width);
            height: var(--phone-height);
            background: #000;
            border-radius: var(--phone-radius);
            box-shadow: 
                0 0 0 10px #1E293B,
                0 0 0 12px #334155,
                0 30px 60px -15px rgba(0, 0, 0, 0.8),
                0 0 80px rgba(99, 102, 241, 0.15);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            border: 4px solid #0F172A;
        }

        /* Punch Hole Camera */
        .camera-punch {
            position: absolute;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            width: 16px;
            height: 16px;
            background: #0A0A0A;
            border-radius: 50%;
            z-index: 99;
            box-shadow: inset 0 0 2px rgba(255,255,255,0.2);
        }

        /* Android Status Bar */
        .status-bar {
            height: 38px;
            padding: 8px 22px 0 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            font-weight: 600;
            color: #FFFFFF;
            z-index: 50;
            background: transparent;
        }

        .status-icons {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
        }

        /* Phone Screen Content */
        .phone-screen {
            flex: 1;
            background: #F8FAFC;
            color: #0F172A;
            overflow-y: auto;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .phone-screen::-webkit-scrollbar {
            width: 3px;
        }
        .phone-screen::-webkit-scrollbar-thumb {
            background: rgba(0,0,0,0.15);
            border-radius: 4px;
        }

        /* Android Navigation Bar */
        .android-nav-bar {
            height: 20px;
            background: #F8FAFC;
            display: flex;
            justify-content: center;
            align-items: center;
            padding-bottom: 6px;
        }

        .home-pill {
            width: 100px;
            height: 4px;
            background: #CBD5E1;
            border-radius: 10px;
        }

        /* Bottom App Navigation (Android Material 3) */
        .app-bottom-nav {
            background: #FFFFFF;
            border-top: 1px solid #E2E8F0;
            height: 62px;
            display: flex;
            justify-content: space-around;
            align-items: center;
            padding: 0 4px;
            box-shadow: 0 -4px 12px rgba(0,0,0,0.03);
            z-index: 40;
        }

        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 3px;
            color: #64748B;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            padding: 6px 12px;
            border-radius: 16px;
            transition: all 0.2s ease;
        }

        .nav-item.active {
            color: var(--primary);
        }

        .nav-item.active .nav-icon-wrap {
            background: #EEF2FF;
            color: var(--primary);
            padding: 4px 14px;
            border-radius: 14px;
        }

        .nav-icon-wrap {
            font-size: 15px;
            transition: all 0.2s ease;
        }

        /* Screens */
        .screen-view {
            display: none;
            padding: 16px;
            flex: 1;
            animation: fadeIn 0.25s ease;
        }

        .screen-view.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Card Styles */
        .material-card {
            background: #FFFFFF;
            border-radius: 18px;
            padding: 18px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.04);
            border: 1px solid #F1F5F9;
            margin-bottom: 14px;
            transition: transform 0.2s;
        }

        .hero-card {
            background: linear-gradient(135deg, #4F46E5 0%, #3730A3 100%);
            color: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 14px;
            box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.4);
            position: relative;
            overflow: hidden;
        }

        .hero-card::after {
            content: '';
            position: absolute;
            top: -40px;
            right: -40px;
            width: 140px;
            height: 140px;
            background: radial-gradient(circle, rgba(255,255,255,0.18) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%;
        }

        /* Avatar */
        .avatar-img {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255,255,255,0.6);
            background: #E0E7FF;
        }

        /* Stat Rings & Badges */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 14px;
        }

        .stat-box {
            background: #FFFFFF;
            border-radius: 16px;
            padding: 14px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            border: 1px solid #F1F5F9;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
        }

        .stat-icon.purple { background: #EEF2FF; color: #4F46E5; }
        .stat-icon.green { background: #ECFDF5; color: #10B981; }
        .stat-icon.blue { background: #EFF6FF; color: #3B82F6; }
        .stat-icon.amber { background: #FFFBEB; color: #D97706; }

        .stat-val {
            font-size: 18px;
            font-weight: 800;
            color: #0F172A;
        }
        .stat-lbl {
            font-size: 11px;
            color: #64748B;
            font-weight: 600;
        }

        /* Auth Screen */
        .login-hero {
            text-align: center;
            padding: 30px 10px 20px 10px;
        }

        .login-logo {
            width: 68px;
            height: 68px;
            background: linear-gradient(135deg, #4F46E5, #06B6D4);
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
            margin-bottom: 16px;
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.35);
        }

        .input-group-m3 {
            background: #F1F5F9;
            border: 1.5px solid #E2E8F0;
            border-radius: 14px;
            padding: 10px 16px;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s;
        }

        .input-group-m3:focus-within {
            border-color: #4F46E5;
            background: #FFFFFF;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
        }

        .input-group-m3 input {
            border: none;
            background: transparent;
            outline: none;
            font-size: 15px;
            font-weight: 600;
            width: 100%;
            color: #0F172A;
        }

        .btn-m3-primary {
            background: #4F46E5;
            color: white;
            border: none;
            width: 100%;
            padding: 14px;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 6px 18px rgba(79, 70, 229, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: transform 0.15s, background 0.15s;
        }

        .btn-m3-primary:active {
            transform: scale(0.98);
            background: #4338CA;
        }

        .btn-m3-outline {
            background: transparent;
            color: #4F46E5;
            border: 1.5px solid #4F46E5;
            width: 100%;
            padding: 12px;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 10px;
        }

        /* Right Panel: Live REST API Inspector */
        .inspector-panel {
            flex: 1;
            background: #111827;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            height: var(--phone-height);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
        }

        .inspector-header {
            background: #1F2937;
            padding: 16px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .inspector-title {
            font-size: 15px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #E2E8F0;
        }

        .inspector-body {
            flex: 1;
            overflow-y: auto;
            padding: 18px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .log-entry {
            background: #0B0F17;
            border: 1px solid #1E293B;
            border-radius: 12px;
            padding: 12px 14px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
        }

        .log-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .badge-method {
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
        }
        .badge-get { background: #0369A1; color: #E0F2FE; }
        .badge-post { background: #047857; color: #D1FAE5; }

        .json-block {
            background: #070B11;
            padding: 10px;
            border-radius: 8px;
            color: #38BDF8;
            max-height: 180px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-break: break-all;
            font-size: 11.5px;
        }

        /* Quick Switcher Roll Numbers */
        .quick-rolls {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .quick-roll-chip {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            color: #CBD5E1;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .quick-roll-chip:hover {
            background: var(--primary);
            color: white;
        }

        /* Grade Badges */
        .grade-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 8px;
            font-weight: 800;
            font-size: 11px;
        }
        .grade-a-plus { background: #DCFCE7; color: #15803D; }
        .grade-a { background: #EEF2FF; color: #4338CA; }
        .grade-b { background: #FEF3C7; color: #B45309; }
        .grade-f { background: #FEE2E2; color: #B91C1C; }
    </style>
</head>
<body>

    <!-- Simulator Header -->
    <header class="simulator-header">
        <div class="brand-logo">
            <div class="brand-icon"><i class="fab fa-android"></i></div>
            <div>
                <div class="brand-title">PVPSIT Android App Live View</div>
                <div class="brand-subtitle">PHP + MySQL REST API (v1) → Kotlin Android Client Preview</div>
            </div>
        </div>
        <div class="header-actions">
            <div class="badge-live">LIVE REST API CONNECTED</div>
            <a href="android_kotlin/README.md" target="_blank" class="quick-roll-chip" style="text-decoration:none;">
                <i class="fab fa-kotlin me-1"></i> View Kotlin Code
            </a>
        </div>
    </header>

    <!-- Main Simulator Workspace -->
    <div class="simulator-workspace">
        
        <!-- Left: Android Phone Device Frame -->
        <div class="device-wrapper">
            <div class="phone-frame">
                <!-- Camera Punch Hole -->
                <div class="camera-punch"></div>

                <!-- Android Status Bar -->
                <div class="status-bar" id="statusBar">
                    <span id="clockDisplay">12:30</span>
                    <div class="status-icons">
                        <i class="fas fa-signal"></i>
                        <i class="fas fa-wifi"></i>
                        <i class="fas fa-battery-full"></i>
                    </div>
                </div>

                <!-- App Screen Content -->
                <div class="phone-screen" id="phoneScreen">
                    
                    <!-- ==========================================
                         SCREEN 1: LOGIN / OTP AUTHENTICATION
                         ========================================== -->
                    <div class="screen-view active" id="screenLogin">
                        <div class="login-hero">
                            <div class="login-logo">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <h2 style="font-size: 22px; font-weight: 800; color: #0F172A;">PVPSIT Student</h2>
                            <p style="font-size: 13px; color: #64748B; margin-top: 4px;">Sign in with your College Roll Number</p>
                        </div>

                        <div id="loginStep1">
                            <div class="material-card">
                                <label style="font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px; display: block;">STUDENT ROLL NUMBER</label>
                                <div class="input-group-m3">
                                    <i class="fas fa-id-card" style="color: #94A3B8;"></i>
                                    <input type="text" id="inputRollNo" placeholder="e.g. 22501A1267" value="22501A1267" uppercase>
                                </div>

                                <div style="font-size: 11.5px; color: #64748B; margin-bottom: 14px;">
                                    <i class="fas fa-envelope-circle-check text-primary"></i> OTP will be sent to your <span id="hintEmail">rollno@pvpsit.ac.in</span>
                                </div>

                                <button class="btn-m3-primary" id="btnSendOtp" onclick="handleSendOtp()">
                                    <span>Send 6-Digit OTP</span>
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>

                            <div style="padding: 0 4px;">
                                <div style="font-size: 12px; font-weight: 700; color: #94A3B8; margin-bottom: 6px;">QUICK TEST STUDENTS:</div>
                                <div class="quick-rolls">
                                    <span class="quick-roll-chip" onclick="setRoll('22501A1267')">22501A1267 (IT)</span>
                                    <span class="quick-roll-chip" onclick="setRoll('24501A12A7')">24501A12A7 (NPTEL Gold)</span>
                                    <span class="quick-roll-chip" onclick="setRoll('22501A1279')">22501A1279 (Placed)</span>
                                </div>
                            </div>
                        </div>

                        <div id="loginStep2" style="display: none;">
                            <div class="material-card">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 12px;">
                                    <span style="font-size: 12px; font-weight: 700; color: #475569;">ENTER 6-DIGIT OTP</span>
                                    <span style="font-size: 11px; color: var(--primary); cursor:pointer;" onclick="backToStep1()">Change Roll</span>
                                </div>
                                <div class="input-group-m3">
                                    <i class="fas fa-key" style="color: #94A3B8;"></i>
                                    <input type="text" id="inputOtp" placeholder="123456" maxlength="6" style="letter-spacing: 6px; font-size: 20px; text-align: center;">
                                </div>

                                <div id="otpAutoFillNotice" style="background:#ECFDF5; border:1px solid #A7F3D0; color:#065F46; padding:8px 12px; border-radius:10px; font-size:12px; margin-bottom:14px; display:none;">
                                    <i class="fas fa-magic"></i> Auto-filled OTP from API: <strong id="detectedOtp"></strong>
                                </div>

                                <button class="btn-m3-primary" id="btnVerifyOtp" onclick="handleVerifyOtp()">
                                    <span>Verify & Sign In</span>
                                    <i class="fas fa-check-circle"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- ==========================================
                         SCREEN 2: HOME DASHBOARD
                         ========================================== -->
                    <div class="screen-view" id="screenHome">
                        <!-- Hero Banner -->
                        <div class="hero-card">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <div style="font-size: 12px; opacity: 0.8; font-weight: 600;">PVPSIT STUDENT</div>
                                    <h3 style="font-size: 18px; font-weight: 800;" id="homeStudentName">Loading...</h3>
                                    <div style="font-size: 12px; opacity: 0.9; margin-top: 2px;" id="homeRollAndDept">22501A1267 • IT</div>
                                </div>
                                <img src="" id="homeAvatar" class="avatar-img" alt="Student Photo">
                            </div>
                        </div>

                        <!-- Stats Grid -->
                        <div class="stat-grid">
                            <div class="stat-box">
                                <div class="stat-icon purple"><i class="fas fa-chart-line"></i></div>
                                <div>
                                    <div class="stat-val" id="homeCgpa">--</div>
                                    <div class="stat-lbl">CURRENT CGPA</div>
                                </div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-icon green"><i class="fas fa-user-check"></i></div>
                                <div>
                                    <div class="stat-val" id="homeAttPct">--%</div>
                                    <div class="stat-lbl">ATTENDANCE</div>
                                </div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-icon blue"><i class="fas fa-award"></i></div>
                                <div>
                                    <div class="stat-val" id="homeCerts">--</div>
                                    <div class="stat-lbl">NPTEL CERTS</div>
                                </div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-icon amber"><i class="fas fa-briefcase"></i></div>
                                <div>
                                    <div class="stat-val" id="homeOffers">--</div>
                                    <div class="stat-lbl">JOB OFFERS</div>
                                </div>
                            </div>
                        </div>

                        <!-- Today's Birthday Widget -->
                        <div class="material-card" id="birthdayCard" style="background: linear-gradient(135deg, #FFFBEB, #FEF3C7); border-color: #FDE68A;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span style="font-size: 24px;">🎂</span>
                                <div>
                                    <div style="font-size: 13px; font-weight: 800; color: #92400E;">Today's Birthdays!</div>
                                    <div style="font-size: 11.5px; color: #B45309;" id="birthdayText">Checking birthdays...</div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Navigation Actions -->
                        <div class="material-card">
                            <div style="font-size: 13px; font-weight: 800; color: #0F172A; margin-bottom: 10px;">QUICK ACCESS</div>
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; text-align: center;">
                                <div style="background: #F8FAFC; padding: 10px; border-radius: 12px; cursor: pointer;" onclick="switchTab('academics')">
                                    <i class="fas fa-book-open text-primary" style="font-size: 20px;"></i>
                                    <div style="font-size: 11px; font-weight: 700; margin-top: 4px;">Results</div>
                                </div>
                                <div style="background: #F8FAFC; padding: 10px; border-radius: 12px; cursor: pointer;" onclick="switchTab('attendance')">
                                    <i class="fas fa-calendar-check" style="color: #10B981; font-size: 20px;"></i>
                                    <div style="font-size: 11px; font-weight: 700; margin-top: 4px;">Attendance</div>
                                </div>
                                <div style="background: #F8FAFC; padding: 10px; border-radius: 12px; cursor: pointer;" onclick="switchTab('placements')">
                                    <i class="fas fa-trophy" style="color: #F59E0B; font-size: 20px;"></i>
                                    <div style="font-size: 11px; font-weight: 700; margin-top: 4px;">Placements</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ==========================================
                         SCREEN 3: ACADEMICS & RESULTS
                         ========================================== -->
                    <div class="screen-view" id="screenAcademics">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 12px;">
                            <h3 style="font-size: 17px; font-weight: 800;">Academic Results</h3>
                            <span class="badge-method badge-get" id="backlogBadge">0 Backlogs</span>
                        </div>
                        <div id="resultsContent">
                            <div style="text-align:center; padding: 40px; color:#64748B;">Loading semester results...</div>
                        </div>
                    </div>

                    <!-- ==========================================
                         SCREEN 4: ATTENDANCE
                         ========================================== -->
                    <div class="screen-view" id="screenAttendance">
                        <h3 style="font-size: 17px; font-weight: 800; margin-bottom: 12px;">Attendance Tracker</h3>
                        
                        <div class="material-card" id="attendanceOverallCard">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <div>
                                    <div style="font-size: 12px; color: #64748B; font-weight: 700;">OVERALL STATUS</div>
                                    <div style="font-size: 28px; font-weight: 900; color: #4F46E5;" id="attOverallPct">--%</div>
                                    <div style="font-size: 12px; color: #475569;" id="attOverallClasses">-- / -- Classes</div>
                                </div>
                                <div id="attStatusPill" style="padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 800; background: #DCFCE7; color: #15803D;">
                                    Eligible
                                </div>
                            </div>
                        </div>

                        <div style="font-size: 13px; font-weight: 800; color: #0F172A; margin: 16px 0 8px 0;">SUBJECT-WISE BREAKDOWN</div>
                        <div id="attendanceSubjectList">
                            <div style="text-align:center; padding: 20px; color:#64748B;">Loading subjects...</div>
                        </div>
                    </div>

                    <!-- ==========================================
                         SCREEN 5: PLACEMENTS & SKILLS
                         ========================================== -->
                    <div class="screen-view" id="screenPlacements">
                        <h3 style="font-size: 17px; font-weight: 800; margin-bottom: 12px;">Placements & NPTEL</h3>
                        
                        <div id="smartScoreBox" class="material-card" style="background: linear-gradient(135deg, #065F46, #047857); color: white;">
                            <div style="font-size: 11px; opacity: 0.8; font-weight: 700;">SMARTSCORE READINESS</div>
                            <div style="font-size: 24px; font-weight: 900;" id="smartScoreVal">--</div>
                            <div style="font-size: 11.5px; opacity: 0.9;" id="smartScoreRank">College Rank: --</div>
                        </div>

                        <div style="font-size: 13px; font-weight: 800; color: #0F172A; margin: 14px 0 8px 0;">JOB OFFERS (<span id="offerCount">0</span>)</div>
                        <div id="jobOffersList">
                            <div style="text-align:center; padding: 20px; color:#64748B;">Loading job offers...</div>
                        </div>

                        <div style="font-size: 13px; font-weight: 800; color: #0F172A; margin: 14px 0 8px 0;">NPTEL CERTIFICATIONS (<span id="nptelCount">0</span>)</div>
                        <div id="nptelList">
                            <div style="text-align:center; padding: 20px; color:#64748B;">Loading NPTEL certifications...</div>
                        </div>
                    </div>

                    <!-- ==========================================
                         SCREEN 6: PROFILE
                         ========================================== -->
                    <div class="screen-view" id="screenProfile">
                        <div class="material-card" style="text-align:center; padding: 24px 16px;">
                            <img src="" id="profileAvatar" class="avatar-img" style="width: 84px; height: 84px; margin-bottom: 10px;" alt="Profile">
                            <h3 style="font-size: 18px; font-weight: 800;" id="profileName">Student Name</h3>
                            <div style="font-size: 13px; color: #64748B;" id="profileRoll">22501A1267</div>
                        </div>

                        <div class="material-card">
                            <div style="font-size: 12px; font-weight: 800; color: #475569; margin-bottom: 12px;">PERSONAL DETAILS</div>
                            
                            <div style="display:flex; justify-content:space-between; padding: 8px 0; border-bottom: 1px solid #F1F5F9;">
                                <span style="font-size: 12.5px; color: #64748B;">Father's Name</span>
                                <span style="font-size: 12.5px; font-weight: 700;" id="profileFather">--</span>
                            </div>
                            <div style="display:flex; justify-content:space-between; padding: 8px 0; border-bottom: 1px solid #F1F5F9;">
                                <span style="font-size: 12.5px; color: #64748B;">Department</span>
                                <span style="font-size: 12.5px; font-weight: 700;" id="profileDept">--</span>
                            </div>
                            <div style="display:flex; justify-content:space-between; padding: 8px 0;">
                                <span style="font-size: 12.5px; color: #64748B;">Date of Birth</span>
                                <span style="font-size: 12.5px; font-weight: 700; color: var(--primary);" id="profileDob">--</span>
                            </div>
                        </div>

                        <button class="btn-m3-outline" onclick="handleLogout()" style="color:#EF4444; border-color:#EF4444;">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </button>
                    </div>

                </div>

                <!-- Bottom Navigation Bar (Shown when authenticated) -->
                <div class="app-bottom-nav" id="appBottomNav" style="display: none;">
                    <div class="nav-item active" onclick="switchTab('home')">
                        <div class="nav-icon-wrap"><i class="fas fa-home"></i></div>
                        <span>Home</span>
                    </div>
                    <div class="nav-item" onclick="switchTab('academics')">
                        <div class="nav-icon-wrap"><i class="fas fa-graduation-cap"></i></div>
                        <span>Results</span>
                    </div>
                    <div class="nav-item" onclick="switchTab('attendance')">
                        <div class="nav-icon-wrap"><i class="fas fa-chart-pie"></i></div>
                        <span>Attendance</span>
                    </div>
                    <div class="nav-item" onclick="switchTab('placements')">
                        <div class="nav-icon-wrap"><i class="fas fa-briefcase"></i></div>
                        <span>Career</span>
                    </div>
                    <div class="nav-item" onclick="switchTab('profile')">
                        <div class="nav-icon-wrap"><i class="fas fa-user"></i></div>
                        <span>Profile</span>
                    </div>
                </div>

                <!-- Android Home Indicator Pill -->
                <div class="android-nav-bar">
                    <div class="home-pill"></div>
                </div>
            </div>
        </div>

        <!-- Right: Live REST API Inspector -->
        <div class="inspector-panel">
            <div class="inspector-header">
                <div class="inspector-title">
                    <i class="fas fa-terminal text-primary"></i>
                    <span>Live REST API Traffic Inspector</span>
                </div>
                <button class="quick-roll-chip" onclick="clearLogs()">Clear</button>
            </div>
            
            <div class="inspector-body" id="inspectorLogs">
                <div class="log-entry">
                    <div class="log-meta">
                        <span class="badge-method badge-get">READY</span>
                        <span style="color:#64748B;">Listening on /api/v1/</span>
                    </div>
                    <div style="color: #94A3B8;">Ready for mobile requests. Sign in using the Android simulator on the left to see live REST API transactions.</div>
                </div>
            </div>
        </div>

    </div>

    <!-- JavaScript Controller -->
    <script>
        // State
        let currentAuthToken = null;
        let activeStudent = null;
        let detectedOtpValue = null;

        // Update clock
        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const mins = String(now.getMinutes()).padStart(2, '0');
            document.getElementById('clockDisplay').textContent = `${hours}:${mins}`;
        }
        setInterval(updateClock, 1000);
        updateClock();

        function setRoll(roll) {
            document.getElementById('inputRollNo').value = roll;
            document.getElementById('hintEmail').textContent = `${roll.toLowerCase()}@pvpsit.ac.in`;
        }

        document.getElementById('inputRollNo').addEventListener('input', function(e) {
            const val = e.target.value.trim().toLowerCase();
            document.getElementById('hintEmail').textContent = val ? `${val}@pvpsit.ac.in` : 'rollno@pvpsit.ac.in';
        });

        function logApiTraffic(method, endpoint, requestData, responseData, statusCode) {
            const container = document.getElementById('inspectorLogs');
            const entry = document.createElement('div');
            entry.className = 'log-entry';
            
            const badgeClass = method === 'GET' ? 'badge-get' : 'badge-post';
            const statusColor = statusCode >= 200 && statusCode < 300 ? '#10B981' : '#EF4444';

            entry.innerHTML = `
                <div class="log-meta">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span class="badge-method ${badgeClass}">${method}</span>
                        <strong style="color:#E2E8F0;">${endpoint}</strong>
                    </div>
                    <span style="color:${statusColor}; font-weight:700;">HTTP ${statusCode}</span>
                </div>
                ${requestData ? `<div style="color:#94A3B8; font-size:11px; margin-bottom:4px;">Request Payload:</div><div class="json-block" style="color:#A78BFA; margin-bottom:6px;">${JSON.stringify(requestData, null, 2)}</div>` : ''}
                <div style="color:#94A3B8; font-size:11px; margin-bottom:4px;">Response JSON:</div>
                <div class="json-block">${JSON.stringify(responseData, null, 2)}</div>
            `;
            container.prepend(entry);
        }

        function clearLogs() {
            document.getElementById('inspectorLogs').innerHTML = '';
        }

        // ==========================================
        // 1. SEND OTP ACTION
        // ==========================================
        async function handleSendOtp() {
            const roll = document.getElementById('inputRollNo').value.trim().toUpperCase();
            if (!roll) {
                alert('Please enter roll number');
                return;
            }

            const btn = document.getElementById('btnSendOtp');
            btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Sending OTP...`;
            btn.disabled = true;

            try {
                const response = await fetch('api/v1/auth/send_otp.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ roll_number: roll })
                });

                const resJson = await response.json();
                logApiTraffic('POST', '/api/v1/auth/send_otp.php', { roll_number: roll }, resJson, response.status);

                if (resJson.status === 'success') {
                    document.getElementById('loginStep1').style.display = 'none';
                    document.getElementById('loginStep2').style.display = 'block';
                    
                    if (resJson.data && resJson.data.otp) {
                        detectedOtpValue = resJson.data.otp;
                        document.getElementById('inputOtp').value = detectedOtpValue;
                        document.getElementById('detectedOtp').textContent = detectedOtpValue;
                        document.getElementById('otpAutoFillNotice').style.display = 'block';
                    }
                } else {
                    alert(resJson.message || 'Failed to send OTP');
                }
            } catch (err) {
                alert('Network Error: ' + err.message);
            } finally {
                btn.innerHTML = `<span>Send 6-Digit OTP</span> <i class="fas fa-arrow-right"></i>`;
                btn.disabled = false;
            }
        }

        function backToStep1() {
            document.getElementById('loginStep2').style.display = 'none';
            document.getElementById('loginStep1').style.display = 'block';
        }

        // ==========================================
        // 2. VERIFY OTP & GET BEARER TOKEN
        // ==========================================
        async function handleVerifyOtp() {
            const roll = document.getElementById('inputRollNo').value.trim().toUpperCase();
            const otp = document.getElementById('inputOtp').value.trim();

            if (!otp || otp.length !== 6) {
                alert('Please enter 6-digit OTP');
                return;
            }

            const btn = document.getElementById('btnVerifyOtp');
            btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Verifying...`;
            btn.disabled = true;

            try {
                const response = await fetch('api/v1/auth/verify_otp.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ roll_number: roll, otp: otp })
                });

                const resJson = await response.json();
                logApiTraffic('POST', '/api/v1/auth/verify_otp.php', { roll_number: roll, otp: otp }, resJson, response.status);

                if (resJson.status === 'success') {
                    currentAuthToken = resJson.data.auth.token;
                    activeStudent = resJson.data.student;

                    // Show Bottom Nav
                    document.getElementById('appBottomNav').style.display = 'flex';
                    
                    // Switch to Home screen
                    switchTab('home');

                    // Populate all screens with real data
                    loadAllStudentData();
                } else {
                    alert(resJson.message || 'Verification failed');
                }
            } catch (err) {
                alert('Error: ' + err.message);
            } finally {
                btn.innerHTML = `<span>Verify & Sign In</span> <i class="fas fa-check-circle"></i>`;
                btn.disabled = false;
            }
        }

        // ==========================================
        // LOAD DATA VIA REST API
        // ==========================================
        async function loadAllStudentData() {
            loadProfile();
            loadResults();
            loadAttendance();
            loadPlacementsAndNptel();
            loadBirthdays();
        }

        async function loadProfile() {
            try {
                const res = await fetch('api/v1/student/profile.php', {
                    headers: { 'Authorization': `Bearer ${currentAuthToken}` }
                });
                const json = await res.json();
                logApiTraffic('GET', '/api/v1/student/profile.php', null, json, res.status);

                if (json.status === 'success') {
                    const s = json.data;
                    document.getElementById('homeStudentName').textContent = s.student_name || 'Student';
                    document.getElementById('homeRollAndDept').textContent = `${s.roll_no} • ${s.department || 'PVPSIT'}`;
                    document.getElementById('homeCgpa').textContent = s.academic_summary?.latest_cgpa ? s.academic_summary.latest_cgpa.toFixed(2) : 'N/A';
                    document.getElementById('homeAttPct').textContent = `${s.attendance_summary?.percentage || 0}%`;

                    const defaultAvatar = `https://ui-avatars.com/api/?name=${encodeURIComponent(s.student_name)}&background=6366F1&color=fff`;
                    const avatarUrl = s.photo_url ? s.photo_url : defaultAvatar;
                    document.getElementById('homeAvatar').src = avatarUrl;
                    document.getElementById('profileAvatar').src = avatarUrl;
                    
                    document.getElementById('profileName').textContent = s.student_name;
                    document.getElementById('profileRoll').textContent = s.roll_no;
                    document.getElementById('profileFather').textContent = s.father_name || 'N/A';
                    document.getElementById('profileDept').textContent = s.department || 'N/A';
                    document.getElementById('profileDob').textContent = s.formatted_dob || 'Not Set';
                }
            } catch (e) {
                console.error(e);
            }
        }

        async function loadResults() {
            try {
                const res = await fetch('api/v1/student/results.php', {
                    headers: { 'Authorization': `Bearer ${currentAuthToken}` }
                });
                const json = await res.json();
                logApiTraffic('GET', '/api/v1/student/results.php', null, json, res.status);

                if (json.status === 'success') {
                    const data = json.data;
                    document.getElementById('backlogBadge').textContent = `${data.backlogs_count} Backlogs`;
                    if (data.backlogs_count > 0) {
                        document.getElementById('backlogBadge').className = 'badge-method badge-post';
                        document.getElementById('backlogBadge').style.background = '#EF4444';
                    }

                    const container = document.getElementById('resultsContent');
                    container.innerHTML = '';

                    if (!data.semesters || data.semesters.length === 0) {
                        container.innerHTML = '<div class="material-card" style="text-align:center; color:#64748B;">No semester results published yet.</div>';
                        return;
                    }

                    data.semesters.forEach(sem => {
                        let subRows = '';
                        if (sem.subjects && sem.subjects.length > 0) {
                            sem.subjects.forEach(sub => {
                                let gradeClass = 'grade-a';
                                if (sub.grade === 'A+' || sub.grade === 'O') gradeClass = 'grade-a-plus';
                                else if (sub.grade === 'B' || sub.grade === 'C') gradeClass = 'grade-b';
                                else if (sub.grade === 'F' || sub.grade === 'AB') gradeClass = 'grade-f';

                                subRows += `
                                    <div style="display:flex; justify-content:space-between; align-items:center; padding: 6px 0; border-bottom:1px solid #F8FAFC;">
                                        <div style="font-size:11.5px; font-weight:600; color:#1E293B; max-width:180px;">${sub.subject_name || sub.subject_code}</div>
                                        <div style="display:flex; gap:8px; align-items:center;">
                                            <span style="font-size:11px; color:#64748B;">${sub.credits || 0} CR</span>
                                            <span class="grade-badge ${gradeClass}">${sub.grade}</span>
                                        </div>
                                    </div>
                                `;
                            });
                        }

                        const semCard = document.createElement('div');
                        semCard.className = 'material-card';
                        semCard.innerHTML = `
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                                <div style="font-weight:800; font-size:14px; color:#0F172A;">${sem.semester || sem.semester_info || 'Semester'}</div>
                                <div style="display:flex; gap:6px;">
                                    <span style="background:#EEF2FF; color:#4F46E5; padding:2px 8px; border-radius:6px; font-weight:800; font-size:11px;">SGPA: ${sem.sgpa || 'N/A'}</span>
                                    <span style="background:#DCFCE7; color:#15803D; padding:2px 8px; border-radius:6px; font-weight:800; font-size:11px;">CGPA: ${sem.cgpa || 'N/A'}</span>
                                </div>
                            </div>
                            <div>${subRows}</div>
                        `;
                        container.appendChild(semCard);
                    });
                }
            } catch (e) {
                console.error(e);
            }
        }

        async function loadAttendance() {
            try {
                const res = await fetch('api/v1/student/attendance.php', {
                    headers: { 'Authorization': `Bearer ${currentAuthToken}` }
                });
                const json = await res.json();
                logApiTraffic('GET', '/api/v1/student/attendance.php', null, json, res.status);

                if (json.status === 'success') {
                    const data = json.data;
                    document.getElementById('attOverallPct').textContent = `${data.overall.percentage}%`;
                    document.getElementById('attOverallClasses').textContent = `${data.overall.present} / ${data.overall.total} Classes`;

                    const pill = document.getElementById('attStatusPill');
                    if (data.overall.percentage >= 75) {
                        pill.textContent = 'Eligible (>= 75%)';
                        pill.style.background = '#DCFCE7';
                        pill.style.color = '#15803D';
                    } else if (data.overall.percentage >= 65) {
                        pill.textContent = 'Condonation (65-75%)';
                        pill.style.background = '#FEF3C7';
                        pill.style.color = '#B45309';
                    } else {
                        pill.textContent = 'Danger (< 65%)';
                        pill.style.background = '#FEE2E2';
                        pill.style.color = '#B91C1C';
                    }

                    const subContainer = document.getElementById('attendanceSubjectList');
                    subContainer.innerHTML = '';

                    if (!data.subjects || data.subjects.length === 0) {
                        subContainer.innerHTML = '<div class="material-card" style="text-align:center; color:#64748B;">No subject attendance records found.</div>';
                        return;
                    }

                    data.subjects.forEach(s => {
                        const card = document.createElement('div');
                        card.className = 'material-card';
                        card.style.padding = '12px 14px';
                        card.style.marginBottom = '8px';
                        card.innerHTML = `
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <div>
                                    <div style="font-size:12.5px; font-weight:700; color:#0F172A;">${s.subject_name}</div>
                                    <div style="font-size:11px; color:#64748B;">${s.subject_code} • ${s.present}/${s.total} attended</div>
                                </div>
                                <div style="font-size:14px; font-weight:800; color:${s.percentage >= 75 ? '#10B981' : '#EF4444'};">
                                    ${s.percentage}%
                                </div>
                            </div>
                        `;
                        subContainer.appendChild(card);
                    });
                }
            } catch (e) {
                console.error(e);
            }
        }

        async function loadPlacementsAndNptel() {
            // Smartscore
            try {
                const sRes = await fetch('api/v1/student/smartscore.php', {
                    headers: { 'Authorization': `Bearer ${currentAuthToken}` }
                });
                const sJson = await sRes.json();
                if (sJson.status === 'success' && sJson.data && sJson.data.available) {
                    document.getElementById('smartScoreVal').textContent = `${sJson.data.overall_score || 0}/100`;
                    document.getElementById('smartScoreRank').textContent = `College Rank #${sJson.data.rank || 0}`;
                } else {
                    document.getElementById('smartScoreVal').textContent = 'Pending';
                    document.getElementById('smartScoreRank').textContent = 'Assessment In Progress';
                }
            } catch (e) {}

            // Job Offers
            try {
                const pRes = await fetch('api/v1/student/placements.php', {
                    headers: { 'Authorization': `Bearer ${currentAuthToken}` }
                });
                const pJson = await pRes.json();
                logApiTraffic('GET', '/api/v1/student/placements.php', null, pJson, pRes.status);

                if (pJson.status === 'success') {
                    const offers = pJson.data.offers || [];
                    document.getElementById('offerCount').textContent = offers.length;
                    document.getElementById('homeOffers').textContent = offers.length;

                    const container = document.getElementById('jobOffersList');
                    container.innerHTML = '';

                    if (offers.length === 0) {
                        container.innerHTML = '<div class="material-card" style="text-align:center; color:#64748B;">No placement offers yet.</div>';
                    } else {
                        offers.forEach(o => {
                            const card = document.createElement('div');
                            card.className = 'material-card';
                            card.innerHTML = `
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <div>
                                        <div style="font-size:14px; font-weight:800; color:#0F172A;">${o.company_name}</div>
                                        <div style="font-size:11.5px; color:#64748B;">${o.job_role || 'Software Engineer'}</div>
                                    </div>
                                    <div style="font-size:14px; font-weight:900; color:#10B981; background:#ECFDF5; padding:4px 10px; border-radius:10px;">
                                        ${o.package_lpa} LPA
                                    </div>
                                </div>
                            `;
                            container.appendChild(card);
                        });
                    }
                }
            } catch (e) {}

            // NPTEL
            try {
                const nRes = await fetch('api/v1/student/nptel.php', {
                    headers: { 'Authorization': `Bearer ${currentAuthToken}` }
                });
                const nJson = await nRes.json();
                logApiTraffic('GET', '/api/v1/student/nptel.php', null, nJson, nRes.status);

                if (nJson.status === 'success') {
                    const certs = nJson.data.certificates || [];
                    document.getElementById('nptelCount').textContent = certs.length;
                    document.getElementById('homeCerts').textContent = certs.length;

                    const container = document.getElementById('nptelList');
                    container.innerHTML = '';

                    if (certs.length === 0) {
                        container.innerHTML = '<div class="material-card" style="text-align:center; color:#64748B;">No NPTEL certifications uploaded.</div>';
                    } else {
                        certs.forEach(c => {
                            const card = document.createElement('div');
                            card.className = 'material-card';
                            card.innerHTML = `
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <div>
                                        <div style="font-size:13px; font-weight:800; color:#0F172A;">${c.course_name}</div>
                                        <div style="font-size:11px; color:#64748B;">Score: ${c.score_percentage}%</div>
                                    </div>
                                    <div style="font-size:11px; font-weight:800; background:#EEF2FF; color:#4F46E5; padding:4px 8px; border-radius:8px;">
                                        ${c.grade_label || 'Certified'}
                                    </div>
                                </div>
                            `;
                            container.appendChild(card);
                        });
                    }
                }
            } catch (e) {}
        }

        async function loadBirthdays() {
            try {
                const res = await fetch('api/v1/birthdays/today.php');
                const json = await res.json();
                if (json.status === 'success' && json.data.count > 0) {
                    const names = json.data.students.map(s => s.student_name).slice(0, 2).join(', ');
                    document.getElementById('birthdayText').textContent = `${names} + ${json.data.count} celebrating today!`;
                } else {
                    document.getElementById('birthdayText').textContent = 'No birthdays today';
                }
            } catch (e) {}
        }

        // ==========================================
        // TAB SWITCHER
        // ==========================================
        function switchTab(tabId) {
            document.querySelectorAll('.screen-view').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));

            if (tabId === 'home') {
                document.getElementById('screenHome').classList.add('active');
                document.querySelectorAll('.nav-item')[0].classList.add('active');
            } else if (tabId === 'academics') {
                document.getElementById('screenAcademics').classList.add('active');
                document.querySelectorAll('.nav-item')[1].classList.add('active');
            } else if (tabId === 'attendance') {
                document.getElementById('screenAttendance').classList.add('active');
                document.querySelectorAll('.nav-item')[2].classList.add('active');
            } else if (tabId === 'placements') {
                document.getElementById('screenPlacements').classList.add('active');
                document.querySelectorAll('.nav-item')[3].classList.add('active');
            } else if (tabId === 'profile') {
                document.getElementById('screenProfile').classList.add('active');
                document.querySelectorAll('.nav-item')[4].classList.add('active');
            }
        }

        // ==========================================
        // LOGOUT
        // ==========================================
        async function handleLogout() {
            if (currentAuthToken) {
                try {
                    await fetch('api/v1/auth/logout.php', {
                        method: 'POST',
                        headers: { 'Authorization': `Bearer ${currentAuthToken}` }
                    });
                } catch (e) {}
            }
            currentAuthToken = null;
            activeStudent = null;
            document.getElementById('appBottomNav').style.display = 'none';
            document.querySelectorAll('.screen-view').forEach(s => s.classList.remove('active'));
            document.getElementById('screenLogin').classList.add('active');
            backToStep1();
        }
    </script>
</body>
</html>
