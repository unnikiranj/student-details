# Android Kotlin Client Integration Guide
## PHP + MySQL REST API → Kotlin Android App

This starter kit provides a complete, production-ready Android client module in Kotlin to connect your Android application to the **PVPSIT Student Portal PHP REST API backend**.

---

## 1. Directory Structure

```
android_kotlin/
├── models/
│   ├── ApiResponse.kt           // Standard generic API response wrapper
│   └── StudentModels.kt         // Data classes for Profile, Attendance, Results, etc.
├── network/
│   ├── ApiClient.kt             // Retrofit 2 + OkHttp Client Singleton
│   ├── AuthInterceptor.kt       // Interceptor attaching Bearer Token automatically
│   └── StudentApiService.kt     // Retrofit Interface with suspend functions
├── repository/
│   └── StudentRepository.kt     // Coroutine Repository handling background dispatchers
└── README.md                    // Setup and usage guide
```

---

## 2. Android Studio Setup

### A. Add Dependencies in `app/build.gradle.kts`

```kotlin
dependencies {
    // Retrofit & Gson Converter
    implementation("com.squareup.retrofit2:retrofit:2.9.0")
    implementation("com.squareup.retrofit2:converter-gson:2.9.0")

    // OkHttp & Logging Interceptor
    implementation("com.squareup.okhttp3:okhttp:4.12.0")
    implementation("com.squareup.okhttp3:logging-interceptor:4.12.0")

    // Kotlin Coroutines
    implementation("org.jetbrains.kotlinx:kotlinx-coroutines-android:1.8.0")

    // Lifecycle & ViewModel
    implementation("androidx.lifecycle:lifecycle-viewmodel-ktx:2.7.0")
    implementation("androidx.lifecycle:lifecycle-runtime-ktx:2.7.0")

    // Coil Image Loading (for Compose / Views)
    implementation("io.coil-kt:coil-compose:2.6.0")
}
```

### B. Network Permissions in `AndroidManifest.xml`

```xml
<manifest xmlns:android="http://schemas.android.com/apk/res/android">

    <uses-permission android:name="android.permission.INTERNET" />
    <uses-permission android:name="android.permission.ACCESS_NETWORK_STATE" />

    <application
        android:allowBackup="true"
        android:usesCleartextTraffic="true" <!-- Required for local HTTP development -->
        android:label="@string/app_name"
        android:theme="@style/Theme.StudentPortal">
        <!-- Activities -->
    </application>
</manifest>
```

---

## 3. Base URL Configuration

In `ApiClient.kt`, set the base URL depending on your testing environment:

