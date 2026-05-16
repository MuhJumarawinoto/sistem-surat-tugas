import { defineStore } from 'pinia'
import api from '@/services/api'

export const useMasterStore = defineStore('master', {
  state: () => ({
    jenjang: [],
    unitKerja: [],
    statusPengajuan: [],
    jenisDokumen: [],
    akreditasi: [],
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

    async fetchJenisDokumen() {
      try {
        const response = await api.get('/master/jenis-dokumen')
        this.jenisDokumen = response.data
      } catch (error) {
        console.error('Failed to fetch jenis dokumen:', error)
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
