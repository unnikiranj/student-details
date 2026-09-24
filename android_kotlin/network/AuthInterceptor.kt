package com.pvpsit.studentportal.data.network

import okhttp3.Interceptor
import okhttp3.Response

/**
 * OkHttp Interceptor that dynamically adds the Bearer Token to outgoing requests
 */
class AuthInterceptor(
    private val tokenProvider: () -> String?
) : Interceptor {

    override fun intercept(chain: Interceptor.Chain): Response {
        val originalRequest = chain.request()
        val token = tokenProvider()

        val newRequestBuilder = originalRequest.newBuilder()
            .header("Accept", "application/json")
            .header("X-Requested-With", "XMLHttpRequest")

        if (!token.isNullOrBlank()) {
            newRequestBuilder.header("Authorization", "Bearer $token")
        }

        return chain.proceed(newRequestBuilder.build())
    }
}
