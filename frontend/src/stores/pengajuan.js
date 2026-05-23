import { defineStore } from 'pinia'
import api from '@/services/api'

export const usePengajuanStore = defineStore('pengajuan', {
  state: () => ({
    pengajuanList: [],
    currentPengajuan: null,
    loading: false,
    error: null,
  }),

  actions: {
    async fetchPengajuan(params = {}) {
      this.loading = true
      this.error = null
      try {
        const response = await api.get('/pengajuan', { params })
        this.pengajuanList = response.data.data || []
        return response.data.data || []
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch pengajuan'
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchPengajuanDetail(id) {
      this.loading = true
      this.error = null
      try {
        const response = await api.get(`/pengajuan/${id}`)
        this.currentPengajuan = response.data
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch pengajuan detail'
        throw error
      } finally {
        this.loading = false
      }
    },

    async createPengajuan(data) {
      this.loading = true
      this.error = null
      try {
        const response = await api.post('/pengajuan', data)
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to create pengajuan'
        throw error
      } finally {
        this.loading = false
      }
    },

    async updatePengajuan(id, data) {
      this.loading = true
      this.error = null
      try {
        const response = await api.put(`/pengajuan/${id}`, data)
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to update pengajuan'
        throw error
      } finally {
        this.loading = false
      }
    },

    async deletePengajuan(id) {
      this.loading = true
      this.error = null
      try {
        await api.delete(`/pengajuan/${id}`)
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to delete pengajuan'
        throw error
      } finally {
        this.loading = false
      }
    },

    async submitPengajuan(id) {
      this.loading = true
      this.error = null
      try {
        const response = await api.post(`/pengajuan/${id}/submit`)

        // Update pengajuan in the list
        const index = this.pengajuanList.findIndex(p => p.id === id)
        if (index !== -1) {
          this.pengajuanList[index] = response.data
        }

        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to submit pengajuan'
        throw error
      } finally {
        this.loading = false
      }
    },

    async cancelPengajuan(id) {
      this.loading = true
      this.error = null
      try {
        const response = await api.post(`/pengajuan/${id}/cancel`)

        // Update pengajuan in the list
        const index = this.pengajuanList.findIndex(p => p.id === id)
        if (index !== -1) {
          this.pengajuanList[index] = response.data
        }

        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to cancel pengajuan'
        throw error
      } finally {
        this.loading = false
      }
    },

    async restorePengajuan(id) {
      this.loading = true
      this.error = null
      try {
        const response = await api.post(`/pengajuan/${id}/restore`)

        // Remove from list if restored (will be back in draft)
        const index = this.pengajuanList.findIndex(p => p.id === id)
        if (index !== -1) {
          this.pengajuanList.splice(index, 1)
        }

        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to restore pengajuan'
        throw error
      } finally {
        this.loading = false
      }
    },

    async getNomorPengajuan() {
      try {
        const response = await api.get('/pengajuan/nomor')
        return response.data.nomor_pengajuan
      } catch (error) {
        throw error
      }
    },
  },
})
