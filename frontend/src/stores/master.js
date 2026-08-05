import { defineStore } from 'pinia'
import api from '@/services/api'

export const useMasterStore = defineStore('master', {
  state: () => ({
    jenjang: [],
    unitKerja: [],
    statusPengajuan: [],
    jenisDokumen: [],
    jenisDokumenPga: [],
    akreditasi: [],
    perguruanTinggi: [],
    prodi: [],
    loading: false,
  }),

  actions: {
    async fetchJenjang() {
      try {
        const response = await api.get('/master/jenjang')
        this.jenjang = response.data
      } catch (error) {
        console.error('Failed to fetch jenjang:', error)
      }
    },

    async fetchUnitKerja() {
      try {
        const response = await api.get('/master/unit-kerja')
        this.unitKerja = response.data
      } catch (error) {
        console.error('Failed to fetch unit kerja:', error)
      }
    },

    async fetchStatusPengajuan() {
      try {
        const response = await api.get('/master/status-pengajuan')
        this.statusPengajuan = response.data
      } catch (error) {
        console.error('Failed to fetch status:', error)
      }
    },

    async fetchJenisDokumen(refresh = false) {
      try {
        const response = await api.get('/master/jenis-dokumen', {
          params: refresh ? { refresh: true } : {}
        })
        this.jenisDokumen = response.data
      } catch (error) {
        console.error('Failed to fetch jenis dokumen:', error)
      }
    },

    async fetchJenisDokumenPga(refresh = false) {
      try {
        const response = await api.get('/master/jenis-dokumen-pga', {
          params: refresh ? { refresh: true } : {}
        })
        this.jenisDokumenPga = response.data
        return response.data
      } catch (error) {
        console.error('Failed to fetch jenis dokumen PGA:', error)
        return []
      }
    },

    async fetchAkreditasi() {
      try {
        const response = await api.get('/master/akreditasi')
        this.akreditasi = response.data
      } catch (error) {
        console.error('Failed to fetch akreditasi:', error)
      }
    },

    async fetchPerguruanTinggi(keyword = '') {
      try {
        const response = await api.get('/master/perguruan-tinggi', {
          params: { keyword }
        })
        this.perguruanTinggi = response.data
        return response.data
      } catch (error) {
        console.error('Failed to fetch perguruan tinggi:', error)
        return []
      }
    },

    async fetchProdi(perguruanTinggiId = null, keyword = '') {
      try {
        const response = await api.get('/master/prodi', {
          params: {
            perguruan_tinggi_id: perguruanTinggiId,
            keyword
          }
        })
        this.prodi = response.data
        return response.data
      } catch (error) {
        console.error('Failed to fetch prodi:', error)
        return []
      }
    },

    async fetchAll() {
      this.loading = true
      try {
        await Promise.all([
          this.fetchJenjang(),
          this.fetchUnitKerja(),
          this.fetchStatusPengajuan(),
          this.fetchJenisDokumen(),
          this.fetchAkreditasi(),
        ])
      } finally {
        this.loading = false
      }
    },
  },
})
