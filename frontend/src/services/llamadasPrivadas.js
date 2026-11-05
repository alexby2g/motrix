import { Room, RoomEvent, Track } from 'livekit-client'
import { api } from 'src/boot/axios.js'

export async function iniciarLlamadaPrivada(viajeId, onEstado = () => {}) {
  if (!Number.isSafeInteger(Number(viajeId)) || Number(viajeId) <= 0) {
    throw new Error('El identificador del viaje no es valido')
  }

  const { data } = await api.post(`/llamadas/${viajeId}/token`)

  if (!data?.url || !data?.token) {
    throw new Error('El servidor no entrego acceso a la llamada')
  }

  const room = new Room()
  const audios = new Set()

  function retirarAudio(elemento) {
    elemento.pause()
    elemento.remove()
    audios.delete(elemento)
  }

  room.on(RoomEvent.TrackSubscribed, (track) => {
    if (track.kind !== Track.Kind.Audio) return

    const audio = track.attach()
    audio.autoplay = true
    document.body.appendChild(audio)
    audios.add(audio)
  })

  room.on(RoomEvent.TrackUnsubscribed, (track) => {
    for (const audio of track.detach()) retirarAudio(audio)
  })

  room.on(RoomEvent.AudioPlaybackStatusChanged, () => {
    if (!room.canPlaybackAudio) onEstado('audio_bloqueado')
  })

  room.on(RoomEvent.Disconnected, () => {
    for (const audio of [...audios]) retirarAudio(audio)
    onEstado('desconectada')
  })

  let etapa = 'conexion LiveKit'

  try {
    onEstado('conectando')
    await room.connect(data.url, data.token)

    etapa = 'activacion del microfono'
    await room.localParticipant.setMicrophoneEnabled(true)
    onEstado('conectada')

    // Android puede exigir un toque para reproducir audio remoto.
    // No desconectar una llamada estable solo por bloqueo de autoplay.
    room.startAudio().catch(() => onEstado('audio_bloqueado'))
  } catch (error) {
    const mensaje = error?.message || 'Error desconocido'
    await room.disconnect().catch(() => {})
    throw new Error(`Fallo en ${etapa}: ${mensaje}`)
  }

  return {
    room,
    activarAudio: () => room.startAudio(),
    silenciar: async (silencio) => {
      await room.localParticipant.setMicrophoneEnabled(!silencio)
    },
    colgar: async () => {
      await room.disconnect()
    }
  }
}
