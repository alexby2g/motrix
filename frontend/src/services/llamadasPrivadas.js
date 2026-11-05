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

  room.on(RoomEvent.Disconnected, () => {
    for (const audio of [...audios]) retirarAudio(audio)
    onEstado('desconectada')
  })

  try {
    onEstado('conectando')
    await room.connect(data.url, data.token)
    await room.startAudio()
    await room.localParticipant.setMicrophoneEnabled(true)
    onEstado('conectada')
  } catch (error) {
    await room.disconnect()
    throw error
  }

  return {
    room,
    silenciar: async (silencio) => {
      await room.localParticipant.setMicrophoneEnabled(!silencio)
    },
    colgar: async () => {
      await room.disconnect()
    }
  }
}
