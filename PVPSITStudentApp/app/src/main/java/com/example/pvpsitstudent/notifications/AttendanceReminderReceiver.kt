package com.example.pvpsitstudent.notifications

import android.app.NotificationManager
import android.app.PendingIntent
import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.os.Build
import android.util.Log
import androidx.core.app.NotificationCompat
import com.example.pvpsitstudent.MainActivity
import com.example.pvpsitstudent.R
import com.example.pvpsitstudent.data.local.SessionManager
import kotlin.random.Random

class AttendanceReminderReceiver : BroadcastReceiver() {

    companion object {
        const val ACTION_REMINDER = "com.example.pvpsitstudent.ACTION_DAILY_ATTENDANCE_REMINDER"

        // Uplifting and motivational daily attendance messages
        val INSPIRATIONAL_MESSAGES = listOf(
            "Every lecture brings you one step closer to your dreams! Mark your presence today. 🌟",
            "Consistency is the secret code to success! Don't miss today's classes. 🚀",
            "80% of success is showing up! Keep your attendance buffer high and stress low. 🛡️",
            "Your future self will thank you for being in class today! Have a wonderful day ahead! ☀️",
            "Today's efforts build tomorrow's achievements! Keep your streak strong! 💪",
            "Great things never come from comfort zones. Be present, stay sharp! 🎯",
            "Education is the passport to the future. Step into your classes with energy! 📚"
        )
    }

    override fun onReceive(context: Context, intent: Intent?) {
        Log.d("AttendanceReminder", "Received broadcast action: ${intent?.action}")

        val sessionManager = SessionManager(context)

        // If phone restarted, re-schedule the alarm
        if (intent?.action == Intent.ACTION_BOOT_COMPLETED) {
            if (sessionManager.isReminderEnabled()) {
                AttendanceReminderScheduler.scheduleDailyReminder(context)
            }
            return
        }

        // Only send if reminder is enabled
        if (!sessionManager.isReminderEnabled()) {
            return
        }

        val studentProfile = sessionManager.getStudentProfile()
        val studentName = studentProfile?.studentName?.split(" ")?.firstOrNull() ?: "Champion"
        val attendancePct = studentProfile?.attendanceSummary?.percentage

        // Craft tailored message based on attendance percentage
        val title: String
        val body: String

        if (attendancePct != null) {
            val pctStr = String.format("%.1f", attendancePct)
            if (attendancePct >= 75.0) {
                title = "Good Morning, $studentName! ($pctStr% Attendance) 🌟"
                val randomQuote = INSPIRATIONAL_MESSAGES[Random.nextInt(INSPIRATIONAL_MESSAGES.size)]
                body = "Great job maintaining $pctStr%! $randomQuote"
            } else if (attendancePct >= 65.0) {
                title = "Condonation Zone: $studentName ($pctStr%) 📋"
                body = "You are at $pctStr% (Eligible with Condonation). Attend upcoming classes to push past 75% and avoid fines! 💪"
            } else {
                title = "Attendance Alert, $studentName! ($pctStr%) ⚠️"
                body = "Critical: attendance is at $pctStr% (<65%). Attend all upcoming classes to reach condonation minimum and avoid detention! 🚨"
            }
        } else {
            title = "Good Morning, $studentName! ☀️"
            val randomQuote = INSPIRATIONAL_MESSAGES[Random.nextInt(INSPIRATIONAL_MESSAGES.size)]
            body = "Time to shine today! $randomQuote Tap to check your attendance status."
        }

        sendNotification(context, title, body)

        // Reschedule for the next day
        AttendanceReminderScheduler.scheduleDailyReminder(context)
    }

    private fun sendNotification(context: Context, title: String, message: String) {
        val notificationManager = context.getSystemService(Context.NOTIFICATION_SERVICE) as? NotificationManager ?: return

        // Ensure channel exists
        AttendanceReminderScheduler.createNotificationChannel(context)

        val openAppIntent = Intent(context, MainActivity::class.java).apply {
            flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK
        }

        val pendingIntentFlags = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        } else {
            PendingIntent.FLAG_UPDATE_CURRENT
        }

        val pendingIntent = PendingIntent.getActivity(
            context,
            0,
            openAppIntent,
            pendingIntentFlags
        )

        val notification = NotificationCompat.Builder(context, AttendanceReminderScheduler.CHANNEL_ID)
            .setSmallIcon(R.drawable.ic_notification_attendance)
            .setContentTitle(title)
            .setContentText(message)
            .setStyle(NotificationCompat.BigTextStyle().bigText(message))
            .setPriority(NotificationCompat.PRIORITY_HIGH)
            .setAutoCancel(true)
            .setContentIntent(pendingIntent)
            .setDefaults(NotificationCompat.DEFAULT_ALL)
            .build()

        notificationManager.notify(AttendanceReminderScheduler.NOTIFICATION_ID, notification)
        Log.d("AttendanceReminder", "Notification posted: $title")
    }
}
