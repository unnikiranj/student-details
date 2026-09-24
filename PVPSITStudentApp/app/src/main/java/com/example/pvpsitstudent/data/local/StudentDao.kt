package com.example.pvpsitstudent.data.local

import androidx.room.Dao
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.Query
import kotlinx.coroutines.flow.Flow

@Dao
interface StudentDao {
    @Query("SELECT * FROM student_profile WHERE rollNo = :rollNo")
    fun getProfile(rollNo: String): Flow<StudentProfileEntity?>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    fun insertProfile(profile: StudentProfileEntity)

    @Query("SELECT * FROM student_attendance WHERE rollNo = :rollNo")
    fun getAttendance(rollNo: String): Flow<AttendanceEntity?>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    fun insertAttendance(attendance: AttendanceEntity)

    @Query("SELECT * FROM student_results WHERE rollNo = :rollNo")
    fun getResults(rollNo: String): Flow<ResultsEntity?>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    fun insertResults(results: ResultsEntity)
}
