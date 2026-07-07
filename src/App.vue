<template>
	<NcContent app-name="iptv">
		<NcAppNavigation>
			<template #list>
			<NcAppNavigationItem
				:name="$t('All channels')"
				icon="icon-play"
				:open="true"
				@click="selectedFolder = null">
				<template #actions>
					<NcActionButton @click="exportM3U8()">
						<template #icon><Download :size="20" /></template>
						{{ $t('Export M3U8') }}
					</NcActionButton>
				</template>
			</NcAppNavigationItem>
				<NcAppNavigationItem
					v-for="folder in folders"
					:key="folder.id"
					:name="folder.name"
					icon="icon-folder"
					:active="selectedFolder === folder.id"
					@click="selectedFolder = folder.id">
					<template #actions>
						<NcActionButton @click="exportM3U8(folder)">
							<template #icon><Download :size="20" /></template>
							{{ $t('Export M3U8') }}
						</NcActionButton>
						<NcActionButton @click="editFolder(folder)">
							<template #icon><Edit :size="20" /></template>
							{{ $t('Edit') }}
						</NcActionButton>
						<NcActionButton @click="confirmDeleteFolder(folder)">
							<template #icon><Delete :size="20" /></template>
							{{ $t('Delete') }}
						</NcActionButton>
					</template>
				</NcAppNavigationItem>
			</template>
			<template #footer>
				<NcButton wide @click="openAddFolder">
					<template #icon><Plus :size="20" /></template>
					{{ $t('Add folder') }}
				</NcButton>
				<NcButton wide @click="openAddChannel">
					<template #icon><Plus :size="20" /></template>
					{{ $t('Add channel') }}
				</NcButton>
				<NcButton wide @click="triggerImport">
					<template #icon><Upload :size="20" /></template>
					{{ $t('Import M3U8') }}
				</NcButton>
				<NcButton wide @click="deleteAllChannels" type="error">
					{{ $t('Delete all channels') }}
				</NcButton>
				<NcButton wide @click="deleteAllFolders" type="error">
					{{ $t('Delete all folders') }}
				</NcButton>
			</template>
		</NcAppNavigation>

		<div class="app-content">
			<div class="channel-grid">
			<div v-for="ch in filteredChannels" :key="ch.id" class="channel-card" @click="playChannel(ch)">
				<div class="channel-card-logo">
					<img v-if="showLogo(ch)" :src="proxyLogoUrl(ch.logo)" :alt="ch.name" @error="onLogoError(ch)" @load="onLogoLoad($event)" />
					<svg v-else class="channel-card-placeholder" viewBox="0 0 100 80" xmlns="http://www.w3.org/2000/svg">
						<rect x="3" y="3" width="94" height="58" rx="6" fill="var(--color-primary)" />
						<rect x="7" y="7" width="86" height="50" rx="3" fill="var(--color-main-background)" />
						<rect x="25" y="18" width="50" height="28" rx="2" fill="var(--color-primary-element)" />
						<polygon points="38,46 50,38 50,54" fill="var(--color-main-background)" />
						<rect x="38" y="64" width="24" height="5" rx="2" fill="var(--color-primary)" />
						<rect x="30" y="69" width="40" height="4" rx="2" fill="var(--color-primary)" />
					</svg>
				</div>
				<div class="channel-card-name">{{ ch.name || $t('Unnamed') }}</div>
				<div class="channel-card-mode">{{ getModeLabel(getChannelMode(ch)) }}</div>
				<NcActions force-menu @click.stop>
					<NcActionButton @click="setChannelMode(ch, 'auto')" :title="$t('Auto help')">
						<template #icon>
							<svg :style="{ opacity: getChannelMode(ch) === 'auto' ? 1 : 0 }" viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
								<path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
							</svg>
						</template>
						{{ $t('Auto') }}
					</NcActionButton>
					<NcActionButton @click="setChannelMode(ch, 'native')" :title="$t('Direct help')">
						<template #icon>
							<svg :style="{ opacity: getChannelMode(ch) === 'native' ? 1 : 0 }" viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
								<path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
							</svg>
						</template>
						{{ $t('Direct') }}
					</NcActionButton>
					<NcActionButton @click="setChannelMode(ch, 'hls')">
						<template #icon>
							<svg :style="{ opacity: getChannelMode(ch) === 'hls' ? 1 : 0 }" viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
								<path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
							</svg>
						</template>
						{{ $t('M3U8') }}
					</NcActionButton>
					<NcActionButton @click="setChannelMode(ch, 'dash')">
						<template #icon>
							<svg :style="{ opacity: getChannelMode(ch) === 'dash' ? 1 : 0 }" viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
								<path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
							</svg>
						</template>
						{{ $t('MPEG-DASH') }}
					</NcActionButton>
					<NcActionButton @click="editChannel(ch)">
						<template #icon><Edit :size="20" /></template>
						{{ $t('Edit') }}
					</NcActionButton>
					<NcActionButton @click="confirmDeleteChannel(ch)">
						<template #icon><Delete :size="20" /></template>
						{{ $t('Delete') }}
					</NcActionButton>
				</NcActions>
				</div>
			</div>
		</div>
	</NcContent>

	<NcModal v-if="showAddFolder" @close="showAddFolder = false">
		<div class="modal-body">
			<h2>{{ $t('Add folder') }}</h2>
			<NcTextField v-model="newFolderName" :label="$t('Folder name')" />
			<NcButton @click="addFolder">{{ $t('Save') }}</NcButton>
		</div>
	</NcModal>

	<NcModal v-if="editingFolder" @close="editingFolder = null">
		<div class="modal-body">
			<h2>{{ $t('Edit folder') }}</h2>
			<NcTextField v-model="editFolderName" :label="$t('Folder name')" />
			<NcButton @click="saveFolder">{{ $t('Save') }}</NcButton>
		</div>
	</NcModal>

	<NcModal v-if="showAddChannel" @close="showAddChannel = false">
		<div class="modal-body">
			<h2>{{ $t('Add channel') }}</h2>
			<p v-if="errorMsg" class="error-msg">{{ errorMsg }}</p>
			<NcTextField v-model="newChannelName" :label="$t('Channel name')" />
			<NcTextField v-model="newChannelUrl" :label="$t('Stream URL')" :placeholder="'https://example.com/stream.m3u8'" />
			<NcTextField v-model="newChannelLogo" :label="$t('Logo URL')" :placeholder="'https://example.com/logo.png'" />
			<label>{{ $t('Folder') }}</label>
			<select v-model="newChannelFolder">
				<option :value="null">{{ $t('No folder') }}</option>
				<option v-for="f in folders" :key="f.id" :value="f.id">{{ f.name }}</option>
			</select>
			<NcButton @click="addChannel">{{ $t('Save') }}</NcButton>
		</div>
	</NcModal>

	<NcModal v-if="editingChannel" @close="editingChannel = null">
		<div class="modal-body">
			<h2>{{ $t('Edit channel') }}</h2>
			<p v-if="errorMsg" class="error-msg">{{ errorMsg }}</p>
			<NcTextField v-model="editChannelName" :label="$t('Channel name')" />
			<NcTextField v-model="editChannelUrl" :label="$t('Stream URL')" :placeholder="'https://example.com/stream.m3u8'" />
			<NcTextField v-model="editChannelLogo" :label="$t('Logo URL')" :placeholder="'https://example.com/logo.png'" />
			<label>{{ $t('Folder') }}</label>
			<select v-model="editChannelFolder">
				<option :value="null">{{ $t('No folder') }}</option>
				<option v-for="f in folders" :key="f.id" :value="f.id">{{ f.name }}</option>
			</select>
			<NcButton @click="saveChannel">{{ $t('Save') }}</NcButton>
		</div>
	</NcModal>

	<NcModal v-if="deleteTarget" @close="deleteTarget = null">
		<div class="modal-body">
			<p>{{ $t('Delete "{name}"?', { name: deleteTarget.item.name || '' }) }}</p>
			<NcButton @click="doDelete" type="error">{{ $t('Delete') }}</NcButton>
		</div>
	</NcModal>

	<input ref="importInput" type="file" accept=".m3u8,.m3u,.txt" style="display:none" @change="handleImportFile" />

	<NcModal v-if="importResult" @close="importResult = null">
		<div class="modal-body">
			<h2>{{ $t('Import results') }}</h2>
			<p>{{ $t('Added') }}: {{ importResult.added }}</p>
			<p v-if="importResult.errors.length">{{ $t('Errors') }}:</p>
			<p v-for="(err, i) in importResult.errors" :key="i" class="error-msg">{{ err }}</p>
			<NcButton @click="importResult = null">{{ $t('Close') }}</NcButton>
		</div>
	</NcModal>

	<div v-if="currentChannel" class="player-overlay">
		<div class="player-nav-area" @click="prevChannel">
			<div class="player-nav-arrow">‹</div>
		</div>
		<div class="player-center" @click.self="closePlayer">
			<div ref="playerContainer" class="player-container">
				<video v-if="!useIframe" ref="videoPlayer" controls autoplay class="player-video"></video>
				<iframe v-else ref="playerIframe" :src="currentChannel.url" class="player-iframe" allow="autoplay; fullscreen"></iframe>
				<div class="player-info">
					<span class="player-name">{{ currentChannel.name }}</span>
					<div class="player-info-actions">
						<NcButton @click="toggleFullscreen">
							<template #icon>
								<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
									<path d="M7 14H5v5h5v-2H7v-3m-2-4h2V7h3V5H5v5m12 7h-3v2h5v-5h-2v3M14 5v2h3v3h2V5h-5Z"/>
								</svg>
							</template>
						</NcButton>
						<NcButton @click="closePlayer">{{ $t('Close') }}</NcButton>
					</div>
				</div>
			</div>
		</div>
		<div class="player-nav-area" @click="nextChannel">
			<div class="player-nav-arrow">›</div>
		</div>
	</div>
