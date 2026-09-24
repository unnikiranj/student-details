package com.pvpsit.studentportal.data.network

import com.pvpsit.studentportal.data.models.*
import okhttp3.MultipartBody
import retrofit2.Response
import retrofit2.http.*

/**
 * Retrofit 2 API Service interface for PVPSIT Student Portal REST API v1
 */
interface StudentApiService {

    // ==========================================
    // AUTHENTICATION
    // ==========================================

    @POST("api/v1/auth/send_otp.php")
    suspend fun sendOtp(
        @Body request: SendOtpRequest
    ): Response<ApiResponse<SendOtpResponse>>

    @POST("api/v1/auth/verify_otp.php")
    suspend fun verifyOtp(
        @Body request: VerifyOtpRequest
    ): Response<ApiResponse<AuthSuccessResponse>>

    @POST("api/v1/auth/logout.php")
    suspend fun logout(): Response<ApiResponse<Unit>>

    // ==========================================
    // STUDENT PROFILE
    // ==========================================

    @GET("api/v1/student/profile.php")
    suspend fun getMyProfile(): Response<ApiResponse<StudentProfile>>

    @GET("api/v1/student/profile.php")
    suspend fun getStudentProfile(
        @Query("roll") rollNo: String
    ): Response<ApiResponse<StudentProfile>>

    @POST("api/v1/student/update_dob.php")
    suspend fun updateDateOfBirth(
        @Body request: UpdateDobRequest
    ): Response<ApiResponse<Map<String, String>>>

    @Multipart
    @POST("api/v1/student/update_photo.php")
    suspend fun updateProfilePhoto(
        @Part photo: MultipartBody.Part
    ): Response<ApiResponse<UpdatePhotoResponse>>

    // ==========================================
    // ACADEMICS & ATTENDANCE
    // ==========================================

    @GET("api/v1/student/results.php")
    suspend fun getMyResults(): Response<ApiResponse<FullResultsResponse>>

    @GET("api/v1/student/results.php")
    suspend fun getStudentResults(
        @Query("roll") rollNo: String
    ): Response<ApiResponse<FullResultsResponse>>

    @GET("api/v1/student/attendance.php")
    suspend fun getMyAttendance(): Response<ApiResponse<FullAttendanceResponse>>

    @GET("api/v1/student/attendance.php")
    suspend fun getStudentAttendance(
        @Query("roll") rollNo: String
    ): Response<ApiResponse<FullAttendanceResponse>>

    // ==========================================
    // SMARTSCORE, NPTEL & PLACEMENTS
    // ==========================================

    @GET("api/v1/student/smartscore.php")
    suspend fun getMySmartScore(): Response<ApiResponse<SmartScoreResponse>>

    @GET("api/v1/student/smartscore.php")
    suspend fun getStudentSmartScore(
        @Query("roll") rollNo: String
    ): Response<ApiResponse<SmartScoreResponse>>

    @GET("api/v1/student/nptel.php")
    suspend fun getMyNptelCertificates(): Response<ApiResponse<NptelListResponse>>

    @GET("api/v1/student/nptel.php")
    suspend fun getStudentNptelCertificates(
        @Query("roll") rollNo: String
    ): Response<ApiResponse<NptelListResponse>>

    @GET("api/v1/student/placements.php")
    suspend fun getMyPlacements(): Response<ApiResponse<PlacementsResponse>>

    @GET("api/v1/student/placements.php")
    suspend fun getStudentPlacements(
        @Query("roll") rollNo: String
    ): Response<ApiResponse<PlacementsResponse>>

    // ==========================================
    // GENERAL & UTILITIES
    // ==========================================

    @GET("api/v1/birthdays/today.php")
    suspend fun getTodayBirthdays(): Response<ApiResponse<BirthdaysResponse>>

    @GET("api/v1/leaderboard.php")
    suspend fun getLeaderboard(
        @Query("limit") limit: Int = 20,
        @Query("department") department: String? = null
    ): Response<ApiResponse<LeaderboardResponse>>
}
