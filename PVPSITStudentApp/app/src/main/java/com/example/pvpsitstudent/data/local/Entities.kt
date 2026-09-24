package com.example.pvpsitstudent.data.local

import androidx.room.Entity
import androidx.room.PrimaryKey
import androidx.room.TypeConverter
import com.example.pvpsitstudent.data.models.*
import com.google.gson.Gson
import com.google.gson.reflect.TypeToken

@Entity(tableName = "student_profile")
data class StudentProfileEntity(
    @PrimaryKey
    val rollNo: String,
    val id: Int?,
    val studentName: String,
    val email: String?,
    val phone: String?,
    val dateOfBirth: String?,
    val formattedDob: String?,
    val privacyDob: String?,
    val fatherName: String?,
    val department: String?,
    val photoUrl: String?,
    val attendanceSummaryJson: String?,
    val academicSummaryJson: String?
) {
    fun toDomain(gson: Gson) = StudentProfile(
        id = id,
        rollNo = rollNo,
        studentName = studentName,
        email = email,
        phone = phone,
        dateOfBirth = dateOfBirth,
        formattedDob = formattedDob,
        privacyDob = privacyDob,
        fatherName = fatherName,
        department = department,
        photoUrl = photoUrl,
        attendanceSummary = attendanceSummaryJson?.let { gson.fromJson(it, AttendanceSummary::class.java) },
        academicSummary = academicSummaryJson?.let { gson.fromJson(it, AcademicSummary::class.java) }
    )
    
    companion object {
        fun fromDomain(profile: StudentProfile, gson: Gson) = StudentProfileEntity(
            id = profile.id,
            rollNo = profile.rollNo,
            studentName = profile.studentName,
            email = profile.email,
            phone = profile.phone,
            dateOfBirth = profile.dateOfBirth,
            formattedDob = profile.formattedDob,
            privacyDob = profile.privacyDob,
            fatherName = profile.fatherName,
            department = profile.department,
            photoUrl = profile.photoUrl,
            attendanceSummaryJson = profile.attendanceSummary?.let { gson.toJson(it) },
            academicSummaryJson = profile.academicSummary?.let { gson.toJson(it) }
        )
    }
}

@Entity(tableName = "student_attendance")
data class AttendanceEntity(
    @PrimaryKey
    val rollNo: String,
    val overallJson: String,
    val latestMonthJson: String?,
    val semesterSummariesJson: String?,
    val monthlyJson: String,
    val subjectsJson: String
) {
    fun toDomain(gson: Gson) = FullAttendanceResponse(
        rollNo = rollNo,
        overall = gson.fromJson(overallJson, AttendanceSummary::class.java),
        latestMonth = latestMonthJson?.let { gson.fromJson(it, MonthlyAttendance::class.java) },
        semesterSummaries = semesterSummariesJson?.let { gson.fromJson(it, object : TypeToken<List<SemesterAttendanceSummary>>(){}.type) },
        monthly = gson.fromJson(monthlyJson, object : TypeToken<List<MonthlyAttendance>>(){}.type),
        subjects = gson.fromJson(subjectsJson, object : TypeToken<List<SubjectAttendance>>(){}.type)
    )
    
    companion object {
        fun fromDomain(rollNo: String, domain: FullAttendanceResponse, gson: Gson) = AttendanceEntity(
            rollNo = rollNo,
            overallJson = gson.toJson(domain.overall),
            latestMonthJson = domain.latestMonth?.let { gson.toJson(it) },
            semesterSummariesJson = domain.semesterSummaries?.let { gson.toJson(it) },
            monthlyJson = gson.toJson(domain.monthly),
            subjectsJson = gson.toJson(domain.subjects)
        )
    }
}

@Entity(tableName = "student_results")
data class ResultsEntity(
    @PrimaryKey
    val rollNo: String,
    val cgpa: Double?,
    val backlogsCount: Int,
    val totalSemesters: Int,
    val semestersJson: String,
    val allSubjectsJson: String
) {
    fun toDomain(gson: Gson) = FullResultsResponse(
        rollNo = rollNo,
        cgpa = cgpa,
        backlogsCount = backlogsCount,
        totalSemesters = totalSemesters,
        semesters = gson.fromJson(semestersJson, object : TypeToken<List<SemesterResult>>(){}.type),
        allSubjects = gson.fromJson(allSubjectsJson, object : TypeToken<List<SubjectGrade>>(){}.type)
    )
    
    companion object {
        fun fromDomain(rollNo: String, domain: FullResultsResponse, gson: Gson) = ResultsEntity(
            rollNo = rollNo,
            cgpa = domain.cgpa,
            backlogsCount = domain.backlogsCount,
            totalSemesters = domain.totalSemesters,
            semestersJson = gson.toJson(domain.semesters),
            allSubjectsJson = gson.toJson(domain.allSubjects)
        )
    }
}

class Converters {
    private val gson = Gson()
    
    @TypeConverter
    fun fromStringList(value: List<String>?): String? = gson.toJson(value)
    
    @TypeConverter
    fun toStringList(value: String?): List<String>? {
        if (value == null) return null
        return gson.fromJson(value, object : TypeToken<List<String>>() {}.type)
    }
}