</template>

<script>
import { nextTick } from 'vue'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import NcContent from '@nextcloud/vue/components/NcContent'
import NcModal from '@nextcloud/vue/components/NcModal'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import Edit from 'vue-material-design-icons/Pencil.vue'
import Delete from 'vue-material-design-icons/Delete.vue'
import Plus from 'vue-material-design-icons/Plus.vue'
import Download from 'vue-material-design-icons/Download.vue'
import Upload from 'vue-material-design-icons/Upload.vue'
import Hls from 'hls.js'
import dashjs from 'dashjs'

export default {
	name: 'App',
	components: {
		NcAppNavigation,
		NcAppNavigationItem,
		NcContent,
		NcModal,
		NcButton,
		NcTextField,
		NcActions,
		NcActionButton,
		Edit,
		Delete,
		Plus,
		Download,
		Upload,
	},
	data() {
		return {
			channels: [],
			folders: [],
			selectedFolder: null,
			currentChannel: null,
			hls: null,
			dashPlayer: null,
			showAddFolder: false,
			newFolderName: '',
			editingFolder: null,
			editFolderName: '',
			showAddChannel: false,
			newChannelName: '',
			newChannelUrl: '',
			newChannelLogo: '',
			newChannelFolder: null,
			editingChannel: null,
			editChannelName: '',
			editChannelUrl: '',
			editChannelLogo: '',
			editChannelFolder: null,
			deleteTarget: null,
			errorMsg: '',
			importResult: null,
			useIframe: false,
		}
	},
	computed: {
		filteredChannels() {
			if (this.selectedFolder === null) return this.channels
			return this.channels.filter(ch => ch.folderId === this.selectedFolder)
		},
	},
	async mounted() {
		await this.loadData()
	},
	methods: {
		isValidHttpUrl(str) {
			try {
				const url = new URL(str)
				return url.protocol === 'http:' || url.protocol === 'https:'
			} catch {
				return false
			}
		},
		isValidImageUrl(str) {
			if (!str) return true
			if (!this.isValidHttpUrl(str)) return false
			const ext = str.split('.').pop()?.toLowerCase()
			if (ext && !['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico'].includes(ext)) {
				return false
			}
			return true
		},
		async loadData() {
			try {
				const [channels, folders] = await Promise.all([
					fetch(OC.generateUrl('/apps/iptv/api/v1/channels')).then(r => r.json()),
					fetch(OC.generateUrl('/apps/iptv/api/v1/folders')).then(r => r.json()),
				])
				for (const ch of channels) {
					ch._mode = localStorage.getItem('iptv_mode_' + ch.id) || 'auto'
				}
				this.channels = channels
				this.folders = folders
			} catch (e) {
				console.error('Failed to load data', e)
			}
		},
		playChannel(ch) {
			if (this.currentChannel?.id === ch.id) return
			this.closePlayer()
			this.currentChannel = ch
			const mode = this.getChannelMode(ch)
			const isHls = ch.url.includes('.m3u8')
			if (mode === 'native' && isHls) {
				this.useIframe = true
				return
			}
			nextTick(() => {
				const video = this.$refs.videoPlayer
				if (!video) return
				const playDirect = () => {
					video.src = ch.url
					video.play().catch(() => {})
				}
				if (mode === 'auto') {
					if (ch.url.includes('.mpd')) {
						const player = dashjs.MediaPlayer().create()
						player.on(dashjs.MediaPlayer.events.ERROR, () => {
							player.destroy()
							this.dashPlayer = null
							playDirect()
						})
						player.initialize(video, ch.url, true)
						this.dashPlayer = player
					} else if (Hls.isSupported() && isHls) {
						const hls = new Hls()
						hls.on(Hls.Events.ERROR, (event, data) => {
							if (data.fatal) {
								hls.destroy()
								this.hls = null
								this.useIframe = true
							}
						})
						hls.loadSource(ch.url)
						hls.attachMedia(video)
						this.hls = hls
					} else {
						playDirect()
					}
				} else if (mode === 'hls' && Hls.isSupported() && isHls) {
					const hls = new Hls()
					hls.loadSource(ch.url)
					hls.attachMedia(video)
					this.hls = hls
				} else if (mode === 'dash' && ch.url.includes('.mpd')) {
					const player = dashjs.MediaPlayer().create()
					player.initialize(video, ch.url, true)
					this.dashPlayer = player
				} else {
					playDirect()
				}
			})
		},
		closePlayer() {
			if (this.hls) {
				this.hls.destroy()
				this.hls = null
			}
			if (this.dashPlayer) {
				this.dashPlayer.destroy()
				this.dashPlayer = null
			}
			this.useIframe = false
			this.currentChannel = null
		},
		toggleFullscreen() {
			const el = this.useIframe ? this.$refs.playerIframe : this.$refs.videoPlayer
			if (!el) return
			if (document.fullscreenElement) {
				document.exitFullscreen()
			} else {
				el.requestFullscreen()
			}
		},
		prevChannel() {
			const list = this.filteredChannels
			const idx = list.findIndex(ch => ch.id === this.currentChannel?.id)
			if (idx > 0) this.playChannel(list[idx - 1])
		},
		nextChannel() {
			const list = this.filteredChannels
			const idx = list.findIndex(ch => ch.id === this.currentChannel?.id)
			if (idx < list.length - 1) this.playChannel(list[idx + 1])
		},
		getChannelMode(ch) {
			return ch._mode || 'auto'
		},
		setChannelMode(ch, mode) {
			ch._mode = mode
			localStorage.setItem('iptv_mode_' + ch.id, mode)
		},
		getModeLabel(mode) {
			const labels = { auto: 'Auto', hls: 'M3U8', native: 'Direct', dash: 'MPEG-DASH' }
			return labels[mode] || mode
		},
		showLogo(ch) {
			return ch.logo && !ch._logoError
		},
		proxyLogoUrl(url) {
			if (!url) return ''
			if (url.startsWith('http://')) {
				return OC.generateUrl('/apps/iptv/api/v1/proxy-image?url=') + encodeURIComponent(url)
			}
			return url
		},
		onLogoError(ch) {
			ch._logoError = true
		},
		onLogoLoad(e) {
			const img = e.target
			if (img.naturalWidth > 500 || img.naturalHeight > 500) {
				console.warn('Logo too large, consider using smaller image (max 500x500)')
			}
		},
		getFolderName(folderId) {
			if (!folderId) return this.$t('All channels')
			const f = this.folders.find(f => f.id === folderId)
			return f ? f.name : ''
		},
		getChannelsForExport(folder) {
			if (!folder) return this.channels
			return this.channels.filter(ch => ch.folderId === folder.id)
		},
		exportM3U8(folder) {
			const chs = this.getChannelsForExport(folder)
			if (!chs.length) return
			const groupName = this.getFolderName(folder ? folder.id : null)
			let lines = '#EXTM3U\n'
			for (const ch of chs) {
				const name = (ch.name || '').replace(/"/g, '\\"')
				const logo = ch.logo ? ` tvg-logo="${ch.logo.replace(/"/g, '&quot;')}"` : ''
				lines += `#EXTINF:-1${logo} group-title="${groupName}",${name}\n${ch.url}\n`
			}
			const blob = new Blob([lines], { type: 'application/x-mpegURL;charset=utf-8' })
			const url = URL.createObjectURL(blob)
			const a = document.createElement('a')
			a.href = url
			a.download = (folder ? folder.name : 'all-channels') + '.m3u8'
			document.body.appendChild(a)
			a.click()
			document.body.removeChild(a)
			URL.revokeObjectURL(url)
		},
		triggerImport() {
			this.$refs.importInput?.click()
		},
		parseM3ULine(line) {
			const m = line.match(/^#EXTINF:\s*(-?\d+)\s*(.*)$/)
			if (!m) return null
			const attrs = m[2]
			let logo = ''
			let groupTitle = ''
			const logoMatch = attrs.match(/tvg-logo="([^"]*)"/)
			if (logoMatch) logo = logoMatch[1]
			const groupMatch = attrs.match(/group-title="([^"]*)"/)
			if (groupMatch) groupTitle = groupMatch[1]
			const idx = attrs.lastIndexOf(',')
			const name = idx >= 0 ? attrs.substring(idx + 1).trim() : ''
			return { name, url: '', logo, groupTitle }
		},
		async handleImportFile(e) {
			const file = e.target.files?.[0]
			if (!file) return
			e.target.value = ''
			const text = await file.text()
			const lines = text.split(/\r?\n/)
			const entries = []
			let current = null
			for (const raw of lines) {
				const line = raw.trim()
				if (!line || line === '#EXTM3U') continue
				if (line.startsWith('#EXTINF:')) {
					if (current && current.url) entries.push(current)
					current = this.parseM3ULine(line)
				} else if (line.startsWith('#')) {
					continue
				} else if (current) {
					current.url = line
				}
			}
			if (current && current.url) entries.push(current)
			if (!entries.length) {
				this.errorMsg = this.$t('No valid channels found in file')
				return
			}
			const result = { added: 0, errors: [] }
			const folderCache = {}
			for (const entry of entries) {
				if (!entry.name || !entry.url) {
					result.errors.push(this.$t('Skipped entry with missing name or URL'))
					continue
				}
				if (!this.isValidHttpUrl(entry.url)) {
					result.errors.push(this.$t('Invalid URL') + ': ' + entry.url)
					continue
				}
				let folderId = null
				if (entry.groupTitle) {
					if (folderCache[entry.groupTitle] !== undefined) {
						folderId = folderCache[entry.groupTitle]
					} else {
						const existing = this.folders.find(f => f.name === entry.groupTitle)
						if (existing) {
							folderId = existing.id
							folderCache[entry.groupTitle] = folderId
						} else {
							try {
								const res = await fetch(OC.generateUrl('/apps/iptv/api/v1/folders'), {
									method: 'POST',
									headers: { 'Content-Type': 'application/json', requesttoken: OC.requestToken },
									body: JSON.stringify({ name: entry.groupTitle }),
								})
								if (res.ok) {
									const folder = await res.json()
									folderId = folder.id
									folderCache[entry.groupTitle] = folderId
									this.folders.push(folder)
								}
							} catch { /* ignore */ }
						}
					}
				}
				try {
					const res = await fetch(OC.generateUrl('/apps/iptv/api/v1/channels'), {
						method: 'POST',
						headers: { 'Content-Type': 'application/json', requesttoken: OC.requestToken },
						body: JSON.stringify({
							name: entry.name,
							url: entry.url,
							logo: entry.logo || null,
							folderId,
						}),
					})
					if (res.ok) {
						result.added++
					} else {
						const data = await res.json().catch(() => ({}))
						result.errors.push(this.$t('Failed to add') + ': ' + entry.name)
					}
				} catch (err) {
					result.errors.push(this.$t('Failed to add') + ': ' + entry.name)
				}
			}
			this.importResult = result
			await this.loadData()
		},
		async addFolder() {
			if (!this.newFolderName.trim()) return
			await fetch(OC.generateUrl('/apps/iptv/api/v1/folders'), {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', requesttoken: OC.requestToken },
				body: JSON.stringify({ name: this.newFolderName.trim() }),
			})
			this.newFolderName = ''
			this.showAddFolder = false
			await this.loadData()
		},
		openAddFolder() {
			this.errorMsg = ''
			this.showAddFolder = true
		},
		openAddChannel() {
			this.errorMsg = ''
			this.showAddChannel = true
		},
		editFolder(folder) {
			this.editingFolder = folder
			this.editFolderName = folder.name
		},
		async saveFolder() {
			if (!this.editingFolder || !this.editFolderName.trim()) return
			await fetch(OC.generateUrl('/apps/iptv/api/v1/folders/{id}', { id: this.editingFolder.id }), {
				method: 'PUT',
				headers: { 'Content-Type': 'application/json', requesttoken: OC.requestToken },
				body: JSON.stringify({ name: this.editFolderName.trim() }),
			})
			this.editingFolder = null
			await this.loadData()
		},
		confirmDeleteFolder(folder) {
			this.deleteTarget = { type: 'folder', item: folder }
		},
		async addChannel() {
			this.errorMsg = ''
			const url = this.newChannelUrl.trim()
			const logo = this.newChannelLogo.trim()
			if (!this.newChannelName.trim() || !url) return
			if (!this.isValidHttpUrl(url)) {
				this.errorMsg = this.$t('Stream URL must be a valid http or https URL')
				return
			}
			if (logo && !this.isValidImageUrl(logo)) {
				this.errorMsg = this.$t('Logo URL must be a valid http or https URL (jpg, png, gif, webp, svg)')
				return
			}
			const res = await fetch(OC.generateUrl('/apps/iptv/api/v1/channels'), {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', requesttoken: OC.requestToken },
				body: JSON.stringify({
					name: this.newChannelName.trim(),
					url,
					logo: logo || null,
					folderId: this.newChannelFolder,
				}),
			})
			if (!res.ok) {
				const data = await res.json().catch(() => ({}))
				this.errorMsg = data.error || this.$t('Failed to add channel')
				return
			}
			this.newChannelName = ''
			this.newChannelUrl = ''
			this.newChannelLogo = ''
			this.newChannelFolder = null
			this.showAddChannel = false
			await this.loadData()
		},
		editChannel(ch) {
			this.errorMsg = ''
			this.editingChannel = ch
			this.editChannelName = ch.name
			this.editChannelUrl = ch.url
			this.editChannelLogo = ch.logo || ''
			this.editChannelFolder = ch.folderId
		},
		async saveChannel() {
			this.errorMsg = ''
			if (!this.editingChannel || !this.editChannelName.trim() || !this.editChannelUrl.trim()) return
			const url = this.editChannelUrl.trim()
			const logo = this.editChannelLogo.trim()
			if (!this.isValidHttpUrl(url)) {
				this.errorMsg = this.$t('Stream URL must be a valid http or https URL')
				return
			}
			if (logo && !this.isValidImageUrl(logo)) {
				this.errorMsg = this.$t('Logo URL must be a valid http or https URL (jpg, png, gif, webp, svg)')
				return
			}
			const res = await fetch(OC.generateUrl('/apps/iptv/api/v1/channels/{id}', { id: this.editingChannel.id }), {
				method: 'PUT',
				headers: { 'Content-Type': 'application/json', requesttoken: OC.requestToken },
				body: JSON.stringify({
					name: this.editChannelName.trim(),
					url,
					logo: logo || null,
					folderId: this.editChannelFolder,
				}),
			})
			if (!res.ok) {
				const data = await res.json().catch(() => ({}))
				this.errorMsg = data.error || this.$t('Failed to save channel')
				return
			}
			this.editingChannel = null
			await this.loadData()
		},
		confirmDeleteChannel(ch) {
			this.deleteTarget = { type: 'channel', item: ch }
		},
		async doDelete() {
			if (!this.deleteTarget) return
			const { type, item } = this.deleteTarget
			const base = type === 'folder' ? 'folders' : 'channels'
			await fetch(OC.generateUrl(`/apps/iptv/api/v1/${base}/{id}`, { id: item.id }), {
				method: 'DELETE',
				headers: { requesttoken: OC.requestToken },
			})
			this.deleteTarget = null
			if (this.currentChannel?.id === item.id) this.closePlayer()
			await this.loadData()
		},
		async deleteAllChannels() {
			if (!confirm(this.$t('Are you sure?'))) return
			await fetch(OC.generateUrl('/apps/iptv/api/v1/channels'), { method: 'DELETE', headers: { requesttoken: OC.requestToken } })
			this.closePlayer()
			await this.loadData()
		},
		async deleteAllFolders() {
			if (!confirm(this.$t('Are you sure?'))) return
			await fetch(OC.generateUrl('/apps/iptv/api/v1/folders'), { method: 'DELETE', headers: { requesttoken: OC.requestToken } })
			await this.loadData()
		},
	},
}
</script>

