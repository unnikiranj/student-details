package com.example.pvpsitstudent.theme

import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.darkColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.ui.graphics.Color

private val DarkObsidianColorScheme = darkColorScheme(
    background = ObsidianBg,
    surface = SlateSurface,
    surfaceVariant = SlateCard,
    outlineVariant = SlateBorder,
    outline = TextMuted,
    primary = ElectricIndigo,
    primaryContainer = DeepIndigo,
    secondary = NeonCyan,
    secondaryContainer = DeepIndigo,
    tertiary = EmeraldSuccess,
    onSurface = TextPrimary,
    onSurfaceVariant = TextSecondary,
    onPrimary = Color.White,
    onPrimaryContainer = IndigoLight,
    onSecondary = Color.Black,
    onError = Color.White,
    error = CoralRed
)

@Composable
fun PVPSITStudentTheme(
    content: @Composable () -> Unit,
) {
    MaterialTheme(
        colorScheme = DarkObsidianColorScheme,
        typography = Typography,
        content = content
    )
}
