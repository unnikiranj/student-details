package com.pvpsit.studentportal.data.network

import com.google.gson.GsonBuilder
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.util.concurrent.TimeUnit

object ApiClient {

    // Current Laptop Wi-Fi IP (Works seamlessly over Wi-Fi and USB)
    const val DEFAULT_WIFI_URL = "http://192.168.1.37/studentdetails/"
    const val DEFAULT_USB_URL = "http://localhost/studentdetails/"

    var currentBaseUrl: String = DEFAULT_WIFI_URL
        private set

    private var apiService: StudentApiService? = null
    private var tokenHolder: String? = null

    fun setAuthToken(token: String?) {
        tokenHolder = token
    }

    fun getAuthToken(): String? = tokenHolder

    fun setBaseUrl(newUrl: String) {
        val formatted = if (newUrl.endsWith("/")) newUrl else "$newUrl/"
        if (currentBaseUrl != formatted) {
            currentBaseUrl = formatted
            apiService = null
        }
    }

    fun getService(): StudentApiService {
        if (apiService == null) {
            val logging = HttpLoggingInterceptor().apply {
                level = HttpLoggingInterceptor.Level.BODY
            }

            val okHttpClient = OkHttpClient.Builder()
                .connectTimeout(15, TimeUnit.SECONDS)
                .readTimeout(15, TimeUnit.SECONDS)
                .writeTimeout(20, TimeUnit.SECONDS)
                .addInterceptor(AuthInterceptor { tokenHolder })
                .addInterceptor(logging)
                .build()

            val gson = GsonBuilder()
                .setLenient()
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
}
