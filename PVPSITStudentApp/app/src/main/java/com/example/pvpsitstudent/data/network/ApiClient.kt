package com.example.pvpsitstudent.data.network

import com.google.gson.GsonBuilder
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import okhttp3.OkHttpClient
import okhttp3.Request
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.util.concurrent.TimeUnit

object ApiClient {

    const val DEFAULT_USB_URL = "http://localhost/studentdetails/"
    const val DEFAULT_LOOPBACK_URL = "http://127.0.0.1/studentdetails/"
    const val DEFAULT_WIFI_URL = "http://10.1.48.114/studentdetails/"
    const val DEFAULT_EMULATOR_URL = "http://10.0.2.2/studentdetails/"

    val CANDIDATE_URLS = listOf(
        DEFAULT_USB_URL,
        DEFAULT_WIFI_URL,
        DEFAULT_LOOPBACK_URL,
        DEFAULT_EMULATOR_URL
    )

    var currentBaseUrl: String = DEFAULT_USB_URL
        private set

    private var apiService: StudentApiService? = null
    private var tokenHolder: String? = null

    fun setAuthToken(token: String?) {
        tokenHolder = token
    }

    fun getAuthToken(): String? = tokenHolder

    fun setBaseUrl(newUrl: String) {
        val trimmed = newUrl.trim()
        val formatted = if (trimmed.endsWith("/")) trimmed else "$trimmed/"
        if (currentBaseUrl != formatted) {
            currentBaseUrl = formatted
            apiService = null
        }
    }

    fun getService(): StudentApiService {
        if (apiService == null) {
            val logging = HttpLoggingInterceptor().apply {
                level = HttpLoggingInterceptor.Level.HEADERS
            }

            val okHttpClient = OkHttpClient.Builder()
                .connectTimeout(6, TimeUnit.SECONDS)
                .readTimeout(8, TimeUnit.SECONDS)
                .writeTimeout(10, TimeUnit.SECONDS)
                .retryOnConnectionFailure(true)
                .addInterceptor(AuthInterceptor { tokenHolder })
                .addInterceptor(logging)
                .build()

            val doubleAdapter = DoubleTypeAdapter()
            val gson = GsonBuilder()
                .setLenient()
                .registerTypeAdapter(Double::class.java, doubleAdapter)
                .registerTypeAdapter(Double::class.javaObjectType, doubleAdapter)
                .create()

            val retrofit = Retrofit.Builder()
                .baseUrl(currentBaseUrl)
                .client(okHttpClient)
                .addConverterFactory(GsonConverterFactory.create(gson))
                .build()

            apiService = retrofit.create(StudentApiService::class.java)
        }
        return apiService!!
    }

    suspend fun testConnection(url: String): Boolean = withContext(Dispatchers.IO) {
        try {
            val formatted = if (url.trim().endsWith("/")) url.trim() else "${url.trim()}/"
            val testUrl = "${formatted}api/v1/student/profile.php?roll=24501A1262"
            val client = OkHttpClient.Builder()
                .connectTimeout(3, TimeUnit.SECONDS)
                .readTimeout(3, TimeUnit.SECONDS)
                .build()
            val request = Request.Builder().url(testUrl).build()
            val response = client.newCall(request).execute()
            response.isSuccessful
        } catch (e: Exception) {
            false
        }
    }

    suspend fun autoDetectWorkingUrl(): String? = withContext(Dispatchers.IO) {
        for (candidate in CANDIDATE_URLS) {
            if (testConnection(candidate)) {
                setBaseUrl(candidate)
                return@withContext candidate
            }
        }
        null
    }
}
