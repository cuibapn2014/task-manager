import 'vuetify/styles'
import '@mdi/font/css/materialdesignicons.css'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'

export default createVuetify({
  components,
  directives,
  theme: {
    defaultTheme: 'dark',
    themes: {
      dark: {
        dark: true,
        colors: {
          primary: '#6C63FF',
          secondary: '#FF6584',
          surface: '#1E1E2E',
          background: '#13131F',
          error: '#FF5370',
          success: '#A3E635',
          warning: '#FBBF24',
          info: '#38BDF8',
        },
      },
    },
  },
})
