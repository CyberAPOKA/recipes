import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import i18n from './i18n'
import './style.css'
import App from './App.vue'

/* Import Font Awesome */
import { library } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faClock, faUsers, faWandMagicSparkles } from '@fortawesome/free-solid-svg-icons'

/* Add icons to the library */
library.add(faClock, faUsers, faWandMagicSparkles)

const app = createApp(App)
const pinia = createPinia()

app.component('font-awesome-icon', FontAwesomeIcon)

app.use(pinia)
app.use(router)
app.use(i18n)

app.mount('#app')
