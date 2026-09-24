package com.pvpsit.studentportal.data.models

import com.google.gson.annotations.SerializedName

// ==========================================
// AUTH & LOGIN MODELS
// ==========================================

data class SendOtpRequest(
    @SerializedName("roll_number")
    val rollNumber: String
)

data class SendOtpResponse(
    @SerializedName("roll_number")
    val rollNumber: String,

    @SerializedName("email")
    val email: String,

    @SerializedName("expires_in")
    val expiresIn: Int,

    @SerializedName("otp")
    val otpFallback: String? = null
)

data class VerifyOtpRequest(
    @SerializedName("roll_number")
    val rollNumber: String,

    @SerializedName("otp")
    val otp: String
)

data class AuthTokenData(
    @SerializedName("token")
    val token: String,

    @SerializedName("token_type")
    val tokenType: String,

    @SerializedName("expires_at")
    val expiresAt: String,

    @SerializedName("expires_in")
    val expiresIn: Long
)

data class AuthSuccessResponse(
    @SerializedName("auth")
    val auth: AuthTokenData,

    @SerializedName("student")
    val student: StudentProfile
)

// ==========================================
// STUDENT PROFILE MODELS
// ==========================================

data class StudentProfile(
    @SerializedName("id")
    val id: Int?,

    @SerializedName("roll_no")
    val rollNo: String,

    @SerializedName("student_name")
    val studentName: String,

    @SerializedName("email")
    val email: String?,

    @SerializedName("phone")
    val phone: String?,

    @SerializedName("date_of_birth")
    val dateOfBirth: String?,

    @SerializedName("formatted_dob")
    val formattedDob: String?,

    @SerializedName("privacy_dob")
    val privacyDob: String?,

    @SerializedName("father_name")
    val fatherName: String?,

    @SerializedName("department")
    val department: String?,

    @SerializedName("photo_url")
    val photoUrl: String?,

    @SerializedName("attendance_summary")
    val attendanceSummary: AttendanceSummary?,

    @SerializedName("academic_summary")
    val academicSummary: AcademicSummary?
)

data class UpdateDobRequest(
    @SerializedName("date_of_birth")
    val dateOfBirth: String // Format: YYYY-MM-DD
)

data class UpdatePhotoResponse(
    @SerializedName("roll_no")
    val rollNo: String,

    @SerializedName("photo_name")
    val photoName: String,

    @SerializedName("photo_url")
    val photoUrl: String
)

// ==========================================
// ATTENDANCE MODELS
// ==========================================

data class AttendanceSummary(
    @SerializedName("present")
    val present: Int,

    @SerializedName("total")
    val total: Int,

    @SerializedName("percentage")
    val percentage: Double,

    @SerializedName("status")
    val status: String? = null
)

data class MonthlyAttendance(
    @SerializedName("session_name")
    val sessionName: String,

    @SerializedName("month")
    val month: String,

    @SerializedName("semester")
    val semester: String,

    @SerializedName("academic_year")
    val academicYear: String,

    @SerializedName("present")
    val present: Int,

    @SerializedName("total")
    val total: Int,

    @SerializedName("percentage")
    val percentage: Double
)

data class SubjectAttendance(
    @SerializedName("subject_code")
    val subjectCode: String,

    @SerializedName("subject_name")
    val subjectName: String,

    @SerializedName("semester")
    val semester: String?,

    @SerializedName("month")
    val month: String?,

    @SerializedName("present")
    val present: Int,

    @SerializedName("extra")
    val extra: Int,

    @SerializedName("total")
    val total: Int,

    @SerializedName("percentage")
    val percentage: Double
)

data class FullAttendanceResponse(
    @SerializedName("roll_no")
    val rollNo: String,

    @SerializedName("overall")
    val overall: AttendanceSummary,

    @SerializedName("latest_month")
    val latestMonth: MonthlyAttendance? = null,

    @SerializedName("monthly")
    val monthly: List<MonthlyAttendance>,

    @SerializedName("subjects")
    val subjects: List<SubjectAttendance>
)

// ==========================================
// ACADEMIC RESULTS MODELS
// ==========================================

data class AcademicSummary(
    @SerializedName("latest_semester")
    val latestSemester: String?,

    @SerializedName("latest_sgpa")
    val latestSgpa: Double?,

    @SerializedName("latest_cgpa")
    val latestCgpa: Double?
)

data class SubjectGrade(
    @SerializedName("id")
    val id: Int,

    @SerializedName("roll_no")
    val rollNo: String,

    @SerializedName("subject_code")
    val subjectCode: String,

    @SerializedName("subject_name")
    val subjectName: String,

    @SerializedName("grade")
    val grade: String,

    @SerializedName("credits")
    val credits: Double?,

    @SerializedName("semester_info")
    val semesterInfo: String?,

    @SerializedName("exam_info")
    val examInfo: String?
)

