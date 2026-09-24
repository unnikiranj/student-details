package com.example.pvpsitstudent.data.repository

import com.example.pvpsitstudent.data.models.*
import com.example.pvpsitstudent.data.network.StudentApiService
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.asRequestBody
import java.io.File
import com.example.pvpsitstudent.data.local.StudentDao
import com.example.pvpsitstudent.data.local.StudentProfileEntity
import com.example.pvpsitstudent.data.local.AttendanceEntity
import com.example.pvpsitstudent.data.local.ResultsEntity
import com.google.gson.Gson
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.flow
import kotlinx.coroutines.flow.firstOrNull
import kotlinx.coroutines.flow.map

import kotlinx.coroutines.flow.flowOn

class StudentRepository(
    private val apiService: StudentApiService,
    private val studentDao: StudentDao
) {
    private val gson = Gson()

    fun getProfile(rollNumber: String): Flow<Result<StudentProfile>> = flow {
        // 1. Emit from cache first
        val cached = studentDao.getProfile(rollNumber).firstOrNull()
        if (cached != null) {
            emit(Result.success(cached.toDomain(gson)))
        }
        
        // 2. Fetch from network
        try {
            val response = apiService.getStudentProfile(rollNumber.trim().uppercase())
            val body = response.body()
            if (response.isSuccessful && body?.isSuccessful == true && body.data != null) {
                // 3. Save to cache
                studentDao.insertProfile(StudentProfileEntity.fromDomain(body.data, gson))
                // 4. Emit fresh data
                emit(Result.success(body.data))
            } else if (cached == null) {
                emit(Result.failure(Exception(body?.message ?: "Student not found (${response.code()})")))
            }
        } catch (e: Exception) {
            if (cached == null) {
                emit(Result.failure(e))
            }
        }
    }.flowOn(Dispatchers.IO)

    fun getAttendance(rollNumber: String): Flow<Result<FullAttendanceResponse>> = flow {
        val cached = studentDao.getAttendance(rollNumber).firstOrNull()
        if (cached != null) {
            emit(Result.success(cached.toDomain(gson)))
        }

        try {
            val response = apiService.getStudentAttendance(rollNumber.trim().uppercase())
            val body = response.body()
            if (response.isSuccessful && body?.isSuccessful == true && body.data != null) {
                studentDao.insertAttendance(AttendanceEntity.fromDomain(rollNumber, body.data, gson))
                emit(Result.success(body.data))
            } else if (cached == null) {
                emit(Result.failure(Exception(body?.message ?: "Failed to fetch attendance")))
            }
        } catch (e: Exception) {
            if (cached == null) emit(Result.failure(e))
        }
    }.flowOn(Dispatchers.IO)

    fun getResults(rollNumber: String): Flow<Result<FullResultsResponse>> = flow {
        val cached = studentDao.getResults(rollNumber).firstOrNull()
        if (cached != null) {
            emit(Result.success(cached.toDomain(gson)))
        }

        try {
            val response = apiService.getStudentResults(rollNumber.trim().uppercase())
            val body = response.body()
            if (response.isSuccessful && body?.isSuccessful == true && body.data != null) {
                studentDao.insertResults(ResultsEntity.fromDomain(rollNumber, body.data, gson))
                emit(Result.success(body.data))
            } else if (cached == null) {
                emit(Result.failure(Exception(body?.message ?: "Failed to fetch results")))
            }
        } catch (e: Exception) {
            if (cached == null) emit(Result.failure(e))
        }
    }.flowOn(Dispatchers.IO)

    suspend fun getSmartScore(rollNumber: String? = null): Result<SmartScoreResponse> = withContext(Dispatchers.IO) {
        try {
            val response = if (rollNumber.isNullOrBlank()) {
                apiService.getMySmartScore()
            } else {
                apiService.getStudentSmartScore(rollNumber.trim().uppercase())
            }
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

    suspend fun getMidmarks(rollNumber: String? = null): Result<MidmarksResponse> = withContext(Dispatchers.IO) {
        try {
            val response = if (rollNumber.isNullOrBlank()) {
                apiService.getMyMidmarks()
            } else {
                apiService.getStudentMidmarks(rollNumber.trim().uppercase())
            }
            val body = response.body()
            if (response.isSuccessful && body?.isSuccessful == true && body.data != null) {
                Result.success(body.data)
            } else {
                Result.failure(Exception(body?.message ?: "Failed to fetch mid-term marks"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getPlacements(rollNumber: String? = null): Result<PlacementsResponse> = withContext(Dispatchers.IO) {
        try {
            val response = if (rollNumber.isNullOrBlank()) {
                apiService.getMyPlacements()
            } else {
                apiService.getStudentPlacements(rollNumber.trim().uppercase())
            }
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

    suspend fun getNptelCertificates(rollNumber: String? = null): Result<NptelListResponse> = withContext(Dispatchers.IO) {
        try {
            val response = if (rollNumber.isNullOrBlank()) {
                apiService.getMyNptelCertificates()
            } else {
                apiService.getStudentNptelCertificates(rollNumber.trim().uppercase())
            }
            val body = response.body()
            if (response.isSuccessful && body?.isSuccessful == true && body.data != null) {
                Result.success(body.data)
            } else {
                Result.failure(Exception(body?.message ?: "Failed to fetch NPTEL certs"))
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

    suspend fun logout(): Result<Unit> = withContext(Dispatchers.IO) {
        try {
            apiService.logout()
            Result.success(Unit)
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun updateDateOfBirth(dateOfBirth: String, rollNumber: String? = null): Result<String> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.updateDateOfBirth(UpdateDobRequest(dateOfBirth, rollNumber))
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

    suspend fun uploadProfilePhoto(imageFile: File, rollNumber: String? = null): Result<UpdatePhotoResponse> = withContext(Dispatchers.IO) {
        try {
            val requestFile = imageFile.asRequestBody("image/*".toMediaTypeOrNull())
            val bodyPart = MultipartBody.Part.createFormData("profile_photo", imageFile.name, requestFile)
            val rollPart = rollNumber?.let { okhttp3.RequestBody.create("text/plain".toMediaTypeOrNull(), it) }
            val response = apiService.updateProfilePhoto(bodyPart, rollPart)
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
}
