package com.pvpsit.studentportal.data.repository

import com.pvpsit.studentportal.data.models.*
import com.pvpsit.studentportal.data.network.StudentApiService
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.asRequestBody
import java.io.File

/**
 * Clean Repository layer abstracting API network calls with Kotlin Coroutines
 */
class StudentRepository(
    private val apiService: StudentApiService
) {

    suspend fun sendOtp(rollNumber: String): Result<SendOtpResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.sendOtp(SendOtpRequest(rollNumber))
            val body = response.body()
            if (response.isSuccessful && body?.isSuccessful == true && body.data != null) {
                Result.success(body.data)
            } else {
                Result.failure(Exception(body?.message ?: "Failed to send OTP (${response.code()})"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun verifyOtp(rollNumber: String, otp: String): Result<AuthSuccessResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.verifyOtp(VerifyOtpRequest(rollNumber, otp))
            val body = response.body()
            if (response.isSuccessful && body?.isSuccessful == true && body.data != null) {
                Result.success(body.data)
            } else {
                Result.failure(Exception(body?.message ?: "Invalid OTP (${response.code()})"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getMyProfile(): Result<StudentProfile> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getMyProfile()
            val body = response.body()
            if (response.isSuccessful && body?.isSuccessful == true && body.data != null) {
                Result.success(body.data)
            } else {
                Result.failure(Exception(body?.message ?: "Failed to load profile (${response.code()})"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun updateDateOfBirth(dateOfBirth: String): Result<String> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.updateDateOfBirth(UpdateDobRequest(dateOfBirth))
            val body = response.body()
            if (response.isSuccessful && body?.isSuccessful == true) {
                Result.success(body.message)
            } else {
                Result.failure(Exception(body?.message ?: "Failed to update DOB"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun uploadProfilePhoto(imageFile: File): Result<UpdatePhotoResponse> = withContext(Dispatchers.IO) {
        try {
            val requestFile = imageFile.asRequestBody("image/*".toMediaTypeOrNull())
            val bodyPart = MultipartBody.Part.createFormData("profile_photo", imageFile.name, requestFile)
            val response = apiService.updateProfilePhoto(bodyPart)
            val body = response.body()
            if (response.isSuccessful && body?.isSuccessful == true && body.data != null) {
                Result.success(body.data)
            } else {
                Result.failure(Exception(body?.message ?: "Failed to upload photo"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getMyAttendance(): Result<FullAttendanceResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getMyAttendance()
            val body = response.body()
            if (response.isSuccessful && body?.isSuccessful == true && body.data != null) {
                Result.success(body.data)
            } else {
                Result.failure(Exception(body?.message ?: "Failed to fetch attendance"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getMyResults(): Result<FullResultsResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getMyResults()
            val body = response.body()
            if (response.isSuccessful && body?.isSuccessful == true && body.data != null) {
                Result.success(body.data)
            } else {
                Result.failure(Exception(body?.message ?: "Failed to fetch results"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getMySmartScore(): Result<SmartScoreResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getMySmartScore()
            val body = response.body()
            if (response.isSuccessful && body?.isSuccessful == true && body.data != null) {
                Result.success(body.data)
            } else {
                Result.failure(Exception(body?.message ?: "Failed to fetch smartscore"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getMyPlacements(): Result<PlacementsResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getMyPlacements()
            val body = response.body()
            if (response.isSuccessful && body?.isSuccessful == true && body.data != null) {
                Result.success(body.data)
            } else {
                Result.failure(Exception(body?.message ?: "Failed to fetch placements"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getTodayBirthdays(): Result<BirthdaysResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getTodayBirthdays()
            val body = response.body()
            if (response.isSuccessful && body?.isSuccessful == true && body.data != null) {
                Result.success(body.data)
            } else {
                Result.failure(Exception(body?.message ?: "Failed to fetch birthdays"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getLeaderboard(limit: Int = 20, department: String? = null): Result<LeaderboardResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getLeaderboard(limit, department)
            val body = response.body()
            if (response.isSuccessful && body?.isSuccessful == true && body.data != null) {
                Result.success(body.data)
            } else {
                Result.failure(Exception(body?.message ?: "Failed to fetch leaderboard"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
