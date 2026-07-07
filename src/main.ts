import { createApp } from 'vue'
import { translate as t, loadTranslations } from '@nextcloud/l10n'
import App from './App.vue'

loadTranslations('iptv').then(() => {
	const app = createApp(App)

	app.mixin({
		methods: {
			$t(text: string, vars?: Record<string, string | number>): string {
				let result = t('iptv', text)
				if (vars) {
					for (const [key, value] of Object.entries(vars)) {
						result = result.replace(`{${key}}`, String(value ?? ''))
					}
				}
				return result
			},
		},
	})

	app.mount('#iptv')
})
