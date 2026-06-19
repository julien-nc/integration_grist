<template>
	<div id="grist_prefs" class="section">
		<h2>
			<GristIcon class="icon" />
			{{ t('integration_grist', 'Grist integration') }}
		</h2>
		<div id="grist-content">
			<NcNoteCard type="info">
				{{ t('integration_grist', 'To create an API key, go to the "Developer" section of your Grist Account settings.') }}
			</NcNoteCard>
			<NcTextField
				v-model="state.url"
				:label="t('integration_grist', 'Grist instance address')"
				placeholder="https://docs.getgrist.com/"
				:show-trailing-button="!!state.url"
				@trailing-button-click="state.url = ''; onInput()"
				@update:model-value="onInput">
				<template #icon>
					<EarthIcon :size="20" />
				</template>
			</NcTextField>
			<NcTextField
				v-model="state.token"
				type="password"
				:label="t('integration_grist', 'API key')"
				:placeholder="t('integration_grist', 'Grist API key')"
				:show-trailing-button="!!state.token"
				@trailing-button-click="state.token = ''; onInput()"
				@update:model-value="onInput">
				<template #icon>
					<KeyOutlineIcon :size="20" />
				</template>
			</NcTextField>
		</div>
	</div>
</template>

<script>
import EarthIcon from 'vue-material-design-icons/Earth.vue'
import KeyOutlineIcon from 'vue-material-design-icons/KeyOutline.vue'

import GristIcon from './icons/GristIcon.vue'

import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcTextField from '@nextcloud/vue/components/NcTextField'

import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { confirmPassword } from '@nextcloud/password-confirmation'

import { delay } from '../utils.js'

export default {
	name: 'PersonalSettings',

	components: {
		NcNoteCard,
		NcTextField,
		GristIcon,
		EarthIcon,
		KeyOutlineIcon,
	},

	props: [],

	data() {
		return {
			state: loadState('integration_grist', 'user-config'),
			initialToken: loadState('integration_grist', 'user-config').token,
			loading: false,
		}
	},

	computed: {
	},

	mounted() {
	},

	methods: {
		onInput() {
			this.loading = true
			delay(() => {
				this.saveOptions({
					url: this.state.url,
				})
				if (!'dummyToken'.includes(this.state.token)) {
					this.saveOptions({
						token: this.state.token,
					})
				}
			}, 2000)()
		},
		async saveOptions(values) {
			await confirmPassword()
			const req = {
				values,
			}
			const url = generateUrl('/apps/integration_grist/config')
			axios.put(url, req)
				.then((response) => {
					showSuccess(t('integration_grist', 'Grist options saved'))
				})
				.catch((error) => {
					showError(
						t('integration_grist', 'Failed to save Grist options')
						+ ': ' + error.response.request.responseText,
					)
				})
				.then(() => {
					this.loading = false
				})
		},
	},
}
</script>

<style scoped lang="scss">
#grist_prefs {
	h2 {
		display: flex;
		align-items: center;
		justify-content: start;
		gap: 8px;
	}
	#grist-content {
		margin-left: 40px;
		display: flex;
		flex-direction: column;
		gap: 4px;
		max-width: 800px;

		.line {
			display: flex;
			align-items: center;
			gap: 8px;
		}
	}
}
</style>
