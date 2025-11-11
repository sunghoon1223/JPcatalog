#!/usr/bin/env python3
import os
import json
import re
import shutil
import urllib.parse
from pathlib import Path
from typing import Dict, List, Optional, Tuple

import requests

ROOT = Path(__file__).resolve().parents[1]
PUBLIC_HTML = ROOT / 'public_html'
OVR_ROOT = PUBLIC_HTML / 'images' / 'overrides'
OVR_FILES = OVR_ROOT / 'files'
OVR_JSON = OVR_ROOT / 'overrides.json'

SUPABASE_URL = os.environ.get('SUPABASE_URL') or os.environ.get('NEXT_PUBLIC_SUPABASE_URL') or 'https://bjqadhzkoxdwyfsglrvq.supabase.co'
SUPABASE_KEY = (
    os.environ.get('SUPABASE_ANON_KEY')
    or os.environ.get('NEXT_PUBLIC_SUPABASE_ANON_KEY')
    or os.environ.get('NEXT_PUBLIC_SUPABASE_PUBLISHABLE_DEFAULT_KEY')
    or 'sb_publishable_NRzhcehsa_tDtdXOOt4q9w_7mwWmgTB'
)

HEADERS = {'apikey': SUPABASE_KEY, 'Authorization': 'Bearer ' + SUPABASE_KEY}


def is_noise(url: str) -> bool:
    u = (url or '').lower()
    if '/lowres/' in u or '/_filtered_out/lowres/' in u:
        return True
    if '/qr/' in u or '/_filtered_out/qr/' in u:
        return True
    if '/banners/' in u or '/_filtered_out/banners/' in u:
        return True
    if 'thumbnail' in u or 'thumb_' in u or 'thumb-' in u:
        return True
    if '/placeholder.svg' in u:
        return True
    return False


def load_overrides() -> Dict:
    if OVR_JSON.exists():
        try:
            return json.loads(OVR_JSON.read_text(encoding='utf-8')) or {}
        except Exception:
            return {}
    return {}


def save_overrides(data: Dict) -> None:
    OVR_ROOT.mkdir(parents=True, exist_ok=True)
    OVR_JSON.write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding='utf-8')


def copy_into_overrides(cat: str, pid: str, urls: List[str]) -> List[str]:
    dest_dir = OVR_FILES / cat / pid
    dest_dir.mkdir(parents=True, exist_ok=True)
    out: List[str] = []
    for u in urls:
        if not u or is_noise(u):
            continue
        src = None
        # direct local path under public_html
        if u.startswith('/images/'):
            cand = PUBLIC_HTML / u.lstrip('/')
            if cand.is_file():
                src = cand
        # fallback: try reports/original_crawled/<cat>/<pid> with base filename
        if src is None:
            base = os.path.basename(urllib.parse.urlparse(u).path)
            ro_base = ROOT / 'reports' / 'original_crawled' / cat / str(pid)
            for sub in ('product','gallery','images/gallery','images/product'):
                p = ro_base / sub / base
                if p.is_file():
                    src = p
                    break
        if src is None or not src.is_file():
            continue
        dst = dest_dir / src.name
        if not dst.exists():
            shutil.copy2(src, dst)
        out.append(f"/images/overrides/files/{cat}/{pid}/{src.name}")
    # dedupe by basename preserving order
    seen = set()
    uniq: List[str] = []
    for u in out:
        base = os.path.basename(u)
        if base in seen:
            continue
        seen.add(base)
        uniq.append(u)
    return uniq


def normalize_identifier(local_url: str) -> Tuple[Optional[str], Optional[str], Optional[str]]:
    # Returns (slug_or_id, category, pid)
    try:
        m = re.search(r"/products/([^/?#]+)", local_url)
        slug = m.group(1) if m else None
        cat = None
        if slug:
            if slug.startswith('agv-'): cat = 'agv'
            elif slug.startswith('equipment-'): cat = 'equipment'
            elif slug.startswith('polyurethane-'): cat = 'polyurethane'
            elif slug.startswith('rubber-'): cat = 'rubber'
        pid = None
        if slug:
            m2 = re.search(r"-(\d+)$", slug)
            if m2: pid = m2.group(1)
            else:
                m3 = re.search(r"_(\d+)$", slug)
                if m3: pid = m3.group(1)
        return slug, cat, pid
    except Exception:
        return None, None, None


def fetch_supabase_product(slug_or_id: str) -> Optional[Dict]:
    orq = f"(id.eq.{slug_or_id},slug.eq.{slug_or_id})"
    url = f"{SUPABASE_URL}/rest/v1/products?select=*&or={urllib.parse.quote(orq)}&limit=1"
    r = requests.get(url, headers=HEADERS, timeout=20)
    if not r.ok:
        return None
    arr = r.json() or []
    return arr[0] if arr else None


def apply_for_local_urls(urls: List[str]) -> int:
    overrides = load_overrides()
    updated = 0
    for local in urls:
        slug, cat, pid = normalize_identifier(local)
        if not slug:
            continue
        p = fetch_supabase_product(slug)
        if not p:
            continue
        cat = cat or ((p.get('category_id') or '').replace('cat_','') or None)
        pid = pid or (p.get('id') or '').split('_')[-1]
        if not cat or not pid:
            continue
        main = p.get('main_image_url')
        imgs = p.get('image_urls') or []
        # Build ordered list with main first
        ordered = []
        if main: ordered.append(main)
        for u in imgs:
            if u == main: continue
            ordered.append(u)
        prod_urls = copy_into_overrides(cat, pid, ordered)
        if not prod_urls:
            continue
        bucket = overrides.setdefault(cat, {})
        entry = bucket.setdefault(pid, {})
        draw = entry.get('drawing_images') or []
        entry['product_images'] = prod_urls
        entry['drawing_images'] = draw
        bucket[pid] = entry
        updated += 1
    save_overrides(overrides)
    return updated


def main() -> int:
    import argparse
    ap = argparse.ArgumentParser()
    ap.add_argument('--file', required=True, help='Text file with local product URLs')
    args = ap.parse_args()
    urls = [line.strip() for line in Path(args.file).read_text(encoding='utf-8').splitlines() if '/products/' in line]
    n = apply_for_local_urls(urls)
    print(f"Applied Supabase images to overrides for {n} products")
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