<style scoped>
.app-content {
	flex: 1;
	overflow-y: auto;
	position: relative;
	padding-bottom: 140px;
}
.channel-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
	gap: 16px;
	padding: 16px;
}
.channel-card {
	background: var(--color-main-background);
	border: 1px solid var(--color-border);
	border-radius: 8px;
	padding: 12px;
	position: relative;
}
.channel-card-logo {
	width: 100%;
	height: 100px;
	display: flex;
	align-items: center;
	justify-content: center;
	overflow: hidden;
	cursor: pointer;
}
.channel-card-logo img {
	max-width: 100%;
	max-height: 100%;
	object-fit: contain;
}
.channel-card-placeholder {
	width: 100%;
	height: 100%;
}
.channel-card {
	cursor: pointer;
}
.channel-card:hover {
	box-shadow: 0 0 0 2px var(--color-primary);
	transform: translateY(-2px);
	transition: all 0.15s ease;
}
.channel-card-name {
	text-align: center;
	margin-top: 8px;
	font-weight: 600;
}
.channel-card-mode {
	text-align: center;
	font-size: 11px;
	color: var(--color-text-maxcontrast);
	margin-top: 2px;
}

.player-overlay {
	position: fixed;
	inset: 0;
	background: rgba(0, 0, 0, 0.7);
	z-index: 2000;
	overflow: hidden;
}
.player-nav-area {
	position: absolute;
	top: 50%;
	transform: translateY(-50%);
	width: 44px;
	height: 80px;
	display: flex;
	align-items: center;
	justify-content: center;
	cursor: pointer;
	opacity: 0.5;
	transition: opacity 0.15s;
	user-select: none;
	z-index: 10;
	border-radius: 8px;
}
.player-nav-area:first-child {
	left: 4px;
}
.player-nav-area:last-child {
	right: 4px;
}
.player-nav-area:hover {
	opacity: 1;
	background: rgba(0,0,0,0.3);
}
.player-nav-arrow {
	font-size: 40px;
	color: #fff;
	line-height: 1;
}
.player-center {
	position: absolute;
	inset: 0;
	display: flex;
	align-items: center;
	justify-content: center;
}
.player-container {
	background: var(--color-main-background);
	border-radius: 12px;
	padding: 12px;
	width: min(92vw, 800px);
	max-height: 85vh;
	display: flex;
	flex-direction: column;
	gap: 6px;
	box-sizing: border-box;
}
.player-video {
	width: 100%;
	height: auto;
	max-height: 70vh;
	border-radius: 8px;
	object-fit: contain;
	flex-shrink: 1;
	min-height: 0;
}
.player-iframe {
	width: 100%;
	height: 70vh;
	max-width: 100%;
	max-height: 70vh;
	border: none;
	border-radius: 8px;
	flex-shrink: 1;
	min-height: 0;
}

.player-info {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 12px;
}
.player-info-actions {
	display: flex;
	align-items: center;
	gap: 8px;
}
.modal-body {
	padding: 24px;
}
.modal-body > * + * {
	margin-top: 6px;
}
.modal-body h2 {
	margin-bottom: 16px;
}
.modal-body label {
	display: block;
	margin-top: 12px;
	margin-bottom: 4px;
	font-weight: 600;
	font-size: 13px;
	color: var(--color-text-maxcontrast);
}
.modal-body select {
	display: block;
	width: 100%;
	padding: 10px 12px;
	border: 2px solid var(--color-border-maxcontrast);
	border-radius: var(--border-radius-element, 6px);
	background: var(--color-main-background);
	color: var(--color-main-text);
	font-size: var(--default-font-size, 14px);
	line-height: 1.4;
	min-height: var(--default-clickable-area, 44px);
}
.error-msg {
	color: var(--color-error);
	font-size: 13px;
	margin-bottom: 12px;
	padding: 8px;
	background: var(--color-background-dark);
	border-radius: 4px;
}
</style>
