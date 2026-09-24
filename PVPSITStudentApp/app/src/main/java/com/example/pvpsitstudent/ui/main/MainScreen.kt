package com.example.pvpsitstudent.ui.main

import android.content.Context
import android.content.ClipboardManager
import android.content.ClipData
import android.widget.Toast
import android.net.Uri
import android.util.Log
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.fragment.app.FragmentActivity
import com.example.pvpsitstudent.security.BiometricAuthHelper
import androidx.compose.animation.*
import androidx.compose.foundation.Canvas
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.verticalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.blur
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.shadow
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.geometry.Size
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.StrokeCap
import androidx.compose.ui.graphics.drawscope.Stroke
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.navigation3.runtime.NavKey
import coil.compose.AsyncImage
import com.example.pvpsitstudent.data.local.SessionManager
import com.example.pvpsitstudent.data.models.*
import com.example.pvpsitstudent.data.network.ApiClient
import com.example.pvpsitstudent.data.repository.StudentRepository
import com.example.pvpsitstudent.data.local.AppDatabase
import com.example.pvpsitstudent.notifications.AttendanceReminderScheduler
import com.example.pvpsitstudent.notifications.AttendanceReminderReceiver
import com.example.pvpsitstudent.theme.*
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import java.io.File
import java.io.FileOutputStream

enum class AppTab(val title: String, val iconEmoji: String) {
    HOME("Home", "🏠"),
    RESULTS("Results", "🎓"),
    ATTENDANCE("Attendance", "📊"),
    CAREER("Career", "💼"),
    PROFILE("Profile", "👤")
}