| Target Environment | URL Configuration |
|---|---|
| **Android Emulator** | `http://10.0.2.2/studentdetails/` |
| **Physical Phone (Same Wi-Fi)** | `http://192.168.1.X/studentdetails/` *(Replace with your PC's IP)* |
| **Production Server** | `https://studentportal.pvpsit.ac.in/` |

---

## 4. Usage in Android ViewModel

```kotlin
package com.pvpsit.studentportal.ui.viewmodel

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.pvpsit.studentportal.data.models.StudentProfile
import com.pvpsit.studentportal.data.network.ApiClient
import com.pvpsit.studentportal.data.repository.StudentRepository
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch

sealed class UiState<out T> {
    object Loading : UiState<Nothing>()
    data class Success<out T>(val data: T) : UiState<T>()
    data class Error(val message: String) : UiState<Nothing>()
}

class StudentViewModel : ViewModel() {

    private val repository = StudentRepository(ApiClient.getService())

    private val _loginState = MutableStateFlow<UiState<String>?>(null)
    val loginState: StateFlow<UiState<String>?> = _loginState

    private val _profileState = MutableStateFlow<UiState<StudentProfile>?>(null)
    val profileState: StateFlow<UiState<StudentProfile>?> = _profileState

    // 1. Send OTP to student college email
    fun sendOtp(rollNumber: String) {
        viewModelScope.launch {
            _loginState.value = UiState.Loading
            repository.sendOtp(rollNumber).fold(
                onSuccess = { response ->
                    _loginState.value = UiState.Success("OTP sent to ${response.email}")
                },
                onFailure = { error ->
                    _loginState.value = UiState.Error(error.message ?: "Failed to send OTP")
                }
            )
        }
    }

    // 2. Verify OTP & Store Bearer Token
    fun verifyOtp(rollNumber: String, otp: String) {
        viewModelScope.launch {
            _loginState.value = UiState.Loading
            repository.verifyOtp(rollNumber, otp).fold(
                onSuccess = { authSuccess ->
                    // Set token in ApiClient for subsequent requests
                    ApiClient.setAuthToken(authSuccess.auth.token)
                    _loginState.value = UiState.Success("Authenticated as ${authSuccess.student.studentName}")
                    _profileState.value = UiState.Success(authSuccess.student)
                },
                onFailure = { error ->
                    _loginState.value = UiState.Error(error.message ?: "Invalid OTP")
                }
            )
        }
    }

    // 3. Load Profile Data
    fun loadProfile() {
        viewModelScope.launch {
            _profileState.value = UiState.Loading
            repository.getMyProfile().fold(
                onSuccess = { profile ->
                    _profileState.value = UiState.Success(profile)
                },
                onFailure = { error ->
                    _profileState.value = UiState.Error(error.message ?: "Failed to load profile")
                }
            )
        }
    }
}
```

---

## 5. Jetpack Compose UI Example

```kotlin
package com.pvpsit.studentportal.ui.screens

import androidx.compose.foundation.layout.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import coil.compose.AsyncImage
import com.pvpsit.studentportal.data.models.StudentProfile
import com.pvpsit.studentportal.data.network.ApiClient

@Composable
fun StudentProfileCard(student: StudentProfile) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(16.dp),
        elevation = CardDefaults.cardElevation(defaultElevation = 6.dp)
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            // Profile Photo with Coil
            student.photoUrl?.let { relativeUrl ->
                val fullUrl = ApiClient.BASE_URL + relativeUrl
                AsyncImage(
                    model = fullUrl,
                    contentDescription = "Profile Photo",
                    modifier = Modifier.size(100.dp)
                )
            }

            Spacer(modifier = Modifier.height(8.dp))
            Text(text = student.studentName, style = MaterialTheme.typography.titleLarge)
            Text(text = "Roll: ${student.rollNo}", style = MaterialTheme.typography.bodyMedium)
            Text(text = "Dept: ${student.department ?: "N/A"}", style = MaterialTheme.typography.bodyMedium)
            Text(text = "DOB: ${student.privacyDob ?: "Not Set"}", style = MaterialTheme.typography.bodyMedium)

            student.attendanceSummary?.let { att ->
                Spacer(modifier = Modifier.height(8.dp))
                Text(
                    text = "Attendance: ${att.percentage}% (${att.present}/${att.total})",
                    style = MaterialTheme.typography.bodyLarge,
                    color = MaterialTheme.colorScheme.primary
                )
            }
        }
    }
}
```

---

## 6. REST API Endpoints Reference Table

| Method | Endpoint | Description | Auth Required |
|---|---|---|---|
| `POST` | `/api/v1/auth/send_otp.php` | Request 6-digit OTP | No |
| `POST` | `/api/v1/auth/verify_otp.php` | Verify OTP & obtain Bearer Token | No |
| `POST` | `/api/v1/auth/admin_login.php` | Faculty/Admin password login | No |
| `POST` | `/api/v1/auth/logout.php` | Invalidate token | Yes (Bearer) |
| `GET` | `/api/v1/student/profile.php` | Fetch complete student profile | Yes (Bearer) |
| `POST` | `/api/v1/student/update_dob.php` | Update Date of Birth | Yes (Bearer) |
| `POST` | `/api/v1/student/update_photo.php` | Multipart Image Upload | Yes (Bearer) |
| `GET` | `/api/v1/student/results.php` | SGPA/CGPA & Subject Grades | Yes (Bearer) |
| `GET` | `/api/v1/student/attendance.php` | Monthly & Subject Attendance | Yes (Bearer) |
| `GET` | `/api/v1/student/smartscore.php` | SmartScore & Assessment Stats | Yes (Bearer) |
| `GET` | `/api/v1/student/nptel.php` | NPTEL Certifications & Scores | Yes (Bearer) |
| `GET` | `/api/v1/student/placements.php` | Placement Job Offers & LPA | Yes (Bearer) |
| `GET` | `/api/v1/birthdays/today.php` | Today's student birthdays | No |
| `GET` | `/api/v1/leaderboard.php` | College & Dept Toppers | No |
