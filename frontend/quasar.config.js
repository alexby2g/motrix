// Configuración principal de MOTRIX
// https://quasar.dev/quasar-cli-vite/quasar-config-file

import { defineConfig } from '#q-app/wrappers'

export default defineConfig((ctx) => {
  return {
    boot: [
      'axios',
      'auth',
      ...(ctx.mode.capacitor ? ['native-capacitor'] : [])
    ],

    css: [
      'app.scss',
      'menu-fix.scss',
      'monitoreo-icon-fix.scss',
      'mobile-dialog-scroll-fix.scss'
    ],

    extras: [
      'roboto-font',
      'material-icons'
    ],

    build: {
      target: {
        browser: 'baseline-widely-available',
        node: 'node22'
      },

      vueRouterMode: 'hash',

      vitePlugins: [
        ['vite-plugin-checker', {
          eslint: {
            lintCommand: 'eslint -c ./eslint.config.js "./src*/**/*.{js,mjs,cjs,vue}"',
            useFlatConfig: true
          }
        }, { server: false }]
      ]
    },

    devServer: {
      open: true
    },

    framework: {
      config: {
        capacitor: {
          backButton: true,
          backButtonExit: [
            '/inicio',
            '/login',
            '/monitoreo',
            '/conductor',
            '/pasajero'
          ]
        }
      },

      plugins: [
        'Dialog',
        'Notify'
      ]
    },

    animations: [],

    ssr: {
      prodPort: 3000,
      middlewares: [
        'render'
      ],
      pwa: false
    },

    pwa: {
      workboxMode: 'GenerateSW'
    },

    capacitor: {
      hideSplashscreen: true
    },

    electron: {
      preloadScripts: [
        'electron-preload'
      ],
      inspectPort: 5858,
      bundler: 'packager',
      packager: {},
      builder: {
        appId: 'mototaxi-front'
      }
    },

    bex: {
      extraScripts: []
    }
  }
})
