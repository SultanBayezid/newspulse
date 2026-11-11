/**
 * Formatting helpers shared across the app.
 */

const dateFormatter = new Intl.DateTimeFormat('en-GB', {
  day: 'numeric',
  month: 'short',
  year: 'numeric',
})

const longDateFormatter = new Intl.DateTimeFormat('en-GB', {
  day: 'numeric',
  month: 'long',
  year: 'numeric',
})

const timeFormatter = new Intl.DateTimeFormat('en-GB', {
  hour: '2-digit',
  minute: '2-digit',
})

export function formatDate(value) {
  if (!value) {
    return ''
  }

  return dateFormatter.format(new Date(value))
}

export function formatLongDate(value) {
  if (!value) {
    return ''
  }

  return longDateFormatter.format(new Date(value))
}

export function formatDateTime(value) {
  if (!value) {
    return ''
  }

  const date = new Date(value)

  return `${dateFormatter.format(date)}, ${timeFormatter.format(date)}`
}

export function formatRelative(value) {
  if (!value) {
    return ''
  }

  const seconds = Math.round((Date.now() - new Date(value).getTime()) / 1000)
  const minutes = Math.round(seconds / 60)
  const hours = Math.round(minutes / 60)
  const days = Math.round(hours / 24)

  if (Math.abs(seconds) < 60) {
    return 'just now'
  }

  if (Math.abs(minutes) < 60) {
    return `${minutes}m ago`
  }

  if (Math.abs(hours) < 24) {
    return `${hours}h ago`
  }

  if (Math.abs(days) < 7) {
    return `${days}d ago`
  }

  return formatDate(value)
}

export function formatNumber(value) {
  if (value === null || value === undefined) {
    return '0'
  }

  return new Intl.NumberFormat('en-US').format(value)
}

export function compactNumber(value) {
  if (value === null || value === undefined) {
    return '0'
  }

  return new Intl.NumberFormat('en-US', {
    notation: 'compact',
    maximumFractionDigits: 1,
  }).format(value)
}
