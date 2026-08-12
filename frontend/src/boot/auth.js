function obtenerUsuario() {
  try {
    return JSON.parse(
      localStorage.getItem(
        'motrix_user'
      )
      || 'null'
    )
  } catch {
    return null
  }
}

function limpiarSesionInvalida() {
  localStorage.removeItem(
    'motrix_token'
  )

  localStorage.removeItem(
    'motrix_user'
  )

  localStorage.removeItem(
    'mototaxista_id'
  )

  localStorage.removeItem(
    'pasajero_id'
  )
}

function normalizarRol(role) {
  const valor = String(role || '')
    .trim()
    .toLowerCase()

  if (valor === 'admin') {
    return 'admin_general'
  }

  if (valor === 'cajero') {
    return 'admin_servicios'
  }

  return valor
}

function obtenerInicioPorRol(role) {
  const rol = normalizarRol(role)

  if (rol === 'admin_general') {
    return '/monitoreo'
  }

  if (rol === 'admin_servicios') {
    return '/solicitudes'
  }

  if (rol === 'secretario') {
    return '/mototaxistas'
  }

  if (rol === 'conductor') {
    return '/conductor'
  }

  if (rol === 'pasajero') {
    return '/pasajero'
  }

  return '/login'
}

export default ({ router }) => {
  router.beforeEach((to) => {
    const token =
      localStorage.getItem(
        'motrix_token'
      )

    const usuario =
      obtenerUsuario()

    const rol = normalizarRol(
      usuario?.role
    )

    /*
     * Cuando alguien abre MOTRIX por primera vez
     * sin sesión en la raíz "/", mostramos primero
     * la presentación pública.
     */
    if (
      to.path === '/'
      && (
        !token
        || !usuario
        || !rol
      )
    ) {
      return '/inicio'
    }

    const requiereAutenticacion =
      to.matched.some(
        (ruta) =>
          ruta.meta?.requiresAuth
          === true
      )

    const esRutaSoloInvitados =
      to.matched.some(
        (ruta) =>
          ruta.meta?.guestOnly
          === true
      )

    /*
     * Si un usuario que ya inició sesión intenta
     * volver al login, MOTRIX lo devuelve a su panel.
     */
    if (
      esRutaSoloInvitados
      && token
      && usuario
      && rol
    ) {
      return obtenerInicioPorRol(
        rol
      )
    }

    if (
      requiereAutenticacion
      && (
        !token
        || !usuario
        || !rol
      )
    ) {
      limpiarSesionInvalida()

      return {
        path: '/login',

        query: {
          redirect: to.fullPath
        }
      }
    }

    const rolesPermitidos =
      Array.isArray(
        to.meta?.roles
      )
        ? to.meta.roles.map(
            (item) =>
              String(item)
                .trim()
                .toLowerCase()
          )
        : []

    if (
      rolesPermitidos.length > 0
      && rol
      && !rolesPermitidos
        .includes(rol)
    ) {
      return obtenerInicioPorRol(
        rol
      )
    }

    return true
  })
}
