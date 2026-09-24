package com.example.pvpsitstudent

import android.Manifest
import android.content.pm.PackageManager
import android.os.Build
import android.os.Bundle
import androidx.fragment.app.FragmentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.ui.Modifier
import androidx.core.content.ContextCompat
import com.example.pvpsitstudent.notifications.AttendanceReminderScheduler
import com.example.pvpsitstudent.theme.PVPSITStudentTheme

class MainActivity : FragmentActivity() {

  private val requestPermissionLauncher = registerForActivityResult(
    ActivityResultContracts.RequestPermission()
  ) { isGranted ->
    if (isGranted) {
      AttendanceReminderScheduler.scheduleDailyReminder(this)
    }
  }

  override fun onCreate(savedInstanceState: Bundle?) {
    super.onCreate(savedInstanceState)

    // Schedule daily attendance reminder
    AttendanceReminderScheduler.createNotificationChannel(this)
    if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
      if (ContextCompat.checkSelfPermission(this, Manifest.permission.POST_NOTIFICATIONS) == PackageManager.PERMISSION_GRANTED) {
        AttendanceReminderScheduler.scheduleDailyReminder(this)
      } else {
        requestPermissionLauncher.launch(Manifest.permission.POST_NOTIFICATIONS)
      }
    } else {
      AttendanceReminderScheduler.scheduleDailyReminder(this)
    }

    enableEdgeToEdge()
    setContent {
      PVPSITStudentTheme { Surface(modifier = Modifier.fillMaxSize(), color = MaterialTheme.colorScheme.background) { MainNavigation() } }
    }
  }
}
