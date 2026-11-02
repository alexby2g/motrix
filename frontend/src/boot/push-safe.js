import { inicializarPushMotrix } from 'src/services/pushNotifications.js'

// MOTRIX no depende de FCM para poder arrancar o iniciar sesiÃ³n.
// Android solicitarÃ¡ permiso despuÃ©s de una interacciÃ³n en el panel conductor.
// No solicita permisos ni registra push en navegador web.
export default ({ router }) => {
  if (typeof window === 'undefined') return
  const esNativo = Boolean(window.Capacitor?.isNativePlatform?.())
  if (!esNativo) return

  let activado = false
  let iniciando = false

  const intentar = () => {
    if (activado || iniciando) return
    if (!router.currentRoute.value.path.startsWith('/conductor')) return
    if (!localStorage.getItem('motrix_token')) return

    iniciando = true
    // No bloquea la interfaz, y todo fallo JS queda aislado.
    Promise.resolve()
      .then(() => inicializarPushMotrix(router))
      .then((resultado) => { activado = resultado === true })
      .catch((error) => {
        console.warn('MOTRIX continÃºa sin Firebase:', error)
      })
      .finally(() => { iniciando = false })
  }

  // Se activa al tocar el panel del mototaxista, no en el arranque.
  window.addEventListener('pointerdown', intentar, { passive: true })
}