data class SemesterResult(
    @SerializedName("id")
    val id: Int,

    @SerializedName("roll_no")
    val rollNo: String,

    @SerializedName("semester")
    val semester: String?,

    @SerializedName("sgpa")
    val sgpa: String?,

    @SerializedName("cgpa")
    val cgpa: String?,

    @SerializedName("scr")
    val scr: String?,

    @SerializedName("tcr")
    val tcr: String?,

    @SerializedName("department")
    val department: String?,

    @SerializedName("academic_year")
    val academicYear: String?,

    @SerializedName("subjects")
    val subjects: List<SubjectGrade>
)

data class FullResultsResponse(
    @SerializedName("roll_no")
    val rollNo: String,

    @SerializedName("cgpa")
    val cgpa: Double?,

    @SerializedName("backlogs_count")
    val backlogsCount: Int,

    @SerializedName("total_semesters")
    val totalSemesters: Int,

    @SerializedName("semesters")
    val semesters: List<SemesterResult>,

    @SerializedName("all_subjects")
    val allSubjects: List<SubjectGrade>
)

// ==========================================
// SMARTSCORE, NPTEL & PLACEMENTS
// ==========================================

data class SmartScoreResponse(
    @SerializedName("roll_no")
    val rollNo: String?,

    @SerializedName("available")
    val available: Boolean,

    @SerializedName("rank")
    val rank: Int?,

    @SerializedName("global_rank")
    val globalRank: Int?,

    @SerializedName("overall_score")
    val overallScore: Double?,

    @SerializedName("name")
    val name: String?,

    @SerializedName("message")
    val message: String?
)

data class NptelCertificate(
    @SerializedName("id")
    val id: Int,

    @SerializedName("roll_no")
    val rollNo: String,

    @SerializedName("course_name")
    val courseName: String,

    @SerializedName("nptel_roll_no")
    val nptelRollNo: String?,

    @SerializedName("certificate_no")
    val certificateNo: String?,

    @SerializedName("score_percentage")
    val scorePercentage: Double,

    @SerializedName("assignments_score")
    val assignmentsScore: String?,

    @SerializedName("proctored_score")
    val proctoredScore: String?,

    @SerializedName("duration")
    val duration: String?,

    @SerializedName("course_weeks")
    val courseWeeks: String?,

    @SerializedName("grade_label")
    val gradeLabel: String?,

    @SerializedName("pdf_filename")
    val pdfFilename: String?,

    @SerializedName("verify_url")
    val verifyUrl: String?
)

data class NptelListResponse(
    @SerializedName("roll_no")
    val rollNo: String,

    @SerializedName("total_certificates")
    val totalCertificates: Int,

    @SerializedName("certificates")
    val certificates: List<NptelCertificate>
)

data class JobOffer(
    @SerializedName("id")
    val id: Int,

    @SerializedName("roll_no")
    val rollNo: String,

    @SerializedName("student_name")
    val studentName: String,

    @SerializedName("company_name")
    val companyName: String,

    @SerializedName("package_lpa")
    val packageLpa: Double?,

    @SerializedName("job_role")
    val jobRole: String?,

    @SerializedName("offer_date")
    val offerDate: String?,

    @SerializedName("academic_year")
    val academicYear: String?,

    @SerializedName("semester")
    val semester: String?
)

data class PlacementsResponse(
    @SerializedName("roll_no")
    val rollNo: String,

    @SerializedName("total_offers")
    val totalOffers: Int,

    @SerializedName("highest_package_lpa")
    val highestPackageLpa: Double?,

    @SerializedName("offers")
    val offers: List<JobOffer>
)

// ==========================================
// BIRTHDAYS & LEADERBOARD
// ==========================================

data class BirthdayStudent(
    @SerializedName("roll_no")
    val rollNo: String,

    @SerializedName("student_name")
    val studentName: String,

    @SerializedName("department")
    val department: String?,

    @SerializedName("date_of_birth")
    val dateOfBirth: String?,

    @SerializedName("birthday_formatted")
    val birthdayFormatted: String?,

    @SerializedName("photo_url")
    val photoUrl: String?
)

data class BirthdaysResponse(
    @SerializedName("date")
    val date: String,

    @SerializedName("formatted_date")
    val formattedDate: String,

    @SerializedName("count")
    val count: Int,

    @SerializedName("students")
    val students: List<BirthdayStudent>
)

data class LeaderboardEntry(
    @SerializedName("rank")
    val rank: Int,

    @SerializedName("roll_no")
    val rollNo: String,

    @SerializedName("student_name")
    val studentName: String,

    @SerializedName("department")
    val department: String?,

    @SerializedName("cgpa")
    val cgpa: Double,

    @SerializedName("latest_semester")
    val latestSemester: String?,

    @SerializedName("photo_url")
    val photoUrl: String?
)

data class LeaderboardResponse(
    @SerializedName("total")
    val total: Int,

    @SerializedName("department_filter")
    val departmentFilter: String,

    @SerializedName("leaderboard")
    val leaderboard: List<LeaderboardEntry>
)
