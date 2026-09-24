package com.example.pvpsitstudent.data.network

import com.google.gson.TypeAdapter
import com.google.gson.stream.JsonReader
import com.google.gson.stream.JsonToken
import com.google.gson.stream.JsonWriter

class DoubleTypeAdapter : TypeAdapter<Double?>() {
    override fun write(out: JsonWriter, value: Double?) {
        if (value == null) {
            out.nullValue()
        } else {
            out.value(value)
        }
    }

    override fun read(reader: JsonReader): Double? {
        if (reader.peek() == JsonToken.NULL) {
            reader.nextNull()
            return null
        }
        return try {
            val str = reader.nextString()
            if (str.isBlank() || str.equals("null", ignoreCase = true) || str.equals("N/A", ignoreCase = true)) {
                null
            } else {
                str.toDoubleOrNull()
            }
        } catch (e: Exception) {
            null
        }
    }
}
