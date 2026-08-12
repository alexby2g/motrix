const paginaEscanerQr =
  import.meta.env.QUASAR_CAPACITOR_MODE
    ? () => import('pages/pasajeros/EscanearQrNativePage.vue')
    : () => import('pages/pasajeros/EscanearQrPage.vue')

const routes = [
  {
    path: '/inicio',
    component: () => import('pages/LandingPage.vue')
  },
  {
    path: '/login',
    component: () => import('pages/LoginPage.vue'),
    meta: { guestOnly: true }
  },
  {
    path: '/verificar/:codigo',
    component: () => import('pages/publico/VerificarPage.vue')
  },
  {
    path: '/',
    component: () => import('layouts/MainLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        redirect: '/monitoreo'
      },
      {
        path: 'monitoreo',
        component: () => import('pages/HomePage.vue'),
        meta: { roles: ['admin_general'] }
      },
      {
        path: 'personas',
        component: () => import('pages/personas/PersonasPage.vue'),
        meta: {
          roles: ['admin_general', 'secretario', 'admin_servicios']
        }
      },
      {
        path: 'federaciones',
        component: () => import('pages/administracion/FederacionesPage.vue'),
        meta: { roles: ['admin_general'] }
      },
      {
        path: 'usuarios',
        component: () => import('pages/administracion/UsuariosPage.vue'),
        meta: { roles: ['admin_general'] }
      },
      {
        path: 'pasajeros',
        component: () => import('pages/pasajeros/PasajerosPage.vue'),
        meta: { roles: ['admin_general', 'admin_servicios'] }
      },
      {
        path: 'sindicatos',
        component: () => import('pages/administracion/SindicatosPage.vue'),
        meta: { roles: ['admin_general', 'secretario'] }
      },
      {
        path: 'mototaxistas',
        component: () => import('pages/mototaxistas/MototaxistasPage.vue'),
        meta: { roles: ['admin_general', 'secretario'] }
      },
      {
        path: 'motocicletas',
        component: () => import('pages/mototaxistas/MotocicletasPage.vue'),
        meta: { roles: ['admin_general', 'secretario'] }
      },
      {
        path: 'solicitudes',
        component: () => import('pages/servicios/SolicitudesPage.vue'),
        meta: { roles: ['admin_general', 'admin_servicios'] }
      },
      {
        path: 'servicios',
        component: () => import('pages/servicios/ServiciosPage.vue'),
        meta: { roles: ['admin_general', 'admin_servicios'] }
      },
      {
        path: 'pagos',
        component: () => import('pages/servicios/PagosPage.vue'),
        meta: { roles: ['admin_general', 'admin_servicios'] }
      },
      {
        path: 'pagos-sindicales',
        component: () => import('pages/administracion/PagosSindicalesPage.vue'),
        meta: { roles: ['admin_general', 'secretario'] }
      },
      {
        path: 'reportes',
        component: () => import('pages/reportes/ReportesPage.vue'),
        meta: { roles: ['admin_general', 'admin_servicios'] }
      },
      {
        path: 'incidencias',
        component: () => import('pages/administracion/IncidenciasPage.vue'),
        meta: { roles: ['admin_general', 'secretario'] }
      },
      {
        path: 'soporte/pasajero/:id',
        component: () => import('pages/soporte/SoportePasajeroPage.vue'),
        meta: { roles: ['admin_general'] }
      },
      {
        path: 'soporte/conductor/:id',
        component: () => import('pages/soporte/SoporteConductorPage.vue'),
        meta: { roles: ['admin_general'] }
      },
      {
        path: 'conductor',
        component: () => import('pages/servicios/ConductorPage.vue'),
        meta: { roles: ['conductor'] }
      },
      {
        path: 'conductor/ganancias',
        component: () => import('pages/servicios/GananciasConductorPage.vue'),
        meta: { roles: ['conductor'] }
      },
      {
        path: 'conductor/perfil',
        component: () => import('pages/servicios/PerfilConductorPage.vue'),
        meta: { roles: ['conductor'] }
      },
      {
        path: 'pasajero',
        component: () => import('pages/pasajeros/PanelPasajeroPage.vue'),
        meta: { roles: ['pasajero'] }
      },
      {
        path: 'pasajero/solicitar',
        component: () => import('pages/pasajeros/SolicitarViajePage.vue'),
        meta: { roles: ['pasajero'] }
      },
      {
        path: 'pasajero/escanear',
        component: paginaEscanerQr,
        meta: { roles: ['pasajero'] }
      },
      {
        path: 'pasajero/historial',
        component: () => import('pages/pasajeros/HistorialPasajeroPage.vue'),
        meta: { roles: ['pasajero'] }
      },
      {
        path: 'pasajero/perfil',
        component: () => import('pages/pasajeros/PerfilPasajeroPage.vue'),
        meta: { roles: ['pasajero'] }
      }
    ]
  },
  {
    path: '/:catchAll(.*)*',
    component: () => import('pages/ErrorNotFound.vue')
  }
]

export default routes
