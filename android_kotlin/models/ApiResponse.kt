package com.pvpsit.studentportal.data.models

import com.google.gson.annotations.SerializedName

/**
 * Generic REST API response wrapper matching PHP backend
 */
data class ApiResponse<T>(
    @SerializedName("status")
    val status: String,

    @SerializedName("code")
    val code: Int,

    @SerializedName("message")
    val message: String,

    @SerializedName("data")
    val data: T?,

    @SerializedName("timestamp")
    val timestamp: Long
) {
    val isSuccessful: Boolean
        get() = status.equals("success", ignoreCase = true) && code in 200..299
}
