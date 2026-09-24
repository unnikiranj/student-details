package com.example.pvpsitstudent.data.local

import android.content.Context
import android.content.SharedPreferences
import com.example.pvpsitstudent.data.models.StudentProfile
import com.google.gson.Gson

/**
 * Manages persistent user session so the user stays logged in until they explicitly log out
 */
class SessionManager(context: Context) {

    private val prefs: SharedPreferences = context.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE)
    private val gson = Gson()

    companion object {
        private const val PREF_NAME = "pvpsit_student_session"
        private const val KEY_AUTH_TOKEN = "key_auth_token"
        private const val KEY_ROLL_NUMBER = "key_roll_number"
        private const val KEY_STUDENT_PROFILE = "key_student_profile"
        private const val KEY_SERVER_URL = "key_server_url"
        private const val KEY_REMINDER_ENABLED = "key_reminder_enabled"
        private const val KEY_REMINDER_HOUR = "key_reminder_hour"
        private const val KEY_REMINDER_MINUTE = "key_reminder_minute"
        private const val KEY_BIOMETRIC_ENABLED = "key_biometric_enabled"
    }

    fun isBiometricEnabled(): Boolean {
        // Defaults to true so students with enrolled fingerprint can use biometric unlock immediately
        return prefs.getBoolean(KEY_BIOMETRIC_ENABLED, true)
    }

    fun setBiometricEnabled(enabled: Boolean) {
        prefs.edit().putBoolean(KEY_BIOMETRIC_ENABLED, enabled).apply()
    }

    fun isReminderEnabled(): Boolean {
        // Default to enabled so students get their helpful daily attendance reminder
        return prefs.getBoolean(KEY_REMINDER_ENABLED, true)
    }

    fun setReminderEnabled(enabled: Boolean) {
        prefs.edit().putBoolean(KEY_REMINDER_ENABLED, enabled).apply()
    }

    fun getReminderHour(): Int {
        return prefs.getInt(KEY_REMINDER_HOUR, 8) // Default 8 AM
    }

    fun getReminderMinute(): Int {
        return prefs.getInt(KEY_REMINDER_MINUTE, 30) // Default 8:30 AM
    }

    fun setReminderTime(hour: Int, minute: Int) {
        prefs.edit()
            .putInt(KEY_REMINDER_HOUR, hour)
            .putInt(KEY_REMINDER_MINUTE, minute)
            .apply()
    }

    fun saveServerUrl(url: String) {
        prefs.edit().putString(KEY_SERVER_URL, url).apply()
    }

    fun getServerUrl(): String? {
        val saved = prefs.getString(KEY_SERVER_URL, null)
        if (saved != null && saved.contains("192.168.1.37")) {
            return null
        }
        return saved
    }

    fun saveAuthToken(token: String) {
        prefs.edit().putString(KEY_AUTH_TOKEN, token).apply()
    }

    fun getAuthToken(): String? {
        return prefs.getString(KEY_AUTH_TOKEN, null)
    }

    fun saveRollNumber(rollNumber: String) {
        prefs.edit().putString(KEY_ROLL_NUMBER, rollNumber).apply()
    }

    fun getRollNumber(): String? {
        return prefs.getString(KEY_ROLL_NUMBER, null)
    }

    fun saveStudentProfile(profile: StudentProfile) {
        val json = gson.toJson(profile)
        prefs.edit().putString(KEY_STUDENT_PROFILE, json).apply()
    }

    fun getStudentProfile(): StudentProfile? {
        val json = prefs.getString(KEY_STUDENT_PROFILE, null) ?: return null
        return try {
            gson.fromJson(json, StudentProfile::class.java)
        } catch (e: Exception) {
            null
        }
    }

    fun isLoggedIn(): Boolean {
        return !getAuthToken().isNullOrBlank()
    }

    fun clearSession() {
        prefs.edit().clear().apply()
    }
}
