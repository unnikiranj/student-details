package com.example.pvpsitstudent.data.models

import com.google.gson.annotations.SerializedName

data class MidmarksResponse(
    @SerializedName("roll_no") val rollNo: String,
    @SerializedName("total_subjects") val totalSubjects: Int,
    @SerializedName("evaluated_count") val evaluatedCount: Int,
    @SerializedName("average_internal_marks") val averageInternalMarks: Double,
    @SerializedName("subjects") val subjects: List<SubjectMidmark> = emptyList()
)

data class SubjectMidmark(
    @SerializedName("id") val id: Int,
    @SerializedName("subject_code") val subjectCode: String,
    @SerializedName("subject_name") val subjectName: String,
    @SerializedName("mid1") val mid1: MidExamBreakdown?,
    @SerializedName("mid2") val mid2: MidExamBreakdown?,
    @SerializedName("total_marks") val totalMarks: Double?,
    @SerializedName("max_marks") val maxMarks: Double?,
    @SerializedName("raw_breakdown") val rawBreakdown: String?,
    @SerializedName("semester_info") val semesterInfo: String?,
    @SerializedName("academic_year") val academicYear: String?
)

data class MidExamBreakdown(
    @SerializedName("descriptive") val descriptive: Double?,
    @SerializedName("objective") val objective: Double?,
    @SerializedName("assignment") val assignment: Double?,
    @SerializedName("total") val total: Double?
)
