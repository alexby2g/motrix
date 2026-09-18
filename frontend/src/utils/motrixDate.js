function texto(valor) {
  return String(valor ?? '').trim()
}

function partesFechaIso(valor) {
  const limpio = texto(valor)
  const match = limpio.match(/^(\d{4})-(\d{2})-(\d{2})(?:$|[T\s])/)
  if (!match) return null

  return {
    year: match[1],
    month: match[2],
    day: match[3]
  }
}


function partesFechaBolivia(valor = new Date()) {
  const fecha = valor instanceof Date ? valor : new Date(valor)
  if (Number.isNaN(fecha.getTime())) return null

  const partes = new Intl.DateTimeFormat('en-CA', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    timeZone: 'America/La_Paz'
  }).formatToParts(fecha)

  const mapa = Object.fromEntries(
    partes
      .filter(parte => parte.type !== 'literal')
      .map(parte => [parte.type, parte.value])
  )

  if (!mapa.year || !mapa.month || !mapa.day) return null

  return {
    year: mapa.year,
    month: mapa.month,
    day: mapa.day
  }
}

export function fechaISOHoyBolivia() {
  const partes = partesFechaBolivia(new Date())
  if (!partes) return ''
  return `${partes.year}-${partes.month}-${partes.day}`
}

export function periodoActualBolivia() {
  const partes = partesFechaBolivia(new Date())
  if (!partes) return ''
  return `${partes.year}-${partes.month}`
}

function fechaHoraNormalizada(valor) {
  const limpio = texto(valor)

  // Laravel/MySQL suele entregar timestamps sin zona horaria.
  // MOTRIX opera en Bolivia, por lo que esos valores se interpretan
  // explícitamente como America/La_Paz (UTC-04) y no con la zona
  // configurada en el teléfono o navegador del usuario.
  if (/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(?::\d{2}(?:\.\d+)?)?$/.test(limpio)) {
    return `${limpio.replace(' ', 'T')}-04:00`
  }

  return limpio
}

export function fechaDDMMYYYY(valor, fallback = '—') {
  if (!valor) return fallback

  const iso = partesFechaIso(valor)
  if (iso) {
    return `${iso.day}/${iso.month}/${iso.year}`
  }

  const fecha = new Date(valor)
  if (Number.isNaN(fecha.getTime())) return texto(valor) || fallback

  return new Intl.DateTimeFormat('es-BO', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    timeZone: 'America/La_Paz'
  }).format(fecha)
}

export function fechaHoraDDMMYYYY(valor, fallback = '—') {
  if (!valor) return fallback

  const limpio = texto(valor)
  if (/^\d{4}-\d{2}-\d{2}$/.test(limpio)) {
    return fechaDDMMYYYY(limpio, fallback)
  }

  const fecha = new Date(fechaHoraNormalizada(limpio))
  if (Number.isNaN(fecha.getTime())) return limpio || fallback

  return new Intl.DateTimeFormat('es-BO', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
    timeZone: 'America/La_Paz'
  }).format(fecha)
}

export function periodoMMYYYY(valor, fallback = '—') {
  const limpio = texto(valor)
  const match = limpio.match(/^(\d{4})-(\d{2})$/)
  if (!match) return limpio || fallback
  return `${match[2]}/${match[1]}`
}

export function fechaSemanticaDDMMYYYY(valor, fallback = '—') {
  return fechaDDMMYYYY(valor, fallback)
}

// -----------------------------------------------------------------------------
// MOTRIX V5.7 - FORMATO VISUAL CENTRAL DE FECHAS
// -----------------------------------------------------------------------------
// Fecha simple: DD/MM/YYYY sin convertir zona horaria.
// Timestamp con Z/offset: convertido a America/La_Paz.
// Timestamp sin zona: se conserva como hora local declarada para evitar
// desplazamientos inesperados en datos históricos.
// -----------------------------------------------------------------------------

const MOTRIX_TIME_ZONE_V57 = 'America/La_Paz'

function motrixDatePartsV57(date) {
  const parts = new Intl.DateTimeFormat('en-GB', {
    timeZone: MOTRIX_TIME_ZONE_V57,
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  }).formatToParts(date)

  return Object.fromEntries(
    parts
      .filter(part => part.type !== 'literal')
      .map(part => [part.type, part.value])
  )
}

function motrixDateTimePartsV57(date) {
  const parts = new Intl.DateTimeFormat('en-GB', {
    timeZone: MOTRIX_TIME_ZONE_V57,
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hourCycle: 'h23'
  }).formatToParts(date)

  return Object.fromEntries(
    parts
      .filter(part => part.type !== 'literal')
      .map(part => [part.type, part.value])
  )
}

export function motrixDateV57(value, fallback = '—') {
  if (value === null || value === undefined || value === '') {
    return fallback
  }

  const text = String(value).trim()

  // Una fecha de calendario NO debe pasar por UTC.
  const dateOnly = text.match(/^(\d{4})-(\d{2})-(\d{2})$/)

  if (dateOnly) {
    const [, year, month, day] = dateOnly
    return `${day}/${month}/${year}`
  }

  // Fecha y hora sin zona: preservar exactamente los componentes recibidos.
  const naiveDateTime = text.match(
    /^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::\d{2}(?:\.\d+)?)?$/
  )

  if (naiveDateTime) {
    const [, year, month, day] = naiveDateTime
    return `${day}/${month}/${year}`
  }

  const date = value instanceof Date ? value : new Date(value)

  if (Number.isNaN(date.getTime())) {
    return text || fallback
  }

  const parts = motrixDatePartsV57(date)

  return `${parts.day}/${parts.month}/${parts.year}`
}

export function motrixDateTimeV57(value, fallback = '—') {
  if (value === null || value === undefined || value === '') {
    return fallback
  }

  const text = String(value).trim()

  const dateOnly = text.match(/^(\d{4})-(\d{2})-(\d{2})$/)

  if (dateOnly) {
    const [, year, month, day] = dateOnly
    return `${day}/${month}/${year}`
  }

  // Laravel/SQL puede entregar un datetime sin Z. No convertirlo de zona.
  const naiveDateTime = text.match(
    /^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::\d{2}(?:\.\d+)?)?$/
  )

  if (naiveDateTime) {
    const [, year, month, day, hour, minute] = naiveDateTime
    return `${day}/${month}/${year} ${hour}:${minute}`
  }

  const date = value instanceof Date ? value : new Date(value)

  if (Number.isNaN(date.getTime())) {
    return text || fallback
  }

  const parts = motrixDateTimePartsV57(date)

  return (
    `${parts.day}/${parts.month}/${parts.year} `
    + `${parts.hour}:${parts.minute}`
  )
}

export function motrixDateInputV57(value = new Date()) {
  if (typeof value === 'string') {
    const match = value.trim().match(/^(\d{4})-(\d{2})-(\d{2})/)
    if (match) {
      return `${match[1]}-${match[2]}-${match[3]}`
    }
  }

  const date = value instanceof Date ? value : new Date(value)

  if (Number.isNaN(date.getTime())) {
    return ''
  }

  const parts = motrixDatePartsV57(date)
  return `${parts.year}-${parts.month}-${parts.day}`
}

