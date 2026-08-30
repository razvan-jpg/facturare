package ro.dateconta.facturare.ui.components

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import ro.dateconta.facturare.core.sync.SyncStatus
import ro.dateconta.facturare.ui.theme.AppTheme

@Composable
fun SyncStatusBadge(
    status: SyncStatus,
    pending: Int,
    modifier: Modifier = Modifier,
) {
    val label = if (pending > 0) "$pending în așteptare" else status.label
    val color = when {
        pending > 0 -> AppTheme.Warm
        status is SyncStatus.Ok || status is SyncStatus.Idle -> Color(0xFF22C55E)
        status is SyncStatus.Syncing -> AppTheme.Warm
        status is SyncStatus.Offline -> Color(0xFFF97316)
        status is SyncStatus.Error -> Color(0xFFEF4444)
        else -> Color.Gray
    }

    Row(
        modifier = modifier
            .clip(RoundedCornerShape(50))
            .background(AppTheme.Mist)
            .padding(horizontal = 10.dp, vertical = 5.dp),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(6.dp),
    ) {
        Box(
            modifier = Modifier
                .clip(CircleShape)
                .background(color)
                .padding(4.dp),
        )
        Text(
            text = label,
            fontSize = 12.sp,
            color = AppTheme.Deep.copy(alpha = 0.85f),
        )
    }
}
