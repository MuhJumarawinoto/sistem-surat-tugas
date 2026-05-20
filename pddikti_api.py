import requests
import time
from fastapi import FastAPI, Query, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from typing import Optional

app = FastAPI(
    title="PDDikti Data API",
    description="API untuk data Universitas, Prodi, dan Akreditasi dari PDDikti",
    version="1.0.0",
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

PDDIKTI_BASE = "https://pddikti.rone.dev/api"
SEMESTER = "20241"
sess = requests.Session()
sess.headers.update({"Accept": "application/json"})


def pddikti_get(endpoint, retries=3, delay=1):
    url = f"{PDDIKTI_BASE}{endpoint}"
    for attempt in range(retries):
        try:
            r = sess.get(url, timeout=30)
            r.raise_for_status()
            return r.json()
        except Exception:
            time.sleep(delay * (attempt + 1))
    return None


@app.get("/")
def root():
    return {
        "message": "PDDikti Data API - Universitas, Prodi, Akreditasi",
        "version": "1.0.0",
        "endpoints": {
            "/api/universitas": "Cari universitas by keyword",
            "/api/universitas/{id}/prodi": "List prodi universitas + akreditasi",
            "/api/universitas/{id}/detail": "Detail universitas",
            "/api/prodi": "Cari prodi by keyword + akreditasi",
            "/api/prodi/{id}": "Detail prodi + akreditasi",
            "/api/search": "Search all (universitas + prodi)",
        },
        "source": "https://pddikti.rone.dev/api/",
    }


@app.get("/api/universitas")
def search_universitas(keyword: str = Query(..., min_length=2, description="Keyword nama universitas")):
    data = pddikti_get(f"/search/pt/{keyword}/")
    if not data:
        return {"success": False, "data": [], "total": 0}

    results = []
    for item in data:
        results.append({
            "id": item.get("id", ""),
            "kode_pt": item.get("kode", ""),
            "nama_pt": item.get("nama", ""),
            "nama_singkat": item.get("nama_singkat", ""),
        })

    return {"success": True, "total": len(results), "data": results}


@app.get("/api/universitas/{id_pt}/detail")
def universitas_detail(id_pt: str):
    data = pddikti_get(f"/pt/detail/{id_pt}/")
    if not data:
        raise HTTPException(status_code=404, detail="Universitas tidak ditemukan")

    return {
        "success": True,
        "data": {
            "nama_pt": data.get("nama_pt", ""),
            "nama_singkat": data.get("nm_singkat", ""),
            "kode_pt": data.get("kode_pt", "").strip(),
            "kelompok": data.get("kelompok", ""),
            "pembina": data.get("pembina", ""),
            "status": data.get("status_pt", ""),
            "akreditasi": data.get("akreditasi_pt", ""),
            "provinsi": data.get("provinsi_pt", ""),
            "kab_kota": data.get("kab_kota_pt", ""),
            "kecamatan": data.get("kecamatan_pt", ""),
            "alamat": data.get("alamat", ""),
            "kode_pos": data.get("kode_pos", ""),
            "website": data.get("website", ""),
            "email": data.get("email", ""),
            "telepon": data.get("no_tel", ""),
            "fax": data.get("no_fax", ""),
            "tanggal_berdiri": data.get("tgl_berdiri_pt", ""),
            "latitude": data.get("lintang_pt", 0),
            "longitude": data.get("bujur_pt", 0),
        },
    }


@app.get("/api/universitas/{id_pt}/prodi")
def universitas_prodi(
    id_pt: str,
    semester: Optional[str] = Query(None, description="Semester (format: 20241/20242)"),
    with_detail: bool = Query(False, description="Ambil detail akreditasi tiap prodi"),
):
    smt = semester or SEMESTER
    prodi_list = pddikti_get(f"/pt/prodi/{id_pt}/{smt}")

    if not prodi_list:
        return {"success": True, "semester": smt, "total": 0, "data": []}

    if not isinstance(prodi_list, list):
        prodi_list = [prodi_list]

    results = []
    for p in prodi_list:
        rec = {
            "id_prodi": p.get("id_sms", ""),
            "nama_prodi": p.get("nama_prodi", ""),
            "kode_prodi": p.get("kode_prodi", ""),
            "jenjang": p.get("jenj_didik", ""),
            "bidang_ilmu": p.get("kel_bidang", ""),
            "status": p.get("status", ""),
            "akreditasi": p.get("akreditasi", ""),
            "akreditasi_internasional": p.get("akreditasi_internasional", ""),
        }

        if with_detail and p.get("id_sms"):
            det = pddikti_get(f"/prodi/detail/{p['id_sms']}/")
            if det and det.get("nama_prodi"):
                rec["akreditasi"] = det.get("akreditasi", rec["akreditasi"])
                rec["akreditasi_internasional"] = det.get("akreditasi_internasional", rec["akreditasi_internasional"])
                rec["status"] = det.get("status", rec["status"])
                rec["tanggal_berdiri"] = det.get("tgl_berdiri", "")
                rec["sk_selenggara"] = det.get("sk_selenggara", "")
                rec["provinsi"] = det.get("provinsi", "")
                rec["kab_kota"] = det.get("kab_kota", "")
            time.sleep(0.2)

        results.append(rec)

    return {"success": True, "semester": smt, "total": len(results), "data": results}


@app.get("/api/prodi")
def search_prodi(keyword: str = Query(..., min_length=2, description="Keyword nama prodi")):
    data = pddikti_get(f"/search/prodi/{keyword}/")
    if not data:
        return {"success": False, "data": [], "total": 0}

    results = []
    for item in data:
        results.append({
            "id_prodi": item.get("id", ""),
            "nama_prodi": item.get("nama", ""),
            "jenjang": item.get("jenjang", ""),
            "nama_pt": item.get("pt", ""),
            "nama_pt_singkat": item.get("pt_singkat", ""),
        })

    return {"success": True, "total": len(results), "data": results}


@app.get("/api/prodi/{id_prodi}")
def prodi_detail(id_prodi: str):
    data = pddikti_get(f"/prodi/detail/{id_prodi}/")
    if not data:
        raise HTTPException(status_code=404, detail="Prodi tidak ditemukan")

    return {
        "success": True,
        "data": {
            "id_prodi": data.get("id_sms", ""),
            "nama_prodi": data.get("nama_prodi", ""),
            "kode_prodi": data.get("kode_prodi", ""),
            "jenjang": data.get("jenj_didik", ""),
            "bidang_ilmu": data.get("kel_bidang", ""),
            "status": data.get("status", ""),
            "akreditasi": data.get("akreditasi", ""),
            "akreditasi_internasional": data.get("akreditasi_internasional", ""),
            "status_akreditasi": data.get("status_akreditasi", ""),
            "nama_pt": data.get("nama_pt", ""),
            "kode_pt": data.get("kode_pt", "").strip(),
            "tanggal_berdiri": data.get("tgl_berdiri", ""),
            "sk_selenggara": data.get("sk_selenggara", ""),
            "telepon": data.get("no_tel", ""),
            "fax": data.get("no_fax", ""),
            "website": data.get("website", ""),
            "email": data.get("email", ""),
            "alamat": data.get("alamat", ""),
            "provinsi": data.get("provinsi", ""),
            "kab_kota": data.get("kab_kota", ""),
            "kecamatan": data.get("kecamatan", ""),
            "latitude": data.get("lintang", 0),
            "longitude": data.get("bujur", 0),
        },
    }


@app.get("/api/search")
def search_all(keyword: str = Query(..., min_length=2, description="Keyword pencarian")):
    data = pddikti_get(f"/search/all/{keyword}/")
    if not data:
        return {"success": False, "data": {}, "total": 0}

    universitas = []
    prodi = []

    for item in data.get("pt", []):
        universitas.append({
            "id": item.get("id", ""),
            "kode_pt": item.get("kode", ""),
            "nama_pt": item.get("nama", ""),
            "nama_singkat": item.get("nama_singkat", ""),
        })

    for item in data.get("prodi", []):
        prodi.append({
            "id_prodi": item.get("id", ""),
            "nama_prodi": item.get("nama", ""),
            "jenjang": item.get("jenjang", ""),
            "nama_pt": item.get("pt", ""),
        })

    return {
        "success": True,
        "total": {"universitas": len(universitas), "prodi": len(prodi)},
        "data": {"universitas": universitas, "prodi": prodi},
    }


@app.get("/api/stats/prodi-akreditasi")
def stats_prodi_akreditasi():
    data = pddikti_get("/stats/prodi-count-akreditasi/")
    if not data:
        raise HTTPException(status_code=502, detail="Gagal mengambil data statistik")
    return {"success": True, "data": data}


@app.get("/api/stats/pt-akreditasi")
def stats_pt_akreditasi():
    data = pddikti_get("/stats/pt-count-akreditasi/")
    if not data:
        raise HTTPException(status_code=502, detail="Gagal mengambil data statistik")
    return {"success": True, "data": data}