fun getFullPhotoUrl(photo: String?): String? {
    if (photo.isNullOrBlank()) return null
    return if (photo.startsWith("http://") || photo.startsWith("https://")) {
        photo
    } else {
        val base = ApiClient.currentBaseUrl.trimEnd('/')
        val path = photo.trimStart('/')
        "$base/$path"
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun MainScreen(
    onItemClick: (NavKey) -> Unit = {},
    modifier: Modifier = Modifier
) {
    val context = LocalContext.current
    val sessionManager = remember { SessionManager(context) }
    
    // Active Searched Student Roll Number State
    val savedRoll = remember { sessionManager.getRollNumber() }
    var activeRoll by remember { mutableStateOf<String?>(savedRoll) }
    var rollInput by remember { mutableStateOf(savedRoll ?: "22501A1267") }

    var currentStudent by remember { mutableStateOf<StudentProfile?>(sessionManager.getStudentProfile()) }
    val db = remember { AppDatabase.getDatabase(context) }
    var repository by remember { mutableStateOf(StudentRepository(ApiClient.getService(), db.studentDao())) }
    val scope = rememberCoroutineScope()
    var currentFetchJob by remember { mutableStateOf<kotlinx.coroutines.Job?>(null) }

    // Loading & Error State
    var isLoading by remember { mutableStateOf(false) }
    var isRefreshing by remember { mutableStateOf(false) }
    var errorMessage by remember { mutableStateOf<String?>(null) }

    // OTP-Secured Profile Edit Dialog State
    var showEditProfileDialog by remember { mutableStateOf(false) }
    var editOtpStep by remember { mutableStateOf(1) } // 1: Send OTP, 2: Verify OTP, 3: Edit Photo & DOB
    var editOtpInput by remember { mutableStateOf("") }
    var editDetectedOtp by remember { mutableStateOf<String?>(null) }
    var editDobInput by remember { mutableStateOf(currentStudent?.dateOfBirth ?: "") }
    var isOtpVerifying by remember { mutableStateOf(false) }
    var isSavingProfile by remember { mutableStateOf(false) }
    var profileEditMessage by remember { mutableStateOf<String?>(null) }
    var profileEditError by remember { mutableStateOf<String?>(null) }

    // Server URL Config State
    val savedServerUrl = remember { sessionManager.getServerUrl() ?: ApiClient.DEFAULT_USB_URL }
    remember(savedServerUrl) {
        ApiClient.setBaseUrl(savedServerUrl)
    }
    var showServerDialog by remember { mutableStateOf(false) }
    var currentUrlState by remember { mutableStateOf(ApiClient.currentBaseUrl) }
    var customUrlInput by remember { mutableStateOf(ApiClient.currentBaseUrl) }
    var isTestingConnection by remember { mutableStateOf(false) }
    var connectionTestStatus by remember { mutableStateOf<String?>(null) }

    // Portal Data State
    var selectedTab by remember { mutableStateOf(AppTab.HOME) }
    var fullResults by remember { mutableStateOf<FullResultsResponse?>(null) }
    var fullAttendance by remember { mutableStateOf<FullAttendanceResponse?>(null) }
    var smartScore by remember { mutableStateOf<SmartScoreResponse?>(null) }
    var placements by remember { mutableStateOf<PlacementsResponse?>(null) }
    var nptelList by remember { mutableStateOf<NptelListResponse?>(null) }
    var birthdays by remember { mutableStateOf<BirthdaysResponse?>(null) }
    var midmarksData by remember { mutableStateOf<MidmarksResponse?>(null) }

    // Results Sub-Tab State (0: Semester Results, 1: Mid-Term Exam Marks)
    var selectedResultsSubTab by remember { mutableStateOf(0) }

    // Attendance Screen Semester Filter State
    var selectedAttendanceSemester by remember { mutableStateOf("All Semesters") }

    // Interactive Attendance What-If Simulator State
    var simAttendMore by remember { mutableStateOf(0) }
    var simMissMore by remember { mutableStateOf(0) }

    // Daily Attendance Reminder & Biometric Preference State
    var isAttendanceReminderOn by remember { mutableStateOf(sessionManager.isReminderEnabled()) }
    var isBiometricOn by remember { mutableStateOf(sessionManager.isBiometricEnabled()) }

    fun updateServerUrl(newUrl: String) {
        ApiClient.setBaseUrl(newUrl)
        sessionManager.saveServerUrl(newUrl)
        currentUrlState = ApiClient.currentBaseUrl
        repository = StudentRepository(ApiClient.getService(), db.studentDao())
    }

    fun loadStudentData(roll: String) {
        val cleanRoll = roll.trim().uppercase()
        if (cleanRoll.isBlank()) return
        
        currentFetchJob?.cancel()

        currentFetchJob = scope.launch {
            isLoading = true
            isRefreshing = true
            errorMessage = null

            Log.d("PVPSIT", "Public Search: Loading data for roll: $cleanRoll")

            launch {
                repository.getProfile(cleanRoll).collect { result ->
                    result.fold(
                        onSuccess = { profile ->
                            Log.d("PVPSIT", "Profile loaded: ${profile.studentName}")
                            currentStudent = profile
                            editDobInput = profile.dateOfBirth ?: ""
                            activeRoll = cleanRoll
                            sessionManager.saveRollNumber(cleanRoll)
                            sessionManager.saveStudentProfile(profile)
                        },
                        onFailure = { err ->
                            Log.e("PVPSIT", "Profile error: ${err.message}")
                            val isConnErr = err.message?.contains("connect", ignoreCase = true) == true ||
                                            err.message?.contains("timeout", ignoreCase = true) == true ||
                                            err.message?.contains("failed", ignoreCase = true) == true
                            if (isConnErr) {
                                val detected = ApiClient.autoDetectWorkingUrl()
                                if (detected != null && detected != currentUrlState) {
                                    Log.d("PVPSIT", "Auto-failover switched to: $detected")
                                    updateServerUrl(detected)
                                    loadStudentData(cleanRoll)
                                    return@fold
                                }
                            }
                            if (currentStudent == null) {
                                errorMessage = err.message ?: "Student roll number not found"
                            }
                        }
                    )
                }
            }

            launch {
                repository.getResults(cleanRoll).collect { result ->
                    result.onSuccess {
                        Log.d("PVPSIT", "Results loaded: ${it.semesters.size} sem, CGPA: ${it.cgpa}")
                        fullResults = it
                    }.onFailure { Log.e("PVPSIT", "Results error: ${it.message}") }
                }
            }

            launch {
                repository.getAttendance(cleanRoll).collect { result ->
                    result.onSuccess {
                        Log.d("PVPSIT", "Attendance loaded: ${it.overall.percentage}%, Latest Month: ${it.latestMonth?.month}")
                        fullAttendance = it
                    }.onFailure { Log.e("PVPSIT", "Attendance error: ${it.message}") }
                }
            }

            launch {
                repository.getSmartScore(cleanRoll).onSuccess { smartScore = it }
            }
            launch {
                repository.getPlacements(cleanRoll).onSuccess { placements = it }
            }
            launch {
                repository.getNptelCertificates(cleanRoll).onSuccess { nptelList = it }
            }
            launch {
                repository.getTodayBirthdays().onSuccess { birthdays = it }
            }
            launch {
                repository.getMidmarks(cleanRoll).onSuccess { midmarksData = it }
            }
            
            kotlinx.coroutines.delay(500) // Give UI time to render cache before turning off loading
            isLoading = false
            isRefreshing = false
        }
    }

    // Photo Picker Activity Launcher
    val photoPickerLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.GetContent()
    ) { uri: Uri? ->
        if (uri != null && activeRoll != null) {
            scope.launch {
                isSavingProfile = true
                profileEditMessage = null
                profileEditError = null
                try {
                    val tempFile = withContext(Dispatchers.IO) {
                        val file = File(context.cacheDir, "temp_avatar_${System.currentTimeMillis()}.jpg")
                        context.contentResolver.openInputStream(uri)?.use { input ->
                            FileOutputStream(file).use { output ->
                                input.copyTo(output)
                            }
                        }
                        file
                    }

                    repository.uploadProfilePhoto(tempFile, activeRoll).fold(
                        onSuccess = { res ->
                            profileEditMessage = "Profile photo updated successfully!"
                            activeRoll?.let { loadStudentData(it) }
                        },
                        onFailure = { err ->
                            profileEditError = err.message ?: "Failed to upload photo"
                        }
                    )
                } catch (e: Exception) {
                    profileEditError = "Error processing image: ${e.message}"
                } finally {
                    isSavingProfile = false
                }
            }
        }
    }

    // Auto-load last searched student on launch
    LaunchedEffect(activeRoll, currentUrlState) {
        val target = activeRoll
        if (!target.isNullOrBlank()) {
            loadStudentData(target)
        }
    }

    // Server Config Dialog
    if (showServerDialog) {
        AlertDialog(
            onDismissRequest = { 
                showServerDialog = false
                connectionTestStatus = null
            },
            title = { 
                Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    Text("🌐", fontSize = 20.sp)
                    Text("Server Connection Settings", fontWeight = FontWeight.Bold)
                }
            },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    Text(
                        text = "Current: $currentUrlState",
                        fontSize = 11.5.sp,
                        fontWeight = FontWeight.Bold,
                        color = MaterialTheme.colorScheme.primaryContainer
                    )

                    // Auto Detect Button
                    Button(
                        onClick = {
                            scope.launch {
                                isTestingConnection = true
                                connectionTestStatus = "Searching for reachable server..."
                                val detected = ApiClient.autoDetectWorkingUrl()
                                if (detected != null) {
                                    updateServerUrl(detected)
                                    connectionTestStatus = "✅ Connected to: $detected"
                                    activeRoll?.let { loadStudentData(it) }
                                } else {
                                    connectionTestStatus = "❌ No server found. Ensure XAMPP Apache is running!"
                                }
                                isTestingConnection = false
                            }
                        },
                        modifier = Modifier.fillMaxWidth(),
                        colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.primary),
                        shape = RoundedCornerShape(10.dp),
                        enabled = !isTestingConnection
                    ) {
                        if (isTestingConnection) {
                            CircularProgressIndicator(color = MaterialTheme.colorScheme.onSurface, modifier = Modifier.size(18.dp))
                            Spacer(modifier = Modifier.width(8.dp))
                            Text("Testing candidate URLs...")
                        } else {
                            Text("🔄 Auto-Detect & Test All Connections", fontWeight = FontWeight.Bold)
                        }
                    }

                    HorizontalDivider(color = MaterialTheme.colorScheme.outlineVariant)

                    Text("QUICK CONNECT MODES:", fontSize = 10.5.sp, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onSurfaceVariant)

                    // USB Mode
                    Button(
                        onClick = {
                            updateServerUrl(ApiClient.DEFAULT_USB_URL)
                            connectionTestStatus = "Switched to USB Localhost"
                            activeRoll?.let { loadStudentData(it) }
                        },
                        modifier = Modifier.fillMaxWidth(),
                        colors = ButtonDefaults.buttonColors(
                            containerColor = if (currentUrlState == ApiClient.DEFAULT_USB_URL) Color(0xFF10B981) else MaterialTheme.colorScheme.surfaceVariant
                        ),
                        shape = RoundedCornerShape(10.dp)
                    ) {
                        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                            Text("⚡ USB Mode (localhost)", fontWeight = FontWeight.SemiBold, fontSize = 12.5.sp)
                            if (currentUrlState == ApiClient.DEFAULT_USB_URL) Text("ACTIVE", fontSize = 10.sp, fontWeight = FontWeight.Bold)
                        }
                    }

                    // Wi-Fi Mode
                    Button(
                        onClick = {
                            updateServerUrl(ApiClient.DEFAULT_WIFI_URL)
                            connectionTestStatus = "Switched to Wi-Fi IP"
                            activeRoll?.let { loadStudentData(it) }
                        },
                        modifier = Modifier.fillMaxWidth(),
                        colors = ButtonDefaults.buttonColors(
                            containerColor = if (currentUrlState == ApiClient.DEFAULT_WIFI_URL) Color(0xFF10B981) else MaterialTheme.colorScheme.surfaceVariant
                        ),
                        shape = RoundedCornerShape(10.dp)
                    ) {
                        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                            Text("📶 Wi-Fi Mode (192.168.1.37)", fontWeight = FontWeight.SemiBold, fontSize = 12.5.sp)
                            if (currentUrlState == ApiClient.DEFAULT_WIFI_URL) Text("ACTIVE", fontSize = 10.sp, fontWeight = FontWeight.Bold)
                        }
                    }

                    // Custom URL
                    OutlinedTextField(
                        value = customUrlInput,
                        onValueChange = { customUrlInput = it },
                        label = { Text("Custom Server URL") },
                        placeholder = { Text("http://192.168.x.x/studentdetails/") },
                        modifier = Modifier.fillMaxWidth(),
                        singleLine = true
                    )

                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        OutlinedButton(
                            onClick = {
                                scope.launch {
                                    isTestingConnection = true
                                    val ok = ApiClient.testConnection(customUrlInput)
                                    connectionTestStatus = if (ok) "✅ Custom URL is reachable!" else "❌ Custom URL unreachable"
                                    isTestingConnection = false
                                }
                            },
                            modifier = Modifier.weight(1f),
                            shape = RoundedCornerShape(8.dp)
                        ) {
                            Text("Test URL", fontSize = 12.sp)
                        }

                        Button(
                            onClick = {
                                if (customUrlInput.isNotBlank()) {
                                    updateServerUrl(customUrlInput)
                                    connectionTestStatus = "Applied Custom URL"
                                    activeRoll?.let { loadStudentData(it) }
                                }
                            },
                            modifier = Modifier.weight(1f),
                            shape = RoundedCornerShape(8.dp)
                        ) {
                            Text("Apply URL", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                        }
                    }

                    if (connectionTestStatus != null) {
                        Card(
                            modifier = Modifier.fillMaxWidth(),
                            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant),
                            shape = RoundedCornerShape(8.dp)
                        ) {
                            Text(
                                text = connectionTestStatus!!,
                                fontSize = 11.5.sp,
                                fontWeight = FontWeight.SemiBold,
                                color = if (connectionTestStatus!!.startsWith("✅")) Color(0xFF34D399) else Color(0xFFFBBF24),
                                modifier = Modifier.padding(10.dp)
                            )
                        }
                    }
                }
            },
            confirmButton = {
                TextButton(onClick = { 
                    showServerDialog = false
                    connectionTestStatus = null
                }) {
                    Text("Close")
                }
            }
        )
    }

    // OTP-Secured Profile Edit Dialog
    if (showEditProfileDialog) {
        val studentEmail = currentStudent?.email ?: "${activeRoll?.lowercase()}@pvpsit.ac.in"
        AlertDialog(
            onDismissRequest = { 
                showEditProfileDialog = false
                editOtpStep = 1
                editOtpInput = ""
                profileEditMessage = null
                profileEditError = null
            },
            title = {
                Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    Text(if (editOtpStep == 3) "✏️" else "🔒", fontSize = 20.sp)
                    Text(
                        if (editOtpStep == 3) "Edit Profile Details" else "Verify Email Identity",
                        fontWeight = FontWeight.Bold
                    )
                }
            },
            text = {
                Column(
                    verticalArrangement = Arrangement.spacedBy(14.dp),
                    horizontalAlignment = Alignment.CenterHorizontally,
                    modifier = Modifier.fillMaxWidth()
                ) {
                    if (editOtpStep == 1) {
                        // Step 1: Send OTP to Student Email
                        Text(
                            text = "To protect student privacy, updating photo and Date of Birth requires email OTP verification.",
                            fontSize = 12.5.sp,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                            textAlign = TextAlign.Center
                        )

                        Card(
                            modifier = Modifier.fillMaxWidth(),
                            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.primaryContainer),
                            shape = RoundedCornerShape(12.dp)
                        ) {
                            Column(modifier = Modifier.padding(12.dp)) {
                                Text("REGISTERED COLLEGE EMAIL:", fontSize = 10.sp, color = MaterialTheme.colorScheme.onPrimaryContainer, fontWeight = FontWeight.Bold)
                                Text(studentEmail, fontSize = 13.sp, color = MaterialTheme.colorScheme.onSurface, fontWeight = FontWeight.Bold)
                            }
                        }

                        Button(
                            onClick = {
                                if (activeRoll != null) {
                                    scope.launch {
                                        isOtpVerifying = true
                                        profileEditError = null
                                        repository.sendOtp(activeRoll!!).fold(
                                            onSuccess = { res ->
                                                editOtpInput = "" // Ensure input is completely empty
                                                editOtpStep = 2
                                            },
                                            onFailure = { err ->
                                                profileEditError = err.message ?: "Failed to send OTP"
                                            }
                                        )
                                        isOtpVerifying = false
                                    }
                                }
                            },
                            modifier = Modifier.fillMaxWidth().height(48.dp),
                            colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.primary),
                            shape = RoundedCornerShape(12.dp),
                            enabled = !isOtpVerifying
                        ) {
                            if (isOtpVerifying) {
                                CircularProgressIndicator(color = MaterialTheme.colorScheme.onSurface, modifier = Modifier.size(20.dp))
                            } else {
                                Text("📧 Send OTP to Student Email", fontWeight = FontWeight.Bold)
                            }
                        }
                    } else if (editOtpStep == 2) {
                        // Step 2: Enter & Verify OTP
                        Text(
                            text = "Please check your inbox at $studentEmail and enter the 6-digit verification code below:",
                            fontSize = 12.sp,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                            textAlign = TextAlign.Center
                        )

                        OutlinedTextField(
                            value = editOtpInput,
                            onValueChange = { if (it.length <= 6 && it.all { char -> char.isDigit() }) editOtpInput = it },
                            placeholder = { Text("Enter 6-digit OTP") },
                            modifier = Modifier.fillMaxWidth(),
                            singleLine = true
                        )

                        Button(
                            onClick = {
                                if (editOtpInput.length == 6 && activeRoll != null) {
                                    scope.launch {
                                        isOtpVerifying = true
                                        profileEditError = null
                                        repository.verifyOtp(activeRoll!!, editOtpInput).fold(
                                            onSuccess = {
                                                editOtpStep = 3
                                            },
                                            onFailure = { err ->
                                                profileEditError = err.message ?: "Invalid OTP"
                                            }
                                        )
                                        isOtpVerifying = false
                                    }
                                }
                            },
                            modifier = Modifier.fillMaxWidth().height(48.dp),
                            colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF10B981)),
                            shape = RoundedCornerShape(12.dp),
                            enabled = !isOtpVerifying
                        ) {
                            if (isOtpVerifying) {
                                CircularProgressIndicator(color = MaterialTheme.colorScheme.onSurface, modifier = Modifier.size(20.dp))
                            } else {
                                Text("Verify & Unlock Editor", fontWeight = FontWeight.Bold)
                            }
                        }
                    } else {
                        // Step 3: Edit Photo & DOB (Unlocked)
                        val photoUrl = getFullPhotoUrl(currentStudent?.photoUrl)
                        if (photoUrl != null) {
                            AsyncImage(
                                model = photoUrl,
                                contentDescription = "Avatar",
                                modifier = Modifier
                                    .size(72.dp)
                                    .clip(CircleShape)
                                    .border(2.dp, MaterialTheme.colorScheme.primaryContainer, CircleShape),
                                contentScale = ContentScale.Crop
                            )
                        } else {
                            Box(
                                modifier = Modifier
                                    .size(72.dp)
                                    .clip(CircleShape)
                                    .background(MaterialTheme.colorScheme.primary),
                                contentAlignment = Alignment.Center
                            ) {
                                Text("👤", fontSize = 36.sp)
                            }
                        }

                        Button(
                            onClick = { photoPickerLauncher.launch("image/*") },
                            modifier = Modifier.fillMaxWidth(),
                            colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.primaryContainer),
                            shape = RoundedCornerShape(12.dp),
                            enabled = !isSavingProfile
                        ) {
                            Text("📷 Choose New Photo", fontSize = 13.sp, fontWeight = FontWeight.SemiBold)
                        }

                        HorizontalDivider(color = MaterialTheme.colorScheme.outlineVariant)

                        Column(modifier = Modifier.fillMaxWidth()) {
                            Text("DATE OF BIRTH (YYYY-MM-DD):", fontSize = 10.5.sp, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onSurfaceVariant)
                            Spacer(modifier = Modifier.height(4.dp))
                            OutlinedTextField(
                                value = editDobInput,
                                onValueChange = { editDobInput = it },
                                placeholder = { Text("e.g. 2004-09-20") },
                                modifier = Modifier.fillMaxWidth(),
                                singleLine = true
                            )
                        }

                        Button(
                            onClick = {
                                if (editDobInput.isNotBlank() && activeRoll != null) {
                                    scope.launch {
                                        isSavingProfile = true
                                        profileEditMessage = null
                                        profileEditError = null
                                        repository.updateDateOfBirth(editDobInput.trim(), activeRoll).fold(
                                            onSuccess = {
                                                profileEditMessage = "Date of Birth saved successfully!"
                                                activeRoll?.let { loadStudentData(it) }
                                            },
                                            onFailure = { err ->
                                                profileEditError = err.message ?: "Failed to update DOB"
                                            }
                                        )
                                        isSavingProfile = false
                                    }
                                }
                            },
                            modifier = Modifier.fillMaxWidth(),
                            colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF10B981)),
                            shape = RoundedCornerShape(12.dp),
                            enabled = !isSavingProfile
                        ) {
                            if (isSavingProfile) {
                                CircularProgressIndicator(color = MaterialTheme.colorScheme.onSurface, modifier = Modifier.size(20.dp))
                            } else {
                                Text("💾 Save Date of Birth", fontSize = 13.sp, fontWeight = FontWeight.Bold)
                            }
                        }
                    }

                    if (profileEditMessage != null) {
                        Text(profileEditMessage!!, color = Color(0xFF34D399), fontSize = 12.sp, fontWeight = FontWeight.Bold)
                    }
                    if (profileEditError != null) {
                        Text(profileEditError!!, color = Color(0xFFEF4444), fontSize = 12.sp, fontWeight = FontWeight.Bold)
                    }
                }
            },
            confirmButton = {
                TextButton(onClick = { 
                    showEditProfileDialog = false
                    editOtpStep = 1
                    editOtpInput = ""
                    profileEditMessage = null
                    profileEditError = null
                }) {
                    Text("Close")
                }
            }
        )
    }

    Scaffold(
        topBar = {
            if (activeRoll != null && currentStudent != null) {
                Surface(
                    color = Color(0xFF0B0F19),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .statusBarsPadding()
                            .padding(horizontal = 16.dp, vertical = 10.dp),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.spacedBy(10.dp)
                        ) {
                            Box(
                                modifier = Modifier
                                    .size(38.dp)
                                    .clip(RoundedCornerShape(12.dp))
                                    .background(Brush.linearGradient(listOf(Color(0xFF6366F1), Color(0xFF06B6D4)))),
                                contentAlignment = Alignment.Center
                            ) {
                                Text("🎓", fontSize = 18.sp)
                            }
                            Column {
                                Text(
                                    text = "PVPSIT PORTAL",
                                    fontSize = 13.5.sp,
                                    fontWeight = FontWeight.ExtraBold,
                                    color = Color.White,
                                    letterSpacing = 0.5.sp
                                )
                                Row(
                                    verticalAlignment = Alignment.CenterVertically,
                                    horizontalArrangement = Arrangement.spacedBy(4.dp)
                                ) {
                                    Box(
                                        modifier = Modifier
                                            .size(6.dp)
                                            .clip(CircleShape)
                                            .background(if (isLoading || isRefreshing) Color(0xFFFBBF24) else Color(0xFF10B981))
                                    )
                                    Text(
                                        text = if (isLoading || isRefreshing) "Syncing..." else "⚡ Live Synchronized",
                                        fontSize = 10.sp,
                                        fontWeight = FontWeight.SemiBold,
                                        color = if (isLoading || isRefreshing) Color(0xFFFBBF24) else Color(0xFF34D399)
                                    )
                                }
                            }
                        }

                        Row(
                            horizontalArrangement = Arrangement.spacedBy(6.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            IconButton(
                                onClick = { showServerDialog = true },
                                modifier = Modifier
                                    .size(34.dp)
                                    .clip(CircleShape)
                                    .background(Color(0xFF1E293B))
                            ) {
                                Text("⚙️", fontSize = 14.sp)
                            }

                            IconButton(
                                onClick = { activeRoll?.let { loadStudentData(it) } },
                                modifier = Modifier
                                    .size(34.dp)
                                    .clip(CircleShape)
                                    .background(Color(0xFF1E293B))
                            ) {
                                if (isRefreshing) {
                                    CircularProgressIndicator(
                                        color = Color.White,
                                        modifier = Modifier.size(16.dp),
                                        strokeWidth = 2.dp
                                    )
                                } else {
                                    Text("🔄", fontSize = 14.sp)
                                }
                            }
                        }
                    }
                }
            }
        },
        bottomBar = {
            if (activeRoll != null && currentStudent != null) {
                // FLOATING GLASSMORPHIC BOTTOM NAVIGATION DOCK
                Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .navigationBarsPadding()
                        .padding(horizontal = 20.dp, vertical = 12.dp)
                ) {
                    Box(
                        modifier = Modifier
                            .fillMaxWidth()
                            .shadow(24.dp, RoundedCornerShape(26.dp))
                            .clip(RoundedCornerShape(26.dp))
                            .background(Color(0xFF0F172A).copy(alpha = 0.95f))
                            .border(
                                width = 1.dp,
                                brush = Brush.linearGradient(listOf(Color(0x446366F1), Color(0x3306B6D4))),
                                shape = RoundedCornerShape(26.dp)
                            )
                    ) {
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(horizontal = 8.dp, vertical = 8.dp),
                            horizontalArrangement = Arrangement.SpaceEvenly
                        ) {
                            AppTab.values().forEach { tab ->
                                val isSelected = selectedTab == tab
                                Column(
                                    modifier = Modifier
                                        .weight(1f)
                                        .clip(RoundedCornerShape(18.dp))
                                        .background(
                                            if (isSelected) Brush.verticalGradient(
                                                listOf(Color(0x556366F1), Color(0x2206B6D4))
                                            ) else Brush.verticalGradient(
                                                listOf(Color.Transparent, Color.Transparent)
                                            )
                                        )
                                        .clickable { selectedTab = tab }
                                        .padding(vertical = 8.dp),
                                    horizontalAlignment = Alignment.CenterHorizontally,
                                    verticalArrangement = Arrangement.spacedBy(3.dp)
                                ) {
                                    Text(tab.iconEmoji, fontSize = if (isSelected) 22.sp else 20.sp)
                                    Text(
                                        tab.title,
                                        fontSize = 10.sp,
                                        fontWeight = if (isSelected) FontWeight.ExtraBold else FontWeight.Medium,
                                        color = if (isSelected) Color(0xFF818CF8) else Color(0xFF64748B)
                                    )
                                    // Active glow dot
                                    if (isSelected) {
                                        Box(
                                            modifier = Modifier
                                                .size(4.dp)
                                                .clip(CircleShape)
                                                .background(
                                                    Brush.radialGradient(listOf(Color(0xFF6366F1), Color(0xFF06B6D4)))
                                                )
                                        )
                                    }
                                }
                            }
                        }
                    }
                }
            }
        },
        containerColor = MaterialTheme.colorScheme.background
    ) { innerPadding ->
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(innerPadding)
        ) {
            if (activeRoll == null || currentStudent == null) {
                // ==========================================
                // ULTRA-PREMIUM SEARCH / WELCOME SCREEN
                // ==========================================
                Box(modifier = Modifier.fillMaxSize()) {
                    // Ambient glow spheres background
                    Canvas(modifier = Modifier.fillMaxSize()) {
                        drawCircle(
                            brush = Brush.radialGradient(
                                colors = listOf(Color(0x306366F1), Color.Transparent),
                                center = Offset(size.width * 0.15f, size.height * 0.2f),
                                radius = size.width * 0.55f
                            ),
                            radius = size.width * 0.55f,
                            center = Offset(size.width * 0.15f, size.height * 0.2f)
                        )
                        drawCircle(
                            brush = Brush.radialGradient(
                                colors = listOf(Color(0x2006B6D4), Color.Transparent),
                                center = Offset(size.width * 0.85f, size.height * 0.6f),
                                radius = size.width * 0.45f
                            ),
                            radius = size.width * 0.45f,
                            center = Offset(size.width * 0.85f, size.height * 0.6f)
                        )
                        drawCircle(
                            brush = Brush.radialGradient(
                                colors = listOf(Color(0x1810B981), Color.Transparent),
                                center = Offset(size.width * 0.5f, size.height * 0.85f),
                                radius = size.width * 0.4f
                            ),
                            radius = size.width * 0.4f,
                            center = Offset(size.width * 0.5f, size.height * 0.85f)
                        )
                    }

                    Column(
                        modifier = Modifier
                            .fillMaxSize()
                            .verticalScroll(rememberScrollState())
                            .padding(horizontal = 24.dp, vertical = 32.dp),
                        horizontalAlignment = Alignment.CenterHorizontally
                    ) {
                        Spacer(modifier = Modifier.height(24.dp))

                        // Glowing Logo
                        Box(contentAlignment = Alignment.Center) {
                            Box(
                                modifier = Modifier
                                    .size(100.dp)
                                    .blur(24.dp)
                                    .background(
                                        Brush.radialGradient(listOf(Color(0x806366F1), Color.Transparent)),
                                        CircleShape
                                    )
                            )
                            Box(
                                modifier = Modifier
                                    .size(88.dp)
                                    .shadow(16.dp, RoundedCornerShape(28.dp))
                                    .clip(RoundedCornerShape(28.dp))
                                    .background(
                                        Brush.linearGradient(
                                            listOf(Color(0xFF6366F1), Color(0xFF06B6D4))
                                        )
                                    ),
                                contentAlignment = Alignment.Center
                            ) {
                                Text("🎓", fontSize = 44.sp)
                            }
                        }

                        Spacer(modifier = Modifier.height(20.dp))

                        Text(
                            text = "PVPSIT STUDENT PORTAL",
                            fontSize = 20.sp,
                            fontWeight = FontWeight.ExtraBold,
                            color = Color.White,
                            letterSpacing = 1.5.sp
                        )
                        Spacer(modifier = Modifier.height(4.dp))
                        Text(
                            text = "Prasad V. Potluri Siddhartha Institute of Technology",
                            fontSize = 11.sp,
                            color = Color(0xFF94A3B8),
                            textAlign = TextAlign.Center
                        )
                        Spacer(modifier = Modifier.height(6.dp))
                        Row(
                            horizontalArrangement = Arrangement.spacedBy(6.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Box(
                                modifier = Modifier
                                    .clip(RoundedCornerShape(6.dp))
                                    .background(Color(0xFF065F46).copy(alpha = 0.7f))
                                    .padding(horizontal = 8.dp, vertical = 3.dp)
                            ) {
                                Text("AUTONOMOUS", fontSize = 9.5.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFF34D399), letterSpacing = 0.5.sp)
                            }
                            Box(
                                modifier = Modifier
                                    .clip(RoundedCornerShape(6.dp))
                                    .background(Color(0xFF312E81).copy(alpha = 0.7f))
                                    .padding(horizontal = 8.dp, vertical = 3.dp)
                            ) {
                                Text("NBA & NAAC A+", fontSize = 9.5.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFFC7D2FE), letterSpacing = 0.5.sp)
                            }
                        }

                        Spacer(modifier = Modifier.height(32.dp))

                        // Glassmorphic Search Card
                        Box(
                            modifier = Modifier
                                .fillMaxWidth()
                                .shadow(20.dp, RoundedCornerShape(28.dp))
                                .clip(RoundedCornerShape(28.dp))
                                .background(Color(0xFF1E293B).copy(alpha = 0.92f))
                                .border(
                                    width = 1.dp,
                                    brush = Brush.linearGradient(listOf(Color(0x556366F1), Color(0x3306B6D4))),
                                    shape = RoundedCornerShape(28.dp)
                                )
                        ) {
                            Column(modifier = Modifier.padding(24.dp)) {
                                Text(
                                    text = "ENTER STUDENT ROLL NUMBER",
                                    fontSize = 10.5.sp,
                                    fontWeight = FontWeight.ExtraBold,
                                    color = Color(0xFF6366F1),
                                    letterSpacing = 1.sp
                                )
                                Spacer(modifier = Modifier.height(10.dp))
                                OutlinedTextField(
                                    value = rollInput,
                                    onValueChange = { rollInput = it.uppercase() },
                                    placeholder = { Text("e.g. 22501A1267", color = Color(0xFF64748B)) },
                                    modifier = Modifier.fillMaxWidth(),
                                    singleLine = true,
                                    shape = RoundedCornerShape(14.dp),
                                    colors = OutlinedTextFieldDefaults.colors(
                                        focusedTextColor = Color.White,
                                        unfocusedTextColor = Color.White,
                                        focusedBorderColor = Color(0xFF6366F1),
                                        unfocusedBorderColor = Color(0xFF334155),
                                        cursorColor = Color(0xFF6366F1)
                                    )
                                )

                                Spacer(modifier = Modifier.height(16.dp))

                                // Gradient Search Button
                                Box(
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .height(54.dp)
                                        .shadow(8.dp, RoundedCornerShape(16.dp))
                                        .clip(RoundedCornerShape(16.dp))
                                        .background(
                                            Brush.horizontalGradient(listOf(Color(0xFF6366F1), Color(0xFF06B6D4)))
                                        )
                                        .clickable {
                                            if (rollInput.isNotBlank()) loadStudentData(rollInput)
                                        },
                                    contentAlignment = Alignment.Center
                                ) {
                                    if (isLoading) {
                                        CircularProgressIndicator(color = Color.White, modifier = Modifier.size(24.dp), strokeWidth = 2.5.dp)
                                    } else {
                                        Row(
                                            verticalAlignment = Alignment.CenterVertically,
                                            horizontalArrangement = Arrangement.spacedBy(8.dp)
                                        ) {
                                            Text("🔍", fontSize = 16.sp)
                                            Text(
                                                "VIEW STUDENT DASHBOARD",
                                                fontWeight = FontWeight.ExtraBold,
                                                fontSize = 13.sp,
                                                color = Color.White,
                                                letterSpacing = 0.5.sp
                                            )
                                        }
                                    }
                                }

                                // Biometric Quick Unlock Card
                                if (savedRoll != null && BiometricAuthHelper.isBiometricAvailable(context) && sessionManager.isBiometricEnabled()) {
                                    Spacer(modifier = Modifier.height(12.dp))
                                    Box(
                                        modifier = Modifier
                                            .fillMaxWidth()
                                            .clip(RoundedCornerShape(16.dp))
                                            .background(Color(0xFF0F172A))
                                            .border(1.dp, Color(0xFF38BDF8).copy(alpha = 0.5f), RoundedCornerShape(16.dp))
                                            .clickable {
                                                (context as? FragmentActivity)?.let { activity ->
                                                    BiometricAuthHelper.promptBiometric(
                                                        activity = activity,
                                                        title = "PVPSIT Portal Unlock",
                                                        subtitle = "Verify fingerprint to unlock $savedRoll",
                                                        onSuccess = {
                                                            rollInput = savedRoll
                                                            loadStudentData(savedRoll)
                                                        },
                                                        onError = { err -> errorMessage = err }
                                                    )
                                                }
                                            }
                                            .padding(14.dp)
                                    ) {
                                        Row(
                                            verticalAlignment = Alignment.CenterVertically,
                                            horizontalArrangement = Arrangement.spacedBy(12.dp)
                                        ) {
                                            Box(
                                                modifier = Modifier
                                                    .size(42.dp)
                                                    .clip(CircleShape)
                                                    .background(Color(0xFF38BDF8).copy(alpha = 0.15f)),
                                                contentAlignment = Alignment.Center
                                            ) {
                                                Text("👆", fontSize = 20.sp)
                                            }
                                            Column(modifier = Modifier.weight(1f)) {
                                                Text("QUICK FINGERPRINT UNLOCK", fontSize = 10.5.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFF38BDF8), letterSpacing = 0.5.sp)
                                                Text(savedRoll, fontSize = 12.sp, fontWeight = FontWeight.Bold, color = Color.White)
                                            }
                                            Text("→", fontSize = 18.sp, color = Color(0xFF38BDF8))
                                        }
                                    }
                                }

                                Spacer(modifier = Modifier.height(20.dp))

                                // Quick Test Rolls as neon pill chips
                                Text("QUICK TEST ROLLS", fontSize = 10.sp, color = Color(0xFF64748B), fontWeight = FontWeight.ExtraBold, letterSpacing = 0.8.sp)
                                Spacer(modifier = Modifier.height(8.dp))
                                Row(
                                    modifier = Modifier.horizontalScroll(rememberScrollState()),
                                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                                ) {
                                    listOf(
                                        Pair("22501A1267", "CSE"),
                                        Pair("24501A12A7", "IT"),
                                        Pair("24501A12B8", "IT"),
                                        Pair("24501A1280", "CSE")
                                    ).forEach { (r, branch) ->
                                        Box(
                                            modifier = Modifier
                                                .clip(RoundedCornerShape(20.dp))
                                                .background(Color(0xFF312E81).copy(alpha = 0.6f))
                                                .border(1.dp, Color(0xFF6366F1).copy(alpha = 0.5f), RoundedCornerShape(20.dp))
                                                .clickable {
                                                    rollInput = r
                                                    loadStudentData(r)
                                                }
                                                .padding(horizontal = 12.dp, vertical = 6.dp)
                                        ) {
                                            Text("$r • $branch", fontSize = 10.5.sp, fontWeight = FontWeight.Bold, color = Color(0xFFC7D2FE))
                                        }
                                    }
                                }

                                if (errorMessage != null) {
                                    Spacer(modifier = Modifier.height(12.dp))
                                    Box(
                                        modifier = Modifier
                                            .fillMaxWidth()
                                            .clip(RoundedCornerShape(10.dp))
                                            .background(Color(0xFF7F1D1D).copy(alpha = 0.5f))
                                            .border(1.dp, Color(0xFFEF4444).copy(alpha = 0.4f), RoundedCornerShape(10.dp))
                                            .padding(12.dp)
                                    ) {
                                        Text(
                                            text = "⚠️ ${errorMessage!!}",
                                            color = Color(0xFFF87171),
                                            fontSize = 12.sp,
                                            textAlign = TextAlign.Center,
                                            modifier = Modifier.fillMaxWidth()
                                        )
                                    }
                                }
                            }
                        }

                        Spacer(modifier = Modifier.height(16.dp))
                        // Server link at bottom
                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            modifier = Modifier
                                .clickable { showServerDialog = true }
                                .padding(6.dp)
                        ) {
                            Text("⚙️ ", fontSize = 11.sp)
                            Text(
                                text = "Server: $currentUrlState",
                                fontSize = 10.5.sp,
                                color = Color(0xFF64748B)
                            )
                        }
                        Spacer(modifier = Modifier.height(32.dp))
                    }
                }
            } else {
                // ==========================================
                // STUDENT PORTAL VIEW (ULTRA-PREMIUM UI)
                // ==========================================
                when (selectedTab) {
                    AppTab.HOME -> {
                        LazyColumn(
                            modifier = Modifier
                                .fillMaxSize()
                                .padding(16.dp),
                            verticalArrangement = Arrangement.spacedBy(16.dp)
                        ) {
                            // ULTRA-PREMIUM DIGITAL STUDENT ID HERO CARD
                            item {
                                Box(
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .shadow(12.dp, RoundedCornerShape(24.dp))
                                        .clip(RoundedCornerShape(24.dp))
                                        .background(
                                            Brush.linearGradient(
                                                listOf(
                                                    MaterialTheme.colorScheme.background,
                                                    MaterialTheme.colorScheme.primaryContainer,
                                                    MaterialTheme.colorScheme.background
                                                )
                                            )
                                        )
                                        .border(
                                            1.dp,
                                            Brush.linearGradient(listOf(MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.5f), Color(0xFF06B6D4).copy(alpha = 0.2f))),
                                            RoundedCornerShape(24.dp)
                                        )
                                ) {
                                    Column(modifier = Modifier.padding(20.dp)) {
                                        // Header Row: Badge + Action Buttons
                                        Row(
                                            modifier = Modifier.fillMaxWidth(),
                                            horizontalArrangement = Arrangement.SpaceBetween,
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            Row(
                                                verticalAlignment = Alignment.CenterVertically,
                                                horizontalArrangement = Arrangement.spacedBy(6.dp),
                                                modifier = Modifier
                                                    .clip(RoundedCornerShape(20.dp))
                                                    .background(MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.5f))
                                                    .padding(horizontal = 10.dp, vertical = 4.dp)
                                            ) {
                                                Text("🏛️", fontSize = 11.sp)
                                                Text("PVPSIT ID CARD", fontSize = 10.5.sp, fontWeight = FontWeight.ExtraBold, color = MaterialTheme.colorScheme.onPrimaryContainer)
                                            }

                                            Row(horizontalArrangement = Arrangement.spacedBy(4.dp), verticalAlignment = Alignment.CenterVertically) {
                                                IconButton(
                                                    onClick = { activeRoll?.let { loadStudentData(it) } },
                                                    modifier = Modifier.size(34.dp)
                                                ) {
                                                    if (isRefreshing) {
                                                        CircularProgressIndicator(color = MaterialTheme.colorScheme.onSurface, modifier = Modifier.size(16.dp))
                                                    } else {
                                                        Text("🔄", fontSize = 16.sp)
                                                    }
                                                }

                                                IconButton(
                                                    onClick = {
                                                        activeRoll = null
                                                        currentStudent = null
                                                    },
                                                    modifier = Modifier.size(34.dp)
                                                ) {
                                                    Text("🔍", fontSize = 16.sp)
                                                }
                                            }
                                        }

                                        Spacer(modifier = Modifier.height(16.dp))

                                        // Student Avatar & Name Info Row
                                        Row(
                                            verticalAlignment = Alignment.CenterVertically,
                                            horizontalArrangement = Arrangement.spacedBy(16.dp),
                                            modifier = Modifier.fillMaxWidth()
                                        ) {
                                            val photoUrl = getFullPhotoUrl(currentStudent?.photoUrl)
                                            Box(
                                                modifier = Modifier
                                                    .size(72.dp)
                                                    .clip(CircleShape)
                                                    .border(2.5.dp, Color(0xFF38BDF8), CircleShape)
                                                    .clickable { showEditProfileDialog = true },
                                                contentAlignment = Alignment.Center
                                            ) {
                                                if (photoUrl != null) {
                                                    AsyncImage(
                                                        model = photoUrl,
                                                        contentDescription = "Avatar",
                                                        modifier = Modifier.fillMaxSize(),
                                                        contentScale = ContentScale.Crop
                                                    )
                                                } else {
                                                    Box(
                                                        modifier = Modifier
                                                            .fillMaxSize()
                                                            .background(MaterialTheme.colorScheme.primary),
                                                        contentAlignment = Alignment.Center
                                                    ) {
                                                        Text("👤", fontSize = 32.sp)
                                                    }
                                                }
                                            }

                                            Column(modifier = Modifier.weight(1f)) {
                                                Row(
                                                    verticalAlignment = Alignment.CenterVertically,
                                                    horizontalArrangement = Arrangement.spacedBy(6.dp)
                                                ) {
                                                    Text(
                                                        text = currentStudent?.studentName ?: "Student Name",
                                                        fontSize = 18.sp,
                                                        fontWeight = FontWeight.ExtraBold,
                                                        color = Color.White,
                                                        lineHeight = 22.sp,
                                                        modifier = Modifier.weight(1f, fill = false)
                                                    )
                                                    Text("✅", fontSize = 13.sp)
                                                }
                                                Spacer(modifier = Modifier.height(4.dp))
                                                Row(
                                                    verticalAlignment = Alignment.CenterVertically,
                                                    horizontalArrangement = Arrangement.spacedBy(6.dp)
                                                ) {
                                                    // 1-Tap Copyable Roll Number Chip
                                                    Row(
                                                        verticalAlignment = Alignment.CenterVertically,
                                                        modifier = Modifier
                                                            .clip(RoundedCornerShape(6.dp))
                                                            .background(Color(0xFF0284C7).copy(alpha = 0.25f))
                                                            .clickable {
                                                                val roll = currentStudent?.rollNo ?: activeRoll ?: ""
                                                                if (roll.isNotBlank()) {
                                                                    val clipboard = context.getSystemService(Context.CLIPBOARD_SERVICE) as? ClipboardManager
                                                                    val clip = ClipData.newPlainText("Roll Number", roll)
                                                                    clipboard?.setPrimaryClip(clip)
                                                                    Toast.makeText(context, "Copied $roll to clipboard! 📋", Toast.LENGTH_SHORT).show()
                                                                }
                                                            }
                                                            .padding(horizontal = 6.dp, vertical = 2.dp)
                                                    ) {
                                                        Text(
                                                            text = currentStudent?.rollNo ?: activeRoll ?: "",
                                                            fontSize = 12.sp,
                                                            fontWeight = FontWeight.Bold,
                                                            color = Color(0xFF38BDF8)
                                                        )
                                                        Spacer(modifier = Modifier.width(3.dp))
                                                        Text("📋", fontSize = 10.sp)
                                                    }

                                                    Text("•", color = Color(0xFF64748B), fontSize = 12.sp)
                                                    Text(
                                                        text = currentStudent?.department ?: "Information Tech",
                                                        fontSize = 12.sp,
                                                        color = Color(0xFFC7D2FE),
                                                        maxLines = 1
                                                    )
                                                }
                                            }
                                        }

                                        Spacer(modifier = Modifier.height(16.dp))
                                        HorizontalDivider(color = Color(0xFF334155))
                                        Spacer(modifier = Modifier.height(10.dp))

                                        // Quick Edit details trigger button
                                        Row(
                                            modifier = Modifier
                                                .fillMaxWidth()
                                                .clip(RoundedCornerShape(8.dp))
                                                .clickable { showEditProfileDialog = true }
                                                .padding(vertical = 4.dp),
                                            horizontalArrangement = Arrangement.SpaceBetween,
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                                                Text("🔒", fontSize = 12.sp)
                                                Text("Edit Photo & DOB (Email OTP Secured)", fontSize = 11.5.sp, color = Color(0xFF818CF8), fontWeight = FontWeight.SemiBold)
                                            }
                                            Text("✏️ Edit", fontSize = 11.5.sp, color = Color(0xFF38BDF8), fontWeight = FontWeight.Bold)
                                        }
                                    }
                                }
                            }

                            // LATEST MONTH ATTENDANCE HIGHLIGHT CARD
                            val latestMonth = fullAttendance?.latestMonth ?: fullAttendance?.monthly?.lastOrNull()
                            item {
                                Card(
                                    modifier = Modifier.fillMaxWidth(),
                                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant),
                                    shape = RoundedCornerShape(20.dp)
                                ) {
                                    Column(modifier = Modifier.padding(18.dp)) {
                                        Row(
                                            modifier = Modifier.fillMaxWidth(),
                                            horizontalArrangement = Arrangement.SpaceBetween,
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                                                Box(
                                                    modifier = Modifier
                                                        .size(38.dp)
                                                        .clip(RoundedCornerShape(10.dp))
                                                        .background(MaterialTheme.colorScheme.primary.copy(alpha = 0.2f)),
                                                    contentAlignment = Alignment.Center
                                                ) {
                                                    Text("📅", fontSize = 18.sp)
                                                }
                                                Column {
                                                    Text(
                                                        text = "LATEST MONTH ATTENDANCE",
                                                        fontSize = 10.5.sp,
                                                        fontWeight = FontWeight.Bold,
                                                        color = MaterialTheme.colorScheme.onSurfaceVariant
                                                    )
                                                    Text(
                                                        text = latestMonth?.month ?: "Active Session",
                                                        fontSize = 15.sp,
                                                        fontWeight = FontWeight.ExtraBold,
                                                        color = MaterialTheme.colorScheme.primaryContainer
                                                    )
                                                }
                                            }
                                            val mPct = latestMonth?.percentage ?: (fullAttendance?.overall?.percentage ?: (currentStudent?.attendanceSummary?.percentage ?: 0.0))
                                            val mColor = if (mPct >= 75) Color(0xFF10B981) else if (mPct >= 65) Color(0xFFF59E0B) else Color(0xFFEF4444)
                                            Text(
                                                text = "$mPct%",
                                                fontSize = 24.sp,
                                                fontWeight = FontWeight.ExtraBold,
                                                color = mColor
                                            )
                                        }

                                        Spacer(modifier = Modifier.height(12.dp))

                                        val mPctVal = latestMonth?.percentage ?: (fullAttendance?.overall?.percentage ?: (currentStudent?.attendanceSummary?.percentage ?: 0.0))
                                        val mPctFloat = (mPctVal / 100.0).toFloat().coerceIn(0f, 1f)
                                        LinearProgressIndicator(
                                            progress = { mPctFloat },
                                            modifier = Modifier
                                                .fillMaxWidth()
                                                .height(8.dp)
                                                .clip(RoundedCornerShape(4.dp)),
                                            color = if (mPctFloat >= 0.75f) Color(0xFF10B981) else if (mPctFloat >= 0.65f) Color(0xFFF59E0B) else Color(0xFFEF4444),
                                            trackColor = MaterialTheme.colorScheme.outlineVariant
                                        )

                                        Spacer(modifier = Modifier.height(8.dp))
                                        Row(
                                            modifier = Modifier.fillMaxWidth(),
                                            horizontalArrangement = Arrangement.SpaceBetween
                                        ) {
                                            Text(
                                                text = "${latestMonth?.present ?: (fullAttendance?.overall?.present ?: (currentStudent?.attendanceSummary?.present ?: 0))} / ${latestMonth?.total ?: (fullAttendance?.overall?.total ?: (currentStudent?.attendanceSummary?.total ?: 0))} Classes Attended",
                                                fontSize = 11.5.sp,
                                                color = Color(0xFFCBD5E1)
                                            )
                                            Text(
                                                text = "Sem: ${latestMonth?.semester ?: (currentStudent?.academicSummary?.latestSemester ?: "Current")}",
                                                fontSize = 11.5.sp,
                                                color = MaterialTheme.colorScheme.onSurfaceVariant
                                            )
                                        }
                                    }
                                }
                            }

                            // Stats Grid: Current Active Attendance & CGPA
                            val curCgpa = currentStudent?.academicSummary?.latestCgpa ?: fullResults?.cgpa
                            val curAtt = fullAttendance?.overall?.percentage ?: (currentStudent?.attendanceSummary?.percentage ?: 0.0)
                            item {
                                Row(
                                    modifier = Modifier.fillMaxWidth(),
                                    horizontalArrangement = Arrangement.spacedBy(12.dp)
                                ) {
                                    StatCard(
                                        title = "CURRENT ATTENDANCE",
                                        value = "$curAtt%",
                                        iconEmoji = "📊",
                                        color = Color(0xFF10B981),
                                        modifier = Modifier.weight(1f).clickable { selectedTab = AppTab.ATTENDANCE }
                                    )
                                    StatCard(
                                        title = "CURRENT CGPA",
                                        value = if (curCgpa != null) "$curCgpa" else "N/A",
                                        iconEmoji = "📈",
                                        color = MaterialTheme.colorScheme.primary,
                                        modifier = Modifier.weight(1f).clickable { selectedTab = AppTab.RESULTS }
                                    )
                                }
                            }

                            // Stats Grid: NPTEL & Job Offers
                            item {
                                Row(
                                    modifier = Modifier.fillMaxWidth(),
                                    horizontalArrangement = Arrangement.spacedBy(12.dp)
                                ) {
                                    StatCard(
                                        title = "NPTEL CERTS",
                                        value = "${nptelList?.totalCertificates ?: 0}",
                                        iconEmoji = "📜",
                                        color = Color(0xFF3B82F6),
                                        modifier = Modifier.weight(1f).clickable { selectedTab = AppTab.CAREER }
                                    )
                                    StatCard(
                                        title = "JOB OFFERS",
                                        value = "${placements?.totalOffers ?: 0}",
                                        iconEmoji = "💼",
                                        color = Color(0xFFF59E0B),
                                        modifier = Modifier.weight(1f).clickable { selectedTab = AppTab.CAREER }
                                    )
                                }
                            }

                            // Birthday Banner
                            if (birthdays != null && birthdays!!.count > 0) {
                                item {
                                    Card(
                                        modifier = Modifier.fillMaxWidth(),
                                        colors = CardDefaults.cardColors(containerColor = Color(0xFF78350F)),
                                        shape = RoundedCornerShape(16.dp)
                                    ) {
                                        Row(
                                            modifier = Modifier.padding(14.dp),
                                            verticalAlignment = Alignment.CenterVertically,
                                            horizontalArrangement = Arrangement.spacedBy(12.dp)
                                        ) {
                                            Text("🎂", fontSize = 24.sp)
                                            Column {
                                                Text("Today's Birthdays!", fontWeight = FontWeight.Bold, color = Color(0xFFFDE68A), fontSize = 13.sp)
                                                Text("${birthdays!!.students.firstOrNull()?.studentName ?: ""} celebrating today", fontSize = 11.5.sp, color = Color(0xFFFEF3C7))
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    AppTab.RESULTS -> {
                        LazyColumn(
                            modifier = Modifier
                                .fillMaxSize()
                                .padding(16.dp),
                            verticalArrangement = Arrangement.spacedBy(12.dp)
                        ) {
                            val semesters = fullResults?.semesters ?: emptyList()
                            // CGPA COMMAND CENTER HERO CARD
                            item {
                                Card(
                                    modifier = Modifier.fillMaxWidth(),
                                    colors = CardDefaults.cardColors(containerColor = Color(0xFF1E293B)),
                                    shape = RoundedCornerShape(20.dp),
                                    border = androidx.compose.foundation.BorderStroke(1.dp, Color(0xFF334155))
                                ) {
                                    Row(
                                        modifier = Modifier
                                            .fillMaxWidth()
                                            .padding(18.dp),
                                        horizontalArrangement = Arrangement.SpaceBetween,
                                        verticalAlignment = Alignment.CenterVertically
                                    ) {
                                        Column {
                                            Text(
                                                "CUMULATIVE GPA (CGPA)",
                                                fontSize = 11.sp,
                                                fontWeight = FontWeight.Bold,
                                                color = Color(0xFF94A3B8)
                                            )
                                            Spacer(modifier = Modifier.height(4.dp))
                                            Text(
                                                text = "${fullResults?.cgpa ?: currentStudent?.academicSummary?.latestCgpa ?: "N/A"}",
                                                fontSize = 34.sp,
                                                fontWeight = FontWeight.ExtraBold,
                                                color = Color(0xFF38BDF8)
                                            )
                                            Text("Scale of 10.0", fontSize = 11.sp, color = Color(0xFF64748B))
                                        }

                                        Column(horizontalAlignment = Alignment.End, verticalArrangement = Arrangement.spacedBy(6.dp)) {
                                            val backlogs = fullResults?.backlogsCount ?: 0
                                            val backlogBg = if (backlogs == 0) Color(0xFF065F46) else Color(0xFF991B1B)
                                            val backlogText = if (backlogs == 0) "All Clear (0 Backlogs)" else "$backlogs Active Backlogs"
                                            SuggestionChip(
                                                onClick = {},
                                                label = { Text(backlogText, fontSize = 11.sp, fontWeight = FontWeight.Bold) },
                                                colors = SuggestionChipDefaults.suggestionChipColors(
                                                    containerColor = backlogBg,
                                                    labelColor = Color.White
                                                )
                                            )
                                            val semsCount = fullResults?.totalSemesters ?: semesters.size
                                            Text(
                                                text = "$semsCount Semesters Tracked",
                                                fontSize = 11.5.sp,
                                                color = Color(0xFF94A3B8)
                                            )
                                        }
                                    }
                                }
                            }

                            // SUB-TAB SELECTOR: Semester Results vs Mid-Term Exams
                            item {
                                Row(
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .clip(RoundedCornerShape(12.dp))
                                        .background(Color(0xFF0F172A))
                                        .padding(4.dp)
                                ) {
                                    Box(
                                        modifier = Modifier
                                            .weight(1f)
                                            .clip(RoundedCornerShape(10.dp))
                                            .background(if (selectedResultsSubTab == 0) Color(0xFF38BDF8) else Color.Transparent)
                                            .clickable { selectedResultsSubTab = 0 }
                                            .padding(vertical = 10.dp),
                                        contentAlignment = Alignment.Center
                                    ) {
                                        Text(
                                            "Semester Results 📜",
                                            fontSize = 12.5.sp,
                                            fontWeight = FontWeight.Bold,
                                            color = if (selectedResultsSubTab == 0) Color(0xFF0F172A) else Color(0xFF94A3B8)
                                        )
                                    }
                                    Box(
                                        modifier = Modifier
                                            .weight(1f)
                                            .clip(RoundedCornerShape(10.dp))
                                            .background(if (selectedResultsSubTab == 1) Color(0xFF38BDF8) else Color.Transparent)
                                            .clickable { selectedResultsSubTab = 1 }
                                            .padding(vertical = 10.dp),
                                        contentAlignment = Alignment.Center
                                    ) {
                                        Text(
                                            "Mid-Term Exams 📝",
                                            fontSize = 12.5.sp,
                                            fontWeight = FontWeight.Bold,
                                            color = if (selectedResultsSubTab == 1) Color(0xFF0F172A) else Color(0xFF94A3B8)
                                        )
                                    }
                                }
                            }

                            if (selectedResultsSubTab == 0) {
                                if (semesters.isEmpty()) {
                                item {
                                    Card(
                                        modifier = Modifier.fillMaxWidth(),
                                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant),
                                        shape = RoundedCornerShape(14.dp)
                                    ) {
                                        Box(modifier = Modifier.padding(24.dp).fillMaxWidth(), contentAlignment = Alignment.Center) {
                                            Text("No semester records found.", color = MaterialTheme.colorScheme.onSurfaceVariant, textAlign = TextAlign.Center)
                                        }
                                    }
                                }
                            } else {
                                items(semesters) { sem ->
                                    Card(
                                        modifier = Modifier.fillMaxWidth(),
                                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant),
                                        shape = RoundedCornerShape(16.dp)
                                    ) {
                                        Column(modifier = Modifier.padding(16.dp)) {
                                            Row(
                                                modifier = Modifier.fillMaxWidth(),
                                                horizontalArrangement = Arrangement.SpaceBetween,
                                                verticalAlignment = Alignment.CenterVertically
                                            ) {
                                                Text(sem.semester ?: "Semester", fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onSurface, fontSize = 15.sp)
                                                Row(horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                                                    Text("SGPA: ${sem.sgpa ?: "N/A"}", fontSize = 11.sp, color = MaterialTheme.colorScheme.primaryContainer, fontWeight = FontWeight.Bold)
                                                    Text("CGPA: ${sem.cgpa ?: "N/A"}", fontSize = 11.sp, color = Color(0xFF34D399), fontWeight = FontWeight.Bold)
                                                }
                                            }

                                            Spacer(modifier = Modifier.height(10.dp))
                                            HorizontalDivider(color = MaterialTheme.colorScheme.outlineVariant)
                                            Spacer(modifier = Modifier.height(6.dp))

                                            // Grid Header
                                            Row(
                                                modifier = Modifier
                                                    .fillMaxWidth()
                                                    .padding(vertical = 8.dp, horizontal = 4.dp),
                                                horizontalArrangement = Arrangement.SpaceBetween,
                                                verticalAlignment = Alignment.CenterVertically
                                            ) {
                                                Text("CODE", fontSize = 10.sp, color = MaterialTheme.colorScheme.outline, fontWeight = FontWeight.Bold, modifier = Modifier.weight(1.5f))
                                                Text("COURSE", fontSize = 10.sp, color = MaterialTheme.colorScheme.outline, fontWeight = FontWeight.Bold, modifier = Modifier.weight(2f))
                                                Text("CR", fontSize = 10.sp, color = MaterialTheme.colorScheme.outline, fontWeight = FontWeight.Bold, modifier = Modifier.weight(0.5f), textAlign = TextAlign.Center)
                                                Text("GRD", fontSize = 10.sp, color = MaterialTheme.colorScheme.outline, fontWeight = FontWeight.Bold, modifier = Modifier.weight(0.5f), textAlign = TextAlign.End)
                                            }

                                            HorizontalDivider(color = MaterialTheme.colorScheme.outlineVariant)
                                            Spacer(modifier = Modifier.height(4.dp))

                                            val semesterSubjects = fullResults?.allSubjects?.filter { it.semesterInfo == sem.semester }?.takeIf { it.isNotEmpty() } ?: sem.subjects
                                            semesterSubjects.forEach { sub ->
                                                val abbreviation = sub.subjectName.split(" ").mapNotNull { it.firstOrNull()?.uppercase() }.joinToString("").take(4)
                                                val isSupply = sub.examInfo?.contains("Supplementary", ignoreCase = true) == true || sub.examInfo?.contains("Supply", ignoreCase = true) == true
                                                
                                                Row(
                                                    modifier = Modifier
                                                        .fillMaxWidth()
                                                        .padding(vertical = 6.dp, horizontal = 4.dp),
                                                    horizontalArrangement = Arrangement.SpaceBetween,
                                                    verticalAlignment = Alignment.CenterVertically
                                                ) {
                                                    Column(modifier = Modifier.weight(1.5f)) {
                                                        Text(sub.subjectCode, fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant, maxLines = 1)
                                                        if (isSupply) {
                                                            Text("SUPPLY", fontSize = 8.sp, color = Color(0xFFEF4444), fontWeight = FontWeight.Bold)
                                                        }
                                                    }
                                                    
                                                    // Full name in title, or just abbreviation for space
                                                    Text(
                                                        text = if (sub.subjectName.length > 15) abbreviation else sub.subjectName, 
                                                        fontSize = 12.sp, 
                                                        color = MaterialTheme.colorScheme.onSurface, 
                                                        modifier = Modifier.weight(2f), 
                                                        maxLines = 1
                                                    )
                                                    
                                                    Text("${sub.credits ?: "3.0"}", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant, modifier = Modifier.weight(0.5f), textAlign = TextAlign.Center)
                                                    Text(
                                                        text = sub.grade,
                                                        fontSize = 11.sp,
                                                        fontWeight = FontWeight.ExtraBold,
                                                        color = if (sub.grade in listOf("A+", "O", "A", "S")) Color(0xFF34D399) else if (sub.grade == "F") Color(0xFFEF4444) else Color(0xFFFBBF24),
                                                        modifier = Modifier.weight(0.5f),
                                                        textAlign = TextAlign.End
                                                    )
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                                // MID-TERM EXAM MARKS VIEW
                                item {
                                    Card(
                                        modifier = Modifier.fillMaxWidth(),
                                        colors = CardDefaults.cardColors(containerColor = Color(0xFF0F172A)),
                                        shape = RoundedCornerShape(16.dp),
                                        border = androidx.compose.foundation.BorderStroke(1.dp, Color(0xFF334155))
                                    ) {
                                        Row(
                                            modifier = Modifier
                                                .fillMaxWidth()
                                                .padding(16.dp),
                                            horizontalArrangement = Arrangement.SpaceBetween,
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            Column {
                                                Text("INTERNAL ASSESSMENT OVERVIEW", fontSize = 10.sp, fontWeight = FontWeight.Bold, color = Color(0xFF94A3B8))
                                                Spacer(modifier = Modifier.height(2.dp))
                                                Text(
                                                    text = "${midmarksData?.averageInternalMarks ?: "--"} / 30.0",
                                                    fontSize = 24.sp,
                                                    fontWeight = FontWeight.ExtraBold,
                                                    color = Color(0xFF38BDF8)
                                                )
                                                Text("Average Internal Marks", fontSize = 11.sp, color = Color(0xFF64748B))
                                            }
                                            Column(horizontalAlignment = Alignment.End) {
                                                SuggestionChip(
                                                    onClick = {},
                                                    label = { Text("${midmarksData?.totalSubjects ?: 0} Subjects", fontSize = 11.sp, fontWeight = FontWeight.Bold) },
                                                    colors = SuggestionChipDefaults.suggestionChipColors(
                                                        containerColor = Color(0xFF1E293B),
                                                        labelColor = Color(0xFF38BDF8)
                                                    )
                                                )
                                                Text(
                                                    "${midmarksData?.evaluatedCount ?: 0} Evaluated",
                                                    fontSize = 11.sp,
                                                    color = Color(0xFF94A3B8)
                                                )
                                            }
                                        }
                                    }
                                }

                                // GROUP MID-TERM MARKS BY SEMESTER — TABLE VIEW
                                val midList = midmarksData?.subjects ?: emptyList()
                                if (midList.isEmpty()) {
                                    item {
                                        Box(
                                            modifier = Modifier.fillMaxWidth().padding(24.dp),
                                            contentAlignment = Alignment.Center
                                        ) {
                                            Text(
                                                "No mid-term exam marks published yet.",
                                                color = Color(0xFF64748B), textAlign = TextAlign.Center, fontSize = 13.sp
                                            )
                                        }
                                    }
                                } else {
                                    // Group subjects by semester (plain val — midList already from remembered state)
                                    val semGrouped = midList
                                        .groupBy { it.semesterInfo ?: "Unknown Sem" }
                                        .entries.sortedBy { it.key }
                                    items(semGrouped) { (semLabel, subjects) ->
                                        // ONE TABLE CARD PER SEMESTER
                                        Box(
                                            modifier = Modifier
                                                .fillMaxWidth()
                                                .shadow(8.dp, RoundedCornerShape(18.dp))
                                                .clip(RoundedCornerShape(18.dp))
                                                .background(Color(0xFF1E293B))
                                                .border(1.dp, Color(0xFF334155), RoundedCornerShape(18.dp))
                                        ) {
                                            Column(modifier = Modifier.padding(16.dp)) {
                                                // Semester Header
                                                Row(
                                                    modifier = Modifier.fillMaxWidth(),
                                                    horizontalArrangement = Arrangement.SpaceBetween,
                                                    verticalAlignment = Alignment.CenterVertically
                                                ) {
                                                    Text(
                                                        text = "📝 Semester $semLabel",
                                                        fontSize = 13.sp,
                                                        fontWeight = FontWeight.ExtraBold,
                                                        color = Color(0xFF38BDF8),
                                                        letterSpacing = 0.3.sp
                                                    )
                                                    val semAvg = subjects.mapNotNull { it.totalMarks }.takeIf { it.isNotEmpty() }?.average()
                                                    if (semAvg != null) {
                                                        Box(
                                                            modifier = Modifier
                                                                .clip(RoundedCornerShape(8.dp))
                                                                .background(
                                                                    when { semAvg >= 25.0 -> Color(0xFF065F46); semAvg >= 18.0 -> Color(0xFF78350F); else -> Color(0xFF7F1D1D) }
                                                                )
                                                                .padding(horizontal = 8.dp, vertical = 3.dp)
                                                        ) {
                                                            Text(
                                                                text = "Avg: ${String.format("%.1f", semAvg)}",
                                                                fontSize = 10.sp, fontWeight = FontWeight.Bold,
                                                                color = when { semAvg >= 25.0 -> Color(0xFF34D399); semAvg >= 18.0 -> Color(0xFFFBBF24); else -> Color(0xFFF87171) }
                                                            )
                                                        }
                                                    }
                                                }

                                                Spacer(modifier = Modifier.height(10.dp))

                                                // Table Header Row
                                                Row(
                                                    modifier = Modifier
                                                        .fillMaxWidth()
                                                        .clip(RoundedCornerShape(8.dp))
                                                        .background(Color(0xFF0F172A))
                                                        .padding(horizontal = 10.dp, vertical = 7.dp)
                                                ) {
                                                    Text("SUBJECT", fontSize = 9.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFF6366F1), modifier = Modifier.weight(2.5f), letterSpacing = 0.5.sp)
                                                    Text("MID-1", fontSize = 9.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFF38BDF8), modifier = Modifier.weight(1f), textAlign = TextAlign.Center, letterSpacing = 0.5.sp)
                                                    Text("MID-2", fontSize = 9.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFFA78BFA), modifier = Modifier.weight(1f), textAlign = TextAlign.Center, letterSpacing = 0.5.sp)
                                                    Text("TOTAL", fontSize = 9.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFF94A3B8), modifier = Modifier.weight(0.9f), textAlign = TextAlign.End, letterSpacing = 0.5.sp)
                                                }

                                                // Table Data Rows
                                                subjects.forEachIndexed { idx, mm ->
                                                    val rowBg = if (idx % 2 == 0) Color.Transparent else Color(0xFF0F172A).copy(alpha = 0.5f)
                                                    val totalColor = when {
                                                        (mm.totalMarks ?: 0.0) >= 25.0 -> Color(0xFF34D399)
                                                        (mm.totalMarks ?: 0.0) >= 18.0 -> Color(0xFFFBBF24)
                                                        else -> Color(0xFFF87171)
                                                    }
                                                    // Short name: first letters abbreviation if too long
                                                    val shortName = if (mm.subjectName.length > 18)
                                                        mm.subjectName.split(" ").mapNotNull { it.firstOrNull()?.uppercase() }.joinToString("").take(5)
                                                    else mm.subjectName

                                                    Row(
                                                        modifier = Modifier
                                                            .fillMaxWidth()
                                                            .background(rowBg)
                                                            .padding(horizontal = 10.dp, vertical = 8.dp),
                                                        verticalAlignment = Alignment.CenterVertically
                                                    ) {
                                                        Column(modifier = Modifier.weight(2.5f)) {
                                                            Text(
                                                                text = shortName,
                                                                fontSize = 11.sp, fontWeight = FontWeight.SemiBold,
                                                                color = Color.White, maxLines = 1
                                                            )
                                                            Text(
                                                                text = mm.subjectCode,
                                                                fontSize = 9.sp, color = Color(0xFF64748B), maxLines = 1
                                                            )
                                                        }
                                                        // MID-1 cell
                                                        Column(
                                                            modifier = Modifier.weight(1f),
                                                            horizontalAlignment = Alignment.CenterHorizontally
                                                        ) {
                                                            Text(
                                                                text = "${mm.mid1?.total ?: "--"}",
                                                                fontSize = 12.sp, fontWeight = FontWeight.Bold, color = Color(0xFF38BDF8)
                                                            )
                                                            if (mm.mid1 != null) {
                                                                Text(
                                                                    text = "D:${mm.mid1.descriptive?.toInt() ?: "-"} O:${mm.mid1.objective?.toInt() ?: "-"}",
                                                                    fontSize = 8.5.sp, color = Color(0xFF64748B)
                                                                )
                                                            }
                                                        }
                                                        // MID-2 cell
                                                        Column(
                                                            modifier = Modifier.weight(1f),
                                                            horizontalAlignment = Alignment.CenterHorizontally
                                                        ) {
                                                            Text(
                                                                text = "${mm.mid2?.total ?: "--"}",
                                                                fontSize = 12.sp, fontWeight = FontWeight.Bold, color = Color(0xFFA78BFA)
                                                            )
                                                            if (mm.mid2 != null) {
                                                                Text(
                                                                    text = "D:${mm.mid2.descriptive?.toInt() ?: "-"} O:${mm.mid2.objective?.toInt() ?: "-"}",
                                                                    fontSize = 8.5.sp, color = Color(0xFF64748B)
                                                                )
                                                            }
                                                        }
                                                        // Total cell
                                                        Text(
                                                            text = "${mm.totalMarks ?: "--"}",
                                                            fontSize = 13.sp, fontWeight = FontWeight.ExtraBold,
                                                            color = totalColor,
                                                            modifier = Modifier.weight(0.9f),
                                                            textAlign = TextAlign.End
                                                        )
                                                    }
                                                    if (idx < subjects.size - 1) {
                                                        HorizontalDivider(color = Color(0xFF1E293B), thickness = 0.5.dp)
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    AppTab.ATTENDANCE -> {
                        val allSubjects = fullAttendance?.subjects ?: emptyList()
                        val semSummaries = fullAttendance?.semesterSummaries ?: emptyList()
                        val uniqueSemesters = remember(allSubjects, semSummaries) {
                            val set = if (semSummaries.isNotEmpty()) {
                                semSummaries.map { it.semester }
                            } else {
                                allSubjects.mapNotNull { it.semester?.takeIf { s -> s.isNotBlank() } }
                            }.distinct()
                            if (set.isEmpty()) listOf("All Semesters") else set.reversed() + listOf("All Semesters")
                        }

                        // Auto-select latest active semester on open
                        LaunchedEffect(uniqueSemesters) {
                            val active = uniqueSemesters.firstOrNull { it != "All Semesters" }
                            if (active != null && selectedAttendanceSemester == "All Semesters") {
                                selectedAttendanceSemester = active
                            }
                        }

                        val filteredSubjects = remember(selectedAttendanceSemester, allSubjects) {
                            if (selectedAttendanceSemester == "All Semesters") {
                                allSubjects
                            } else {
                                allSubjects.filter { it.semester.equals(selectedAttendanceSemester, ignoreCase = true) }
                            }
                        }

                        // DYNAMIC ATTENDANCE SUMMARY CALCULATION BASED ON SELECTED SEMESTER
                        val isAllSem = selectedAttendanceSemester == "All Semesters"
                        val targetSummary = if (!isAllSem) semSummaries.find { it.semester.equals(selectedAttendanceSemester, ignoreCase = true) } else null

                        val dynamicPresent = if (isAllSem) {
                            fullAttendance?.overall?.present ?: (currentStudent?.attendanceSummary?.present ?: 0)
                        } else {
                            targetSummary?.present ?: filteredSubjects.sumOf { it.present }
                        }
                        val dynamicTotal = if (isAllSem) {
                            fullAttendance?.overall?.total ?: (currentStudent?.attendanceSummary?.total ?: 0)
                        } else {
                            targetSummary?.total ?: filteredSubjects.sumOf { it.total }
                        }
                        val dynamicPct = if (isAllSem) {
                            fullAttendance?.overall?.percentage ?: (currentStudent?.attendanceSummary?.percentage ?: 0.0)
                        } else {
                            targetSummary?.percentage ?: (if (dynamicTotal > 0) Math.round((dynamicPresent.toDouble() / dynamicTotal.toDouble()) * 10000.0) / 100.0 else 0.0)
                        }

                        val monthlyList = remember(selectedAttendanceSemester, fullAttendance) {
                            val allMonthly = fullAttendance?.monthly ?: emptyList()
                            if (selectedAttendanceSemester == "All Semesters") {
                                allMonthly
                            } else {
                                allMonthly.filter { it.semester.equals(selectedAttendanceSemester, ignoreCase = true) }
                            }
                        }

                        LazyColumn(
                            modifier = Modifier
                                .fillMaxSize()
                                .padding(16.dp),
                            verticalArrangement = Arrangement.spacedBy(12.dp)
                        ) {
                            // PREMIUM CIRCULAR ARC GAUGE SUMMARY CARD
                            item {
                                Box(
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .shadow(16.dp, RoundedCornerShape(24.dp))
                                        .clip(RoundedCornerShape(24.dp))
                                        .background(Color(0xFF1E293B))
                                        .border(1.dp, Color(0xFF334155), RoundedCornerShape(24.dp))
                                ) {
                                    Column(modifier = Modifier.padding(20.dp)) {
                                        Text(
                                            text = if (isAllSem) "OVERALL ATTENDANCE" else "$selectedAttendanceSemester ATTENDANCE",
                                            fontSize = 10.5.sp,
                                            fontWeight = FontWeight.ExtraBold,
                                            color = Color(0xFF6366F1),
                                            letterSpacing = 1.sp
                                        )

                                        Spacer(modifier = Modifier.height(16.dp))

                                        // Arc Gauge + Stats Side by Side
                                        Row(
                                            modifier = Modifier.fillMaxWidth(),
                                            verticalAlignment = Alignment.CenterVertically,
                                            horizontalArrangement = Arrangement.spacedBy(20.dp)
                                        ) {
                                            // CIRCULAR ARC GAUGE
                                            val gaugeColor = if (dynamicPct >= 75) Color(0xFF10B981) else if (dynamicPct >= 65) Color(0xFFF59E0B) else Color(0xFFEF4444)
                                            val sweepAngle = (dynamicPct.toFloat() / 100f * 240f).coerceIn(0f, 240f)
                                            Box(
                                                modifier = Modifier.size(120.dp),
                                                contentAlignment = Alignment.Center
                                            ) {
                                                Canvas(modifier = Modifier.size(120.dp)) {
                                                    val strokeWidth = 14.dp.toPx()
                                                    val inset = strokeWidth / 2f
                                                    val arcSize = Size(size.width - strokeWidth, size.height - strokeWidth)
                                                    val topLeft = Offset(inset, inset)
                                                    // Track
                                                    drawArc(
                                                        color = Color(0xFF334155),
                                                        startAngle = 150f,
                                                        sweepAngle = 240f,
                                                        useCenter = false,
                                                        topLeft = topLeft,
                                                        size = arcSize,
                                                        style = Stroke(strokeWidth, cap = StrokeCap.Round)
                                                    )
                                                    // Progress Arc
                                                    drawArc(
                                                        brush = Brush.sweepGradient(
                                                            listOf(gaugeColor.copy(alpha = 0.7f), gaugeColor)
                                                        ),
                                                        startAngle = 150f,
                                                        sweepAngle = sweepAngle,
                                                        useCenter = false,
                                                        topLeft = topLeft,
                                                        size = arcSize,
                                                        style = Stroke(strokeWidth, cap = StrokeCap.Round)
                                                    )
                                                }
                                                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                                                    Text(
                                                        text = "$dynamicPct%",
                                                        fontSize = 20.sp,
                                                        fontWeight = FontWeight.ExtraBold,
                                                        color = gaugeColor
                                                    )
                                                    Text(
                                                        text = "ATTENDANCE",
                                                        fontSize = 7.5.sp,
                                                        fontWeight = FontWeight.Bold,
                                                        color = Color(0xFF94A3B8),
                                                        letterSpacing = 0.5.sp
                                                    )
                                                }
                                            }

                                            // Right Side Stats
                                            Column(
                                                modifier = Modifier.weight(1f),
                                                verticalArrangement = Arrangement.spacedBy(10.dp)
                                            ) {
                                                val statusText = if (dynamicPct >= 75) "✅ ELIGIBLE (SAFE)" else if (dynamicPct >= 65) "⚠️ CONDONATION" else "🚨 CRITICAL"
                                                val statusBg = if (dynamicPct >= 75) Color(0xFF065F46) else if (dynamicPct >= 65) Color(0xFF78350F) else Color(0xFF991B1B)
                                                val gaugeColor2 = if (dynamicPct >= 75) Color(0xFF34D399) else if (dynamicPct >= 65) Color(0xFFFBBF24) else Color(0xFFF87171)
                                                Box(
                                                    modifier = Modifier
                                                        .clip(RoundedCornerShape(8.dp))
                                                        .background(statusBg.copy(alpha = 0.7f))
                                                        .padding(horizontal = 10.dp, vertical = 5.dp)
                                                ) {
                                                    Text(statusText, fontSize = 10.sp, fontWeight = FontWeight.ExtraBold, color = gaugeColor2)
                                                }

                                                Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                                                    Column {
                                                        Text("$dynamicPresent", fontSize = 18.sp, fontWeight = FontWeight.ExtraBold, color = Color.White)
                                                        Text("PRESENT", fontSize = 9.sp, fontWeight = FontWeight.Bold, color = Color(0xFF94A3B8), letterSpacing = 0.3.sp)
                                                    }
                                                    Column {
                                                        Text("$dynamicTotal", fontSize = 18.sp, fontWeight = FontWeight.ExtraBold, color = Color.White)
                                                        Text("TOTAL", fontSize = 9.sp, fontWeight = FontWeight.Bold, color = Color(0xFF94A3B8), letterSpacing = 0.3.sp)
                                                    }
                                                }

                                                Text(
                                                    text = "Min. 75% required for eligibility",
                                                    fontSize = 10.sp,
                                                    color = Color(0xFF64748B),
                                                    lineHeight = 14.sp
                                                )
                                            }
                                        }

                                        // SMART ATTENDANCE BUNK SHIELD / CONDONATION & RECOVERY
                                        val safeBunks = if (dynamicTotal > 0 && dynamicPct >= 75.0) {
                                            val canMiss = Math.floor((4.0 * dynamicPresent - 3.0 * dynamicTotal) / 3.0).toInt()
                                            if (canMiss < 0) 0 else canMiss
                                        } else 0

                                        val neededFor75 = if (dynamicTotal > 0 && dynamicPct < 75.0) {
                                            val need = Math.ceil(3.0 * dynamicTotal - 4.0 * dynamicPresent).toInt()
                                            if (need < 0) 0 else need
                                        } else 0

                                        // How many classes can be missed before falling below 65% (for 65% to 74.9%)
                                        val safeBunksFor65 = if (dynamicTotal > 0 && dynamicPct >= 65.0) {
                                            val canMiss = Math.floor((20.0 * dynamicPresent - 13.0 * dynamicTotal) / 13.0).toInt()
                                            if (canMiss < 0) 0 else canMiss
                                        } else 0

                                        // Classes needed to reach 65% condonation cutoff if < 65%
                                        val neededFor65 = if (dynamicTotal > 0 && dynamicPct < 65.0) {
                                            val need = Math.ceil((13.0 * dynamicTotal - 20.0 * dynamicPresent) / 7.0).toInt()
                                            if (need < 0) 0 else need
                                        } else 0

                                        Spacer(modifier = Modifier.height(12.dp))
                                        HorizontalDivider(color = Color(0xFF334155))
                                        Spacer(modifier = Modifier.height(10.dp))

                                        if (dynamicPct >= 75.0) {
                                            // >= 75%: Safe / Regular Eligibility
                                            Row(
                                                modifier = Modifier
                                                    .fillMaxWidth()
                                                    .clip(RoundedCornerShape(12.dp))
                                                    .background(Color(0xFF065F46).copy(alpha = 0.35f))
                                                    .border(1.dp, Color(0xFF10B981).copy(alpha = 0.4f), RoundedCornerShape(12.dp))
                                                    .padding(horizontal = 12.dp, vertical = 8.dp),
                                                verticalAlignment = Alignment.CenterVertically,
                                                horizontalArrangement = Arrangement.spacedBy(8.dp)
                                            ) {
                                                Text("🛡️", fontSize = 18.sp)
                                                Column {
                                                    Text("BUNK MARGIN SHIELD • REGULAR ELIGIBLE", fontSize = 10.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFF34D399), letterSpacing = 0.5.sp)
                                                    Text(
                                                        text = if (safeBunks > 0) "You can safely miss $safeBunks classes and still stay above 75%!" else "You are right at 75%. Attend upcoming classes to build buffer!",
                                                        fontSize = 11.5.sp,
                                                        fontWeight = FontWeight.Medium,
                                                        color = Color.White
                                                    )
                                                }
                                            }
                                        } else if (dynamicPct >= 65.0) {
                                            // 65% to 74.9%: Eligible with Condonation
                                            Row(
                                                modifier = Modifier
                                                    .fillMaxWidth()
                                                    .clip(RoundedCornerShape(12.dp))
                                                    .background(Color(0xFF78350F).copy(alpha = 0.35f))
                                                    .border(1.dp, Color(0xFFF59E0B).copy(alpha = 0.4f), RoundedCornerShape(12.dp))
                                                    .padding(horizontal = 12.dp, vertical = 8.dp),
                                                verticalAlignment = Alignment.CenterVertically,
                                                horizontalArrangement = Arrangement.spacedBy(8.dp)
                                            ) {
                                                Text("📋", fontSize = 18.sp)
                                                Column(verticalArrangement = Arrangement.spacedBy(2.dp)) {
                                                    Text("CONDONATION ZONE • ELIGIBLE WITH CONDONATION (65%–74%)", fontSize = 10.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFFFBBF24), letterSpacing = 0.4.sp)
                                                    Text(
                                                        text = "Eligible for exams with condonation fee. Attend $neededFor75 more classes to reach 75% (no fine)! Buffer before 65%: $safeBunksFor65 classes.",
                                                        fontSize = 11.5.sp,
                                                        fontWeight = FontWeight.Medium,
                                                        color = Color.White
                                                    )
                                                }
                                            }
                                        } else {
                                            // < 65%: Critical / Detention Risk
                                            Row(
                                                modifier = Modifier
                                                    .fillMaxWidth()
                                                    .clip(RoundedCornerShape(12.dp))
                                                    .background(Color(0xFF7F1D1D).copy(alpha = 0.35f))
                                                    .border(1.dp, Color(0xFFEF4444).copy(alpha = 0.4f), RoundedCornerShape(12.dp))
                                                    .padding(horizontal = 12.dp, vertical = 8.dp),
                                                verticalAlignment = Alignment.CenterVertically,
                                                horizontalArrangement = Arrangement.spacedBy(8.dp)
                                            ) {
                                                Text("⚠️", fontSize = 18.sp)
                                                Column {
                                                    Text("CRITICAL DETENTION ALERT • BELOW 65%", fontSize = 10.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFFF87171), letterSpacing = 0.5.sp)
                                                    Text(
                                                        text = "Must attend $neededFor65 classes to reach 65% condonation minimum, or $neededFor75 classes for 75%!",
                                                        fontSize = 11.5.sp,
                                                        fontWeight = FontWeight.Medium,
                                                        color = Color.White
                                                    )
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            // ==========================================
                            // INTERACTIVE ATTENDANCE "WHAT-IF" SIMULATOR
                            // ==========================================
                            item {
                                val simTotal = dynamicTotal + simAttendMore + simMissMore
                                val simPresent = dynamicPresent + simAttendMore
                                val simPct = if (simTotal > 0) {
                                    Math.round((simPresent.toDouble() / simTotal.toDouble()) * 10000.0) / 100.0
                                } else dynamicPct
                                val deltaPct = Math.round((simPct - dynamicPct) * 100.0) / 100.0

                                Card(
                                    modifier = Modifier.fillMaxWidth(),
                                    colors = CardDefaults.cardColors(containerColor = Color(0xFF1E293B)),
                                    shape = RoundedCornerShape(20.dp),
                                    border = androidx.compose.foundation.BorderStroke(1.dp, Color(0xFF334155))
                                ) {
                                    Column(modifier = Modifier.padding(18.dp)) {
                                        Row(
                                            modifier = Modifier.fillMaxWidth(),
                                            horizontalArrangement = Arrangement.SpaceBetween,
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                                                Text("🎯", fontSize = 20.sp)
                                                Column {
                                                    Text(
                                                        "WHAT-IF ATTENDANCE SIMULATOR",
                                                        fontSize = 11.sp,
                                                        fontWeight = FontWeight.ExtraBold,
                                                        color = Color(0xFF38BDF8),
                                                        letterSpacing = 0.5.sp
                                                    )
                                                    Text(
                                                        "Plan upcoming classes & predict your percentage",
                                                        fontSize = 11.sp,
                                                        color = Color(0xFF94A3B8)
                                                    )
                                                }
                                            }
                                            if (simAttendMore > 0 || simMissMore > 0) {
                                                TextButton(
                                                    onClick = {
                                                        simAttendMore = 0
                                                        simMissMore = 0
                                                    },
                                                    contentPadding = PaddingValues(horizontal = 8.dp, vertical = 2.dp)
                                                ) {
                                                    Text("Reset ↺", fontSize = 11.5.sp, color = Color(0xFFF87171), fontWeight = FontWeight.Bold)
                                                }
                                            }
                                        }

                                        Spacer(modifier = Modifier.height(14.dp))

                                        // Projected Result Display Box
                                        Row(
                                            modifier = Modifier
                                                .fillMaxWidth()
                                                .clip(RoundedCornerShape(14.dp))
                                                .background(Color(0xFF0F172A))
                                                .padding(14.dp),
                                            horizontalArrangement = Arrangement.SpaceBetween,
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            Column {
                                                Text("PROJECTED ATTENDANCE", fontSize = 10.sp, fontWeight = FontWeight.Bold, color = Color(0xFF64748B))
                                                Spacer(modifier = Modifier.height(2.dp))
                                                Row(verticalAlignment = Alignment.Bottom, horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                                                    Text(
                                                        "$simPct%",
                                                        fontSize = 26.sp,
                                                        fontWeight = FontWeight.ExtraBold,
                                                        color = when {
                                                            simPct >= 75.0 -> Color(0xFF34D399)
                                                            simPct >= 65.0 -> Color(0xFFFBBF24)
                                                            else -> Color(0xFFF87171)
                                                        }
                                                    )
                                                    if (deltaPct != 0.0) {
                                                        Text(
                                                            text = if (deltaPct > 0) "+$deltaPct%" else "$deltaPct%",
                                                            fontSize = 13.sp,
                                                            fontWeight = FontWeight.Bold,
                                                            color = if (deltaPct > 0) Color(0xFF34D399) else Color(0xFFF87171),
                                                            modifier = Modifier.padding(bottom = 3.dp)
                                                        )
                                                    }
                                                }
                                                Text(
                                                    "$simPresent attended of $simTotal held",
                                                    fontSize = 11.5.sp,
                                                    color = Color(0xFF94A3B8)
                                                )
                                            }

                                            // Status Badge
                                            val badgeBg = when {
                                                simPct >= 75.0 -> Color(0xFF065F46)
                                                simPct >= 65.0 -> Color(0xFF78350F)
                                                else -> Color(0xFF7F1D1D)
                                            }
                                            val badgeColor = when {
                                                simPct >= 75.0 -> Color(0xFF34D399)
                                                simPct >= 65.0 -> Color(0xFFFBBF24)
                                                else -> Color(0xFFF87171)
                                            }
                                            val badgeLabel = when {
                                                simPct >= 75.0 -> "SAFE (>75%)"
                                                simPct >= 65.0 -> "CONDONATION"
                                                else -> "DETAINED (<65%)"
                                            }
                                            SuggestionChip(
                                                onClick = {},
                                                label = { Text(badgeLabel, fontSize = 10.5.sp, fontWeight = FontWeight.ExtraBold) },
                                                colors = SuggestionChipDefaults.suggestionChipColors(
                                                    containerColor = badgeBg,
                                                    labelColor = badgeColor
                                                )
                                            )
                                        }

                                        Spacer(modifier = Modifier.height(14.dp))

                                        // Controls: Attend More Stepper (vertical stacked for screen adaptability)
                                        Box(
                                            modifier = Modifier
                                                .fillMaxWidth()
                                                .clip(RoundedCornerShape(12.dp))
                                                .background(Color(0xFF0F172A))
                                                .padding(12.dp)
                                        ) {
                                            Column(modifier = Modifier.fillMaxWidth()) {
                                                Row(
                                                    modifier = Modifier.fillMaxWidth(),
                                                    horizontalArrangement = Arrangement.SpaceBetween,
                                                    verticalAlignment = Alignment.CenterVertically
                                                ) {
                                                    Column(modifier = Modifier.weight(1f)) {
                                                        Text("📗 Classes to Attend", fontSize = 12.sp, fontWeight = FontWeight.Bold, color = Color(0xFF34D399))
                                                        Text("Upcoming classes you will attend", fontSize = 10.sp, color = Color(0xFF64748B))
                                                    }
                                                    Row(
                                                        verticalAlignment = Alignment.CenterVertically,
                                                        horizontalArrangement = Arrangement.spacedBy(10.dp)
                                                    ) {
                                                        FilledTonalIconButton(
                                                            onClick = { if (simAttendMore > 0) simAttendMore-- },
                                                            modifier = Modifier.size(38.dp),
                                                            colors = IconButtonDefaults.filledTonalIconButtonColors(containerColor = Color(0xFF334155))
                                                        ) { Text("−", fontSize = 20.sp, fontWeight = FontWeight.Bold, color = Color.White) }
                                                        Text(
                                                            "+$simAttendMore",
                                                            fontSize = 16.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFF34D399),
                                                            modifier = Modifier.widthIn(min = 36.dp), textAlign = TextAlign.Center
                                                        )
                                                        FilledTonalIconButton(
                                                            onClick = { simAttendMore++ },
                                                            modifier = Modifier.size(38.dp),
                                                            colors = IconButtonDefaults.filledTonalIconButtonColors(containerColor = Color(0xFF065F46))
                                                        ) { Text("+", fontSize = 20.sp, fontWeight = FontWeight.Bold, color = Color.White) }
                                                    }
                                                }
                                                Spacer(modifier = Modifier.height(6.dp))
                                                // Quick attend chips
                                                Row(horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                                                    listOf(1, 3, 5, 10).forEach { add ->
                                                        Box(
                                                            modifier = Modifier
                                                                .clip(RoundedCornerShape(20.dp))
                                                                .background(Color(0xFF134E4A))
                                                                .clickable { simAttendMore += add }
                                                                .padding(horizontal = 10.dp, vertical = 4.dp)
                                                        ) { Text("+$add", fontSize = 11.sp, fontWeight = FontWeight.Bold, color = Color(0xFF2DD4BF)) }
                                                    }
                                                }
                                            }
                                        }

                                        Spacer(modifier = Modifier.height(8.dp))

                                        // Controls: Miss/Bunk More Stepper
                                        Box(
                                            modifier = Modifier
                                                .fillMaxWidth()
                                                .clip(RoundedCornerShape(12.dp))
                                                .background(Color(0xFF0F172A))
                                                .padding(12.dp)
                                        ) {
                                            Column(modifier = Modifier.fillMaxWidth()) {
                                                Row(
                                                    modifier = Modifier.fillMaxWidth(),
                                                    horizontalArrangement = Arrangement.SpaceBetween,
                                                    verticalAlignment = Alignment.CenterVertically
                                                ) {
                                                    Column(modifier = Modifier.weight(1f)) {
                                                        Text("📕 Classes to Miss / Bunk", fontSize = 12.sp, fontWeight = FontWeight.Bold, color = Color(0xFFF87171))
                                                        Text("Leaves, health, or events", fontSize = 10.sp, color = Color(0xFF64748B))
                                                    }
                                                    Row(
                                                        verticalAlignment = Alignment.CenterVertically,
                                                        horizontalArrangement = Arrangement.spacedBy(10.dp)
                                                    ) {
                                                        FilledTonalIconButton(
                                                            onClick = { if (simMissMore > 0) simMissMore-- },
                                                            modifier = Modifier.size(38.dp),
                                                            colors = IconButtonDefaults.filledTonalIconButtonColors(containerColor = Color(0xFF334155))
                                                        ) { Text("−", fontSize = 20.sp, fontWeight = FontWeight.Bold, color = Color.White) }
                                                        Text(
                                                            "-$simMissMore",
                                                            fontSize = 16.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFFF87171),
                                                            modifier = Modifier.widthIn(min = 36.dp), textAlign = TextAlign.Center
                                                        )
                                                        FilledTonalIconButton(
                                                            onClick = { simMissMore++ },
                                                            modifier = Modifier.size(38.dp),
                                                            colors = IconButtonDefaults.filledTonalIconButtonColors(containerColor = Color(0xFF7F1D1D))
                                                        ) { Text("+", fontSize = 20.sp, fontWeight = FontWeight.Bold, color = Color.White) }
                                                    }
                                                }
                                                Spacer(modifier = Modifier.height(6.dp))
                                                Row(horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                                                    listOf(1, 2, 3, 5).forEach { sub ->
                                                        Box(
                                                            modifier = Modifier
                                                                .clip(RoundedCornerShape(20.dp))
                                                                .background(Color(0xFF450A0A))
                                                                .clickable { simMissMore += sub }
                                                                .padding(horizontal = 10.dp, vertical = 4.dp)
                                                        ) { Text("-$sub", fontSize = 11.sp, fontWeight = FontWeight.Bold, color = Color(0xFFFCA5A5)) }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            // DAILY ATTENDANCE REMINDER CARD
                            item {
                                Card(
                                    modifier = Modifier.fillMaxWidth(),
                                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant),
                                    shape = RoundedCornerShape(16.dp)
                                ) {
                                    Column(modifier = Modifier.padding(16.dp)) {
                                        Row(
                                            modifier = Modifier.fillMaxWidth(),
                                            horizontalArrangement = Arrangement.SpaceBetween,
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            Row(
                                                verticalAlignment = Alignment.CenterVertically,
                                                horizontalArrangement = Arrangement.spacedBy(10.dp)
                                            ) {
                                                Text("⏰", fontSize = 22.sp)
                                                Column {
                                                    Text(
                                                        text = "Daily Morning Reminder",
                                                        fontSize = 14.sp,
                                                        fontWeight = FontWeight.Bold,
                                                        color = MaterialTheme.colorScheme.onSurface
                                                    )
                                                    Text(
                                                        text = if (isAttendanceReminderOn) "Scheduled every morning at 8:30 AM" else "Reminder is turned off",
                                                        fontSize = 11.sp,
                                                        color = MaterialTheme.colorScheme.onSurfaceVariant
                                                    )
                                                }
                                            }
                                            Switch(
                                                checked = isAttendanceReminderOn,
                                                onCheckedChange = { isChecked ->
                                                    isAttendanceReminderOn = isChecked
                                                    sessionManager.setReminderEnabled(isChecked)
                                                    if (isChecked) {
                                                        AttendanceReminderScheduler.scheduleDailyReminder(context)
                                                        Toast.makeText(context, "✅ Daily reminder set for 8:30 AM!", Toast.LENGTH_SHORT).show()
                                                    } else {
                                                        AttendanceReminderScheduler.cancelReminder(context)
                                                        Toast.makeText(context, "Daily reminder disabled", Toast.LENGTH_SHORT).show()
                                                    }
                                                }
                                            )
                                        }

                                        if (isAttendanceReminderOn) {
                                            Spacer(modifier = Modifier.height(10.dp))
                                            HorizontalDivider(color = MaterialTheme.colorScheme.outlineVariant)
                                            Spacer(modifier = Modifier.height(8.dp))

                                            Row(
                                                modifier = Modifier.fillMaxWidth(),
                                                horizontalArrangement = Arrangement.SpaceBetween,
                                                verticalAlignment = Alignment.CenterVertically
                                            ) {
                                                Text(
                                                    text = "Sends uplifting quotes + live attendance buffer",
                                                    fontSize = 11.sp,
                                                    color = Color(0xFF34D399),
                                                    modifier = Modifier.weight(1f)
                                                )

                                                OutlinedButton(
                                                    onClick = {
                                                        // Send instant preview reminder
                                                        val testIntent = android.content.Intent(context, AttendanceReminderReceiver::class.java).apply {
                                                            action = AttendanceReminderReceiver.ACTION_REMINDER
                                                        }
                                                        context.sendBroadcast(testIntent)
                                                        Toast.makeText(context, "🔔 Notification sent! Check your status bar.", Toast.LENGTH_SHORT).show()
                                                    },
                                                    shape = RoundedCornerShape(10.dp),
                                                    contentPadding = PaddingValues(horizontal = 10.dp, vertical = 4.dp),
                                                    modifier = Modifier.height(34.dp)
                                                ) {
                                                    Text("🔔 Test Now", fontSize = 11.5.sp, fontWeight = FontWeight.Bold)
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            // Semester Tabs / Filter Chips
                            item {
                                Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                                    Text("SELECT SEMESTER:", fontSize = 11.sp, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onSurfaceVariant)
                                    Row(
                                        modifier = Modifier
                                            .fillMaxWidth()
                                            .horizontalScroll(rememberScrollState()),
                                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                                    ) {
                                        uniqueSemesters.forEach { sem ->
                                            val isSelected = selectedAttendanceSemester == sem
                                            FilterChip(
                                                selected = isSelected,
                                                onClick = { selectedAttendanceSemester = sem },
                                                label = { Text(sem, fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Normal) },
                                                colors = FilterChipDefaults.filterChipColors(
                                                    selectedContainerColor = MaterialTheme.colorScheme.primary,
                                                    selectedLabelColor = MaterialTheme.colorScheme.onSurface,
                                                    containerColor = MaterialTheme.colorScheme.surfaceVariant,
                                                    labelColor = MaterialTheme.colorScheme.onSurfaceVariant
                                                )
                                            )
                                        }
                                    }
                                }
                            }

                            // Table Header
                            item {
                                Row(
                                    modifier = Modifier.fillMaxWidth(),
                                    horizontalArrangement = Arrangement.SpaceBetween,
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    Text(
                                        text = if (isAllSem) "All Subjects (${filteredSubjects.size})" else "$selectedAttendanceSemester Subjects (${filteredSubjects.size})",
                                        fontSize = 13.sp,
                                        fontWeight = FontWeight.Bold,
                                        color = MaterialTheme.colorScheme.onSurface
                                    )
                                }
                            }

                            // Semester-Wise Subjects Table Card View
                            if (filteredSubjects.isEmpty()) {
                                item {
                                    Card(
                                        modifier = Modifier.fillMaxWidth(),
                                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant),
                                        shape = RoundedCornerShape(14.dp)
                                    ) {
                                        Box(modifier = Modifier.padding(24.dp).fillMaxWidth(), contentAlignment = Alignment.Center) {
                                            Text("No attendance records for $selectedAttendanceSemester", color = MaterialTheme.colorScheme.onSurfaceVariant, fontSize = 13.sp)
                                        }
                                    }
                                }
                            } else {
                                items(filteredSubjects) { s ->
                                    Card(
                                        modifier = Modifier.fillMaxWidth(),
                                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant),
                                        shape = RoundedCornerShape(14.dp)
                                    ) {
                                        Column(modifier = Modifier.padding(14.dp)) {
                                            Row(
                                                modifier = Modifier.fillMaxWidth(),
                                                horizontalArrangement = Arrangement.SpaceBetween,
                                                verticalAlignment = Alignment.CenterVertically
                                            ) {
                                                Column(modifier = Modifier.weight(1f)) {
                                                    Text(s.subjectName, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onSurface, fontSize = 13.5.sp)
                                                    Text("${s.subjectCode} • ${s.semester ?: ""}", fontSize = 11.sp, color = MaterialTheme.colorScheme.primaryContainer)
                                                }
                                                val sPct = s.percentage
                                                val sColor = if (sPct >= 75) Color(0xFF10B981) else if (sPct >= 65) Color(0xFFF59E0B) else Color(0xFFEF4444)
                                                Text(
                                                    text = "$sPct%",
                                                    fontSize = 18.sp,
                                                    fontWeight = FontWeight.ExtraBold,
                                                    color = sColor
                                                )
                                            }

                                            Spacer(modifier = Modifier.height(8.dp))

                                            val sPctFloat = (s.percentage / 100.0).toFloat().coerceIn(0f, 1f)
                                            LinearProgressIndicator(
                                                progress = { sPctFloat },
                                                modifier = Modifier
                                                    .fillMaxWidth()
                                                    .height(6.dp)
                                                    .clip(RoundedCornerShape(3.dp)),
                                                color = if (sPctFloat >= 0.75f) Color(0xFF10B981) else if (sPctFloat >= 0.65f) Color(0xFFF59E0B) else Color(0xFFEF4444),
                                                trackColor = MaterialTheme.colorScheme.outlineVariant
                                            )

                                            Spacer(modifier = Modifier.height(6.dp))
                                            Row(
                                                modifier = Modifier.fillMaxWidth(),
                                                horizontalArrangement = Arrangement.SpaceBetween
                                            ) {
                                                Text(
                                                    text = "${s.present} / ${s.total} Classes Attended",
                                                    fontSize = 11.5.sp,
                                                    color = Color(0xFFCBD5E1)
                                                )
                                                if (s.extra > 0) {
                                                    Text(
                                                        text = "+${s.extra} Extra",
                                                        fontSize = 11.sp,
                                                        fontWeight = FontWeight.Bold,
                                                        color = Color(0xFF38BDF8)
                                                    )
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            // Monthly Attendance Breakdown Section
                            if (monthlyList.isNotEmpty()) {
                                item {
                                    Spacer(modifier = Modifier.height(10.dp))
                                    Text(
                                        text = if (isAllSem) "MONTHLY ATTENDANCE TIMELINE" else "$selectedAttendanceSemester MONTHLY BREAKDOWN",
                                        fontSize = 12.sp,
                                        fontWeight = FontWeight.Bold,
                                        color = MaterialTheme.colorScheme.onSurfaceVariant
                                    )
                                }

                                items(monthlyList) { m ->
                                    Card(
                                        modifier = Modifier.fillMaxWidth(),
                                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant),
                                        shape = RoundedCornerShape(12.dp)
                                    ) {
                                        Row(
                                            modifier = Modifier
                                                .fillMaxWidth()
                                                .padding(12.dp),
                                            horizontalArrangement = Arrangement.SpaceBetween,
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            Column {
                                                Text(m.month, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onSurface, fontSize = 13.sp)
                                                Text("${m.present}/${m.total} classes • ${m.semester}", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                                            }
                                            Text(
                                                text = "${m.percentage}%",
                                                fontWeight = FontWeight.ExtraBold,
                                                fontSize = 15.sp,
                                                color = if (m.percentage >= 75) Color(0xFF10B981) else if (m.percentage >= 65) Color(0xFFF59E0B) else Color(0xFFEF4444)
                                            )
                                        }
                                    }
                                }
                            }
                        }
                    }

                    AppTab.CAREER -> {
                        LazyColumn(
                            modifier = Modifier
                                .fillMaxSize()
                                .padding(16.dp),
                            verticalArrangement = Arrangement.spacedBy(12.dp)
                        ) {
                            // SmartScore
                            item {
                                Card(
                                    modifier = Modifier.fillMaxWidth(),
                                    colors = CardDefaults.cardColors(containerColor = Color(0xFF065F46)),
                                    shape = RoundedCornerShape(18.dp)
                                ) {
                                    Column(modifier = Modifier.padding(16.dp)) {
                                        Text("SMARTSCORE READINESS", fontSize = 11.sp, fontWeight = FontWeight.Bold, color = Color(0xFFA7F3D0))
                                        Text("${smartScore?.overallScore ?: "N/A"}/100", fontSize = 24.sp, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onSurface)
                                        Text("College Rank #${smartScore?.rank ?: "--"}", fontSize = 12.sp, color = Color(0xFFD1FAE5))
                                    }
                                }
                            }

                            item {
                                Text("JOB OFFERS (${placements?.totalOffers ?: 0})", fontSize = 12.sp, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onSurfaceVariant)
                            }

                            val offers = placements?.offers ?: emptyList()
                            if (offers.isEmpty()) {
                                item { Text("No job offers yet.", color = MaterialTheme.colorScheme.outline, fontSize = 12.sp) }
                            } else {
                                items(offers) { o ->
                                    Card(
                                        modifier = Modifier.fillMaxWidth(),
                                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant),
                                        shape = RoundedCornerShape(14.dp)
                                    ) {
                                        Row(
                                            modifier = Modifier
                                                .fillMaxWidth()
                                                .padding(14.dp),
                                            horizontalArrangement = Arrangement.SpaceBetween,
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            Column {
                                                Text(o.companyName, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onSurface, fontSize = 14.sp)
                                                Text(o.jobRole ?: "Software Engineer", fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                                            }
                                            Text("${o.packageLpa ?: "4.0"} LPA", fontSize = 15.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFF34D399))
                                        }
                                    }
                                }
                            }

                            item {
                                Text("NPTEL CERTIFICATES (${nptelList?.totalCertificates ?: 0})", fontSize = 12.sp, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onSurfaceVariant)
                            }

                            val certs = nptelList?.certificates ?: emptyList()
                            if (certs.isEmpty()) {
                                item { Text("No NPTEL certificates on record.", color = MaterialTheme.colorScheme.outline, fontSize = 12.sp) }
                            } else {
                                items(certs) { c ->
                                    Card(
                                        modifier = Modifier.fillMaxWidth(),
                                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant),
                                        shape = RoundedCornerShape(14.dp)
                                    ) {
                                        Row(
                                            modifier = Modifier
                                                .fillMaxWidth()
                                                .padding(14.dp),
                                            horizontalArrangement = Arrangement.SpaceBetween,
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            Column(modifier = Modifier.weight(1f)) {
                                                Text(c.courseName, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onSurface, fontSize = 13.sp)
                                                Text("Score: ${c.scorePercentage}%", fontSize = 11.5.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                                            }
                                            Text(c.gradeLabel ?: "Certified", fontSize = 11.sp, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.primaryContainer)
                                        }
                                    }
                                }
                            }
                        }
                    }

                    AppTab.PROFILE -> {
                        LazyColumn(
                            modifier = Modifier
                                .fillMaxSize()
                                .padding(16.dp),
                            verticalArrangement = Arrangement.spacedBy(16.dp),
                            horizontalAlignment = Alignment.CenterHorizontally
                        ) {
                            item {
                                val photoUrl = getFullPhotoUrl(currentStudent?.photoUrl)
                                Box(
                                    modifier = Modifier
                                        .size(90.dp)
                                        .clip(CircleShape)
                                        .border(3.dp, MaterialTheme.colorScheme.primaryContainer, CircleShape)
                                        .clickable { showEditProfileDialog = true },
                                    contentAlignment = Alignment.Center
                                ) {
                                    if (photoUrl != null) {
                                        AsyncImage(
                                            model = photoUrl,
                                            contentDescription = "Avatar",
                                            modifier = Modifier.fillMaxSize(),
                                            contentScale = ContentScale.Crop
                                        )
                                    } else {
                                        Box(
                                            modifier = Modifier
                                                .fillMaxSize()
                                                .background(MaterialTheme.colorScheme.primary),
                                            contentAlignment = Alignment.Center
                                        ) {
                                            Text("👤", fontSize = 46.sp)
                                        }
                                    }
                                }
                            }

                            item {
                                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                                    Text(
                                        text = currentStudent?.studentName ?: "Student",
                                        fontSize = 20.sp,
                                        fontWeight = FontWeight.Bold,
                                        color = MaterialTheme.colorScheme.onSurface
                                    )
                                    Text(
                                        text = currentStudent?.rollNo ?: "",
                                        fontSize = 14.sp,
                                        fontWeight = FontWeight.SemiBold,
                                        color = Color(0xFF38BDF8)
                                    )
                                }
                            }

                            item {
                                Card(
                                    modifier = Modifier.fillMaxWidth(),
                                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant),
                                    shape = RoundedCornerShape(18.dp)
                                ) {
                                    Column(modifier = Modifier.padding(18.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
                                        ProfileRow("College Email", currentStudent?.email ?: "${activeRoll?.lowercase()}@pvpsit.ac.in")
                                        HorizontalDivider(color = MaterialTheme.colorScheme.outlineVariant)
                                        ProfileRow("Father's Name", currentStudent?.fatherName ?: "N/A")
                                        HorizontalDivider(color = MaterialTheme.colorScheme.outlineVariant)
                                        ProfileRow("Department", currentStudent?.department ?: "N/A")
                                        HorizontalDivider(color = MaterialTheme.colorScheme.outlineVariant)
                                        ProfileRow("Date of Birth", currentStudent?.formattedDob ?: "Not Set")
                                    }
                                }
                            }

                            item {
                                Card(
                                    modifier = Modifier.fillMaxWidth(),
                                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant),
                                    shape = RoundedCornerShape(18.dp)
                                ) {
                                    Column(modifier = Modifier.padding(18.dp)) {
                                        Text("SECURITY & BIOMETRICS", fontSize = 11.sp, fontWeight = FontWeight.Bold, color = Color(0xFF94A3B8))
                                        Spacer(modifier = Modifier.height(10.dp))
                                        Row(
                                            modifier = Modifier.fillMaxWidth(),
                                            horizontalArrangement = Arrangement.SpaceBetween,
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            Column(modifier = Modifier.weight(1f)) {
                                                Text("Fingerprint / Face Unlock", fontWeight = FontWeight.Bold, fontSize = 14.sp, color = MaterialTheme.colorScheme.onSurface)
                                                Text("Instant login using device biometric security", fontSize = 11.5.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                                            }
                                            Switch(
                                                checked = isBiometricOn,
                                                onCheckedChange = { checked ->
                                                    isBiometricOn = checked
                                                    sessionManager.setBiometricEnabled(checked)
                                                    Toast.makeText(
                                                        context,
                                                        if (checked) "Biometric unlock enabled" else "Biometric unlock disabled",
                                                        Toast.LENGTH_SHORT
                                                    ).show()
                                                }
                                            )
                                        }
                                    }
                                }
                            }

                            item {
                                Button(
                                    onClick = { showEditProfileDialog = true },
                                    modifier = Modifier.fillMaxWidth().height(50.dp),
                                    colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.primary),
                                    shape = RoundedCornerShape(14.dp)
                                ) {
                                    Text("🔒 Edit Photo & DOB (Email OTP)", fontWeight = FontWeight.Bold)
                                }
                            }

                            item {
                                Button(
                                    onClick = {
                                        activeRoll = null
                                        currentStudent = null
                                    },
                                    modifier = Modifier.fillMaxWidth().height(50.dp),
                                    colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.outlineVariant),
                                    shape = RoundedCornerShape(14.dp)
                                ) {
                                    Text("🔍 Search Another Student", fontWeight = FontWeight.Bold)
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun StatCard(title: String, value: String, iconEmoji: String, color: Color, modifier: Modifier = Modifier) {
    Box(
        modifier = modifier
            .shadow(8.dp, RoundedCornerShape(18.dp))
            .clip(RoundedCornerShape(18.dp))
            .background(Color(0xFF1E293B))
            .border(
                width = 1.dp,
                brush = Brush.linearGradient(listOf(color.copy(alpha = 0.4f), color.copy(alpha = 0.1f))),
                shape = RoundedCornerShape(18.dp)
            )
    ) {
        // Subtle gradient tint in corner
        Box(
            modifier = Modifier
                .size(60.dp)
                .align(Alignment.TopEnd)
                .blur(20.dp)
                .background(
                    Brush.radialGradient(listOf(color.copy(alpha = 0.3f), Color.Transparent)),
                    CircleShape
                )
        )
        Row(
            modifier = Modifier.padding(16.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            Box(
                modifier = Modifier
                    .size(42.dp)
                    .clip(RoundedCornerShape(14.dp))
                    .background(
                        Brush.linearGradient(listOf(color.copy(alpha = 0.25f), color.copy(alpha = 0.1f)))
                    ),
                contentAlignment = Alignment.Center
            ) {
                Text(iconEmoji, fontSize = 20.sp)
            }
            Column {
                Text(value, fontSize = 18.sp, fontWeight = FontWeight.ExtraBold, color = Color.White)
                Text(title, fontSize = 9.5.sp, fontWeight = FontWeight.Bold, color = Color(0xFF94A3B8), letterSpacing = 0.3.sp)
            }
        }
    }
}

@Composable
fun ProfileRow(label: String, value: String) {
    Row(
        modifier = Modifier.fillMaxWidth(),
        horizontalArrangement = Arrangement.SpaceBetween
    ) {
        Text(label, fontSize = 12.5.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
        Text(value, fontSize = 12.5.sp, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onSurface)
    }
}
