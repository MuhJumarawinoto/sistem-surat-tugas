import { defineStore } from 'pinia'
import api from '@/services/api'

export const usePgaStore = defineStore('pga', {
  state: () => ({
    pgaList: [],
    currentPga: null,
    loading: false,
    error: null,
  }),

  actions: {
    async fetchPga(params = {}) {
      this.loading = true
      this.error = null
      try {
        const response = await api.get('/pga', { params })
        this.pgaList = response.data.data || []
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch PGA'
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchPgaDetail(id) {
      this.loading = true
      this.error = null
      try {
        const response = await api.get(`/pga/${id}`)
        this.currentPga = response.data
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch PGA detail'
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchPgaById(id) {
      return await this.fetchPgaDetail(id)
    },

    async createPga(data) {
      this.loading = true
      this.error = null
      try {
        const response = await api.post('/pga', data, {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        })
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to create PGA'
        throw error
      } finally {
        this.loading = false
      }
    },

    async updatePga(id, data) {
      this.loading = true
      this.error = null
      try {
        // For FormData with PUT, use POST with _method override
        // This is more reliable than PUT for multipart/form-data
        if (data instanceof FormData) {
          data.append('_method', 'PUT')
          const response = await api.post(`/pga/${id}`, data, {
            headers: {
              'Content-Type': 'multipart/form-data',
            },
          })
          return response.data
        }

        // For regular JSON data
        const response = await api.put(`/pga/${id}`, data, {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        })
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to update PGA'
        throw error
      } finally {
        this.loading = false
      }
    },

    async deletePga(id) {
      this.loading = true
      this.error = null
      try {
        await api.delete(`/pga/${id}`)
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to delete PGA'
        throw error
      } finally {
        this.loading = false
      }
    },

    async submitPga(id) {
      this.loading = true
      this.error = null
      try {
        const response = await api.post(`/pga/${id}/submit`)

        // Update PGA in the list
        const index = this.pgaList.findIndex(p => p.id === id)
        if (index !== -1) {
          this.pgaList[index] = response.data.data
        }

        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to submit PGA'
        throw error
      } finally {
        this.loading = false
      }
    },

    async restorePga(id) {
      this.loading = true
      this.error = null
      try {
        const response = await api.post(`/pga/${id}/restore`)

        // Remove from list if restored (will be back in draft)
        const index = this.pgaList.findIndex(p => p.id === id)
        if (index !== -1) {
          this.pgaList.splice(index, 1)
        }

        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to restore PGA'
        throw error
      } finally {
        this.loading = false
      }
    },

    async approvePga(id) {
      this.loading = true
      this.error = null
      try {
        const response = await api.post(`/pga/${id}/approve`)

        // Update PGA in the list
        const index = this.pgaList.findIndex(p => p.id === id)
        if (index !== -1) {
          this.pgaList[index] = response.data.data
        }

        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to approve PGA'
        throw error
      } finally {
        this.loading = false
      }
    },

    async rejectPga(id, catatan) {
      this.loading = true
      this.error = null
      try {
        console.log('Rejecting PGA:', { id, catatan, type: typeof catatan })
        const response = await api.post(`/pga/${id}/reject`, { catatan_tolak: catatan })

        // Update PGA in the list
        const index = this.pgaList.findIndex(p => p.id === id)
        if (index !== -1) {
          this.pgaList[index] = response.data.data
        }

        return response.data
      } catch (error) {
        console.error('Reject PGA error:', error.response?.status, error.response?.data)
        this.error = error.response?.data?.message || 'Failed to reject PGA'
        throw error
      } finally {
        this.loading = false
      }
    },

    /**
     * Get document URL for preview
     */
    getDocumentUrl(pga, type) {
      const fileMap = {
        surat_pengantar: pga.surat_pengantar_file,
        sk_pangkat: pga.sk_pangkat_file,
        sk_jabatan: pga.sk_jabatan_file,
        surat_izin: pga.surat_izin_file,
        ijazah: pga.ijazah_file,
        ijazah_forlap: pga.ijazah_forlap_file,
        transkrip: pga.transkrip_file,
        akreditasi: pga.akreditasi_file,
        ijazah_dikti: pga.ijazah_dikti_file,
        sk_kum: pga.sk_kum_file,
      }

      const filePath = fileMap[type]
      if (!filePath) return null

      return `${import.meta.env.VITE_API_URL?.replace('/api', '') || ''}/storage/${filePath}`
    },

    /**
     * Get download URL for document
     */
    downloadDocument(pgaId, type) {
      const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
      return `${apiUrl}/pga/${pgaId}/document/${type}`
    },

    /**
     * Get all document info for a PGA
     */
    getDocumentInfo(pga) {
      const documentTypes = [
        { key: 'surat_pengantar', label: 'Surat Pengantar/Usulan dari Instansi', isRequired: true },
        { key: 'sk_pangkat', label: 'SK Pangkat Terakhir', isRequired: true },
        { key: 'sk_jabatan', label: 'SK Jabatan Terbaru', isRequired: true },
        { key: 'surat_izin', label: 'Surat Izin Belajar/Tugas Belajar', isRequired: true },
        { key: 'ijazah', label: 'Asli Ijazah', isRequired: true },
        { key: 'ijazah_forlap', label: 'Lampiran Forlap Dikti', isRequired: true },
        { key: 'transkrip', label: 'Asli Transkrip Nilai', isRequired: true },
        { key: 'akreditasi', label: 'Akreditasi Program Studi', isRequired: true },
        { key: 'ijazah_dikti', label: 'Penyetaraan Ijazah Luar Negeri', isRequired: false },
      ]

      return documentTypes.map(doc => ({
        ...doc,
        hasFile: !!pga[`${doc.key}_file`],
        url: this.getDocumentUrl(pga, doc.key),
        downloadUrl: pga ? this.downloadDocument(pga.id, doc.key) : null,
      }))
    },

    /**
     * Check if all required documents are uploaded
     */
    hasAllRequiredDocuments(pga) {
      const requiredFields = [
        'surat_pengantar_file',
        'sk_pangkat_file',
        'sk_jabatan_file',
        'surat_izin_file',
        'ijazah_file',
        'ijazah_forlap_file',
        'transkrip_file',
        'akreditasi_file',
      ]

      return requiredFields.every(field => !!pga[field])
    },

    /**
     * Get uploaded documents count
     */
    getUploadedCount(pga) {
      const documentFields = [
        'surat_pengantar_file',
        'sk_pangkat_file',
        'sk_jabatan_file',
        'surat_izin_file',
        'ijazah_file',
        'ijazah_forlap_file',
        'transkrip_file',
        'akreditasi_file',
        'ijazah_dikti_file',
      ]

      return documentFields.filter(field => !!pga[field]).length
    },
  },
})